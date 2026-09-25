<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\PushNotification;
use App\Services\FcmClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Throwable;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * Un envoi qui traine ne doit pas immobiliser la file. Au-dela, la file
     * abandonne le job et `failed()` rend la notification renvoyable.
     */
    public int $timeout = 180;

    public function __construct(public readonly string $notificationId) {}

    public function handle(FcmClient $fcm): void
    {
        $notification = PushNotification::find($this->notificationId);

        // Seule une notification en attente part : évite un double envoi si le
        // job est rejoué ou si un administrateur a cliqué deux fois.
        if (! $notification || $notification->status !== 'PENDING') {
            return;
        }

        if (! $fcm->configured()) {
            $this->markFailed($notification, 'FCM non configuré : renseignez FCM_CREDENTIALS.');

            return;
        }

        $notification->update([
            'status' => 'PROCESSING',
            'delivered_count' => 0,
            'pruned_count' => 0,
            'failed_count' => 0,
        ]);

        try {
            [$delivered, $stale, $errors, $attempted] = $this->deliver($fcm, $notification);
        } catch (Throwable $e) {
            $this->markFailed($notification, $e->getMessage());

            return;
        }

        if ($stale !== []) {
            Device::whereIn('fcm_token', $stale)->delete();
        }

        // Une audience sans aucun appareil n'est pas un succès : l'intention de
        // l'administrateur n'a été satisfaite pour personne. FAILED le dit et
        // laisse la notification renvoyable une fois le parc corrigé.
        if ($attempted === 0) {
            $this->markFailed($notification, $this->emptyAudienceReason($notification));

            return;
        }

        if ($delivered === 0 && $errors !== []) {
            $this->markFailed($notification, $errors[0], count($stale), count($errors));

            return;
        }

        $notification->update([
            'status' => 'SENT',
            'sent_at' => now(),
            'last_error' => null,
            'delivered_count' => $delivered,
            'pruned_count' => count($stale),
            'failed_count' => count($errors),
        ]);

        AuditLog::record('sent', $notification, [
            'delivered' => $delivered,
            'pruned' => count($stale),
            'failed' => count($errors),
        ]);
    }

    /**
     * Appelé par la file quand le job lève, dépasse son temps ou perd son
     * worker. Sans cela, une notification passée en PROCESSING y reste pour
     * toujours : `send()` refuse ce statut et plus aucune action ne la libère.
     */
    public function failed(?Throwable $e): void
    {
        $notification = PushNotification::find($this->notificationId);

        if (! $notification || ! in_array($notification->status, ['PENDING', 'PROCESSING'], true)) {
            return;
        }

        $this->markFailed(
            $notification,
            $e?->getMessage() ?: "L'envoi a été interrompu avant la fin du traitement.",
        );
    }

    /**
     * @return array{0: int, 1: list<string>, 2: list<string>, 3: int} livrés, tokens morts, erreurs, appareils visés
     */
    private function deliver(FcmClient $fcm, PushNotification $notification): array
    {
        $delivered = 0;
        $stale = [];
        $errors = [];
        $attempted = 0;

        Device::query()
            ->forAudience($notification)
            ->select(['id', 'fcm_token'])
            ->chunkById((int) config('fcm.chunk'), function (Collection $devices) use ($fcm, $notification, &$delivered, &$stale, &$errors, &$attempted): void {
                $tokens = array_values(array_map('strval', $devices->pluck('fcm_token')->all()));
                $attempted += count($tokens);

                foreach ($fcm->send($tokens, $notification) as $token => $result) {
                    match (true) {
                        $result->delivered => $delivered++,
                        $result->stale => $stale[] = $token,
                        default => $errors[] = (string) $result->error,
                    };
                }
            });

        return [$delivered, $stale, $errors, $attempted];
    }

    /**
     * Dit quoi faire, pas seulement que c'est vide : le parc d'appareils est
     * la cause la plus fréquente d'un envoi qui ne part nulle part.
     */
    private function emptyAudienceReason(PushNotification $notification): string
    {
        return match ($notification->audience) {
            'DISTRICT' => 'Aucun appareil rattaché à ce district. L\'application doit transmettre church_id à l\'enregistrement pour que le ciblage fonctionne.',
            'ZONE' => 'Aucun appareil rattaché à cette zone. L\'application doit transmettre church_id à l\'enregistrement pour que le ciblage fonctionne.',
            'CHURCH' => 'Aucun appareil rattaché à cette église. L\'application doit transmettre church_id à l\'enregistrement pour que le ciblage fonctionne.',
            default => 'Aucun appareil enregistré avec les notifications activées. Lancez l\'application mobile pour qu\'elle transmette son token à POST /api/v1/devices.',
        };
    }

    private function markFailed(PushNotification $notification, string $error, int $pruned = 0, int $failed = 0): void
    {
        $notification->update([
            'status' => 'FAILED',
            'last_error' => mb_substr($error, 0, 1000),
            'retry_count' => $notification->retry_count + 1,
            'delivered_count' => 0,
            'pruned_count' => $pruned,
            'failed_count' => $failed,
        ]);
    }
}
