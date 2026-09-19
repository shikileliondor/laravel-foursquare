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
            $this->fail($notification, 'FCM non configuré : renseignez FCM_CREDENTIALS.');

            return;
        }

        $notification->update(['status' => 'PROCESSING']);

        try {
            [$delivered, $stale, $errors] = $this->deliver($fcm, $notification);
        } catch (Throwable $e) {
            $this->fail($notification, $e->getMessage());

            return;
        }

        if ($stale !== []) {
            Device::whereIn('fcm_token', $stale)->delete();
        }

        if ($delivered === 0 && $errors !== []) {
            $this->fail($notification, $errors[0]);

            return;
        }

        $notification->update([
            'status' => 'SENT',
            'sent_at' => now(),
            'last_error' => null,
        ]);

        AuditLog::record('sent', $notification, [
            'delivered' => $delivered,
            'pruned' => count($stale),
            'failed' => count($errors),
        ]);
    }

    /**
     * @return array{0: int, 1: list<string>, 2: list<string>} livrés, tokens morts, erreurs
     */
    private function deliver(FcmClient $fcm, PushNotification $notification): array
    {
        $delivered = 0;
        $stale = [];
        $errors = [];

        Device::query()
            ->forAudience($notification)
            ->select(['id', 'fcm_token'])
            ->chunkById((int) config('fcm.chunk'), function (Collection $devices) use ($fcm, $notification, &$delivered, &$stale, &$errors): void {
                $tokens = array_values(array_map('strval', $devices->pluck('fcm_token')->all()));

                foreach ($fcm->send($tokens, $notification) as $token => $result) {
                    match (true) {
                        $result->delivered => $delivered++,
                        $result->stale => $stale[] = $token,
                        default => $errors[] = (string) $result->error,
                    };
                }
            });

        return [$delivered, $stale, $errors];
    }

    private function fail(PushNotification $notification, string $error): void
    {
        $notification->update([
            'status' => 'FAILED',
            'last_error' => mb_substr($error, 0, 1000),
            'retry_count' => $notification->retry_count + 1,
        ]);
    }
}
