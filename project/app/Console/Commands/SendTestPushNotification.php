<?php

namespace App\Console\Commands;

use App\Data\FcmResult;
use App\Models\Device;
use App\Models\PushNotification;
use App\Services\FcmClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

/**
 * Envoi de diagnostic : court-circuite la base et la file pour repondre a une
 * seule question, « le telephone recoit-il ? ». Rien n'est enregistre et aucun
 * token n'est supprime, contrairement a un envoi reel.
 */
class SendTestPushNotification extends Command
{
    protected $signature = 'fcm:test
        {--token=* : Token FCM cible. Repetable. Par defaut : les appareils enregistres}
        {--title=Test Foursquare : Titre affiche en gras sur le telephone}
        {--body=Si vous lisez ceci, les notifications fonctionnent. : Corps du message}
        {--limit=10 : Nombre maximum d appareils enregistres a joindre}
        {--force : N a pas demander confirmation avant d ecrire aux appareils reels}';

    protected $description = 'Envoie une notification de test a un token FCM ou aux appareils enregistres';

    public function handle(FcmClient $fcm): int
    {
        if (! $fcm->configured()) {
            $this->components->error('FCM non configuré.');
            $this->line('  FCM_CREDENTIALS = '.(config('fcm.credentials') ?: '(vide)'));
            $this->line('  Déposez le JSON du compte de service Firebase dans storage/app/firebase/,');
            $this->line('  puis vérifiez FCM_CREDENTIALS dans .env.');

            return self::FAILURE;
        }

        $tokens = $this->tokens();

        if ($tokens === []) {
            return self::FAILURE;
        }

        $this->components->info('Projet Firebase : '.$this->projectId($fcm));

        try {
            $results = $fcm->send($tokens, $this->notification());
        } catch (Throwable $e) {
            $this->components->error('Envoi impossible : '.$e->getMessage());

            return self::FAILURE;
        }

        return $this->report($results);
    }

    /**
     * Les tokens explicites priment : ils permettent de tester un telephone qui
     * ne s'est pas encore enregistre aupres de l'API.
     *
     * @return list<string>
     */
    private function tokens(): array
    {
        /** @var list<string> $explicit */
        $explicit = array_values(array_filter((array) $this->option('token')));

        if ($explicit !== []) {
            return $explicit;
        }

        $devices = Device::query()
            ->where('notifications_enabled', true)
            ->orderByDesc('last_seen_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->pluck('fcm_token')
            ->all();

        if ($devices === []) {
            $this->components->error('Aucun appareil enregistré.');
            $this->line('  Lancez l\'application mobile pour qu\'elle poste son token sur POST /api/v1/devices,');
            $this->line('  ou passez le token à la main : php artisan fcm:test --token=...');

            return [];
        }

        $count = count($devices);

        // Ces tokens sont de vrais telephones : on ne les sonne pas par accident.
        if (! $this->option('force') && ! $this->confirm("Écrire à {$count} appareil(s) réel(s) ?", false)) {
            $this->components->warn('Annulé.');

            return [];
        }

        return array_values(array_map('strval', $devices));
    }

    /**
     * Une notification volontairement non persistee : ce test ne doit pas
     * apparaitre dans l'historique du panel.
     */
    private function notification(): PushNotification
    {
        $notification = new PushNotification([
            'title' => (string) $this->option('title'),
            'body' => (string) $this->option('body'),
            'type' => 'GENERAL',
            'audience' => 'ALL',
        ]);

        $notification->id = (string) Str::uuid7();

        return $notification;
    }

    /**
     * @param  array<string, FcmResult>  $results
     */
    private function report(array $results): int
    {
        $delivered = 0;
        $rows = [];

        foreach ($results as $token => $result) {
            $delivered += $result->delivered ? 1 : 0;

            $rows[] = [
                Str::limit($token, 24),
                match (true) {
                    $result->delivered => '<fg=green>livrée</>',
                    $result->stale => '<fg=yellow>token mort</>',
                    default => '<fg=red>échec</>',
                },
                $result->error ?? '',
            ];
        }

        $this->table(['Token', 'Résultat', 'Détail'], $rows);

        $total = count($results);

        if ($delivered === 0) {
            $this->components->error("0 / {$total} livrée(s). Le téléphone ne recevra rien.");

            return self::FAILURE;
        }

        $this->components->info("{$delivered} / {$total} livrée(s). Regardez le téléphone.");

        return self::SUCCESS;
    }

    private function projectId(FcmClient $fcm): string
    {
        try {
            return $fcm->projectId();
        } catch (Throwable) {
            return '(indéterminé)';
        }
    }
}
