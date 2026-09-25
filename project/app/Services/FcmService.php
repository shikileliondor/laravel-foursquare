<?php

namespace App\Services;

use App\Models\Device;
use App\Models\PushNotification;

class FcmService
{
    public function __construct(private readonly FcmClient $client) {}

    /**
     * Send a push notification to every active device.
     *
     * @param  array<string, mixed>  $data
     * @return array{delivered: int, pruned: int, failed: int, errors: list<string>}
     */
    public function broadcast(string $title, string $body, array $data = []): array
    {
        $tokens = Device::query()
            ->where('notifications_enabled', true)
            ->pluck('fcm_token')
            ->map(fn (mixed $token): string => (string) $token)
            ->values()
            ->all();

        return $this->sendToDevices(
            $tokens,
            $this->notification($title, $body, $data),
        );
    }

    /**
     * Send a prepared notification to explicit FCM tokens and disable stale ones.
     *
     * @param  array<array-key, string>  $tokens
     * @return array{delivered: int, pruned: int, failed: int, errors: list<string>}
     */
    public function sendToDevices(array $tokens, PushNotification $notification): array
    {
        $tokens = array_values(array_unique(array_filter(array_map('strval', $tokens))));

        $delivered = 0;
        $stale = [];
        $errors = [];
        $chunkSize = max(1, (int) config('fcm.chunk'));

        foreach (array_chunk($tokens, $chunkSize) as $chunk) {
            foreach ($this->client->send($chunk, $notification) as $token => $result) {
                match (true) {
                    $result->delivered => $delivered++,
                    $result->stale => $stale[] = $token,
                    default => $errors[] = (string) $result->error,
                };
            }
        }

        $this->disableTokens($stale);

        return [
            'delivered' => $delivered,
            'pruned' => count($stale),
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<string>  $tokens
     */
    public function disableTokens(array $tokens): void
    {
        if ($tokens === []) {
            return;
        }

        Device::whereIn('fcm_token', $tokens)->update(['notifications_enabled' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notification(string $title, string $body, array $data): PushNotification
    {
        return new PushNotification([
            'title' => $title,
            'body' => $body,
            'type' => (string) ($data['type'] ?? 'GENERAL'),
            'news_id' => $data['news_id'] ?? null,
            'event_id' => $data['event_id'] ?? null,
            'audience' => 'ALL',
        ]);
    }
}
