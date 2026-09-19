<?php

namespace App\Services;

use App\Data\FcmResult;
use App\Models\PushNotification;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * FCM HTTP v1, sans SDK : un JWT signé avec le compte de service est échangé
 * contre un jeton OAuth, puis chaque appareil reçoit son propre message —
 * l'API v1 n'offre plus d'envoi groupé.
 */
class FcmClient
{
    private const TOKEN_CACHE_KEY = 'fcm.access_token';

    /** Codes renvoyés par FCM quand le token n'existe plus. */
    private const STALE_CODES = ['UNREGISTERED', 'INVALID_ARGUMENT', 'SENDER_ID_MISMATCH'];

    public function configured(): bool
    {
        $path = $this->credentialsPath();

        return $path !== null && is_readable($path);
    }

    /**
     * @param  list<string>  $tokens
     * @return array<string, FcmResult> indexé par token FCM
     */
    public function send(array $tokens, PushNotification $notification): array
    {
        if ($tokens === []) {
            return [];
        }

        $accessToken = $this->accessToken();
        $url = str_replace(':project', $this->projectId(), config('fcm.endpoints.send'));
        $payload = $this->payload($notification);
        $timeout = (int) config('fcm.timeout');

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (string $token) => $pool->as($token)
                ->withToken($accessToken)
                ->timeout($timeout)
                ->post($url, ['message' => ['token' => $token] + $payload]),
            $tokens,
        ));

        $results = [];

        foreach ($tokens as $token) {
            $results[$token] = $this->interpret($responses[$token] ?? null);
        }

        return $results;
    }

    /**
     * Le corps du message, sans le token de l'appareil.
     *
     * @return array<string, mixed>
     */
    private function payload(PushNotification $notification): array
    {
        return [
            'notification' => [
                'title' => $notification->title,
                'body' => $notification->body,
            ],
            // FCM n'accepte que des chaînes dans `data`.
            'data' => array_map('strval', array_filter([
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'news_id' => $notification->news_id,
                'event_id' => $notification->event_id,
            ], fn ($value) => $value !== null)),
            'android' => [
                'priority' => 'high',
                'notification' => ['sound' => 'default'],
            ],
            'apns' => [
                'payload' => ['aps' => ['sound' => 'default']],
            ],
        ];
    }

    private function interpret(mixed $response): FcmResult
    {
        if (! $response instanceof Response) {
            $message = $response instanceof \Throwable ? $response->getMessage() : 'Réponse FCM illisible';

            return FcmResult::failed($message);
        }

        if ($response->successful()) {
            return FcmResult::ok();
        }

        $code = (string) $response->json('error.status', '');
        $message = (string) $response->json('error.message', 'HTTP '.$response->status());

        return in_array($code, self::STALE_CODES, true)
            ? FcmResult::stale($message)
            : FcmResult::failed($message);
    }

    /**
     * Jeton OAuth du compte de service, valable une heure côté Google.
     */
    private function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(50), function (): string {
            $account = $this->serviceAccount();
            $now = time();

            $assertion = $this->signJwt([
                'iss' => $account['client_email'],
                'scope' => config('fcm.scope'),
                'aud' => config('fcm.endpoints.token'),
                'iat' => $now,
                'exp' => $now + 3600,
            ], $account['private_key']);

            $response = Http::asForm()
                ->timeout((int) config('fcm.timeout'))
                ->post(config('fcm.endpoints.token'), [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]);

            $token = $response->json('access_token');

            if (! $response->successful() || ! is_string($token)) {
                throw new RuntimeException('Jeton OAuth FCM refusé : '.$response->body());
            }

            return $token;
        });
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function signJwt(array $claims, string $privateKey): string
    {
        $encode = fn (array $part) => rtrim(strtr(base64_encode(
            json_encode($part, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ), '+/', '-_'), '=');

        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode($claims);

        if (! openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Signature du JWT FCM impossible : clé privée invalide.');
        }

        return $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    /**
     * @return array{client_email: string, private_key: string, project_id?: string}
     */
    private function serviceAccount(): array
    {
        $path = $this->credentialsPath();

        if ($path === null || ! is_readable($path)) {
            throw new RuntimeException('FCM_CREDENTIALS ne pointe sur aucun fichier lisible.');
        }

        $account = json_decode((string) file_get_contents($path), true);

        if (! is_array($account) || ! isset($account['client_email'], $account['private_key'])) {
            throw new RuntimeException('Le compte de service FCM est illisible ou incomplet.');
        }

        return $account;
    }

    private function projectId(): string
    {
        $projectId = config('fcm.project_id') ?: ($this->serviceAccount()['project_id'] ?? null);

        if (! is_string($projectId) || $projectId === '') {
            throw new RuntimeException('FCM_PROJECT_ID est absent et introuvable dans le compte de service.');
        }

        return $projectId;
    }

    private function credentialsPath(): ?string
    {
        $path = config('fcm.credentials');

        if (! is_string($path) || $path === '') {
            return null;
        }

        $resolved = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)
            ? $path
            : base_path($path);

        if (is_dir($resolved)) {
            $files = glob(rtrim($resolved, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.json') ?: [];
            sort($files);

            return $files[0] ?? null;
        }

        return $resolved;
    }
}
