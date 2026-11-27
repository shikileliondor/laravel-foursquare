<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\AuditLog;
use App\Models\DailyMeditation;
use App\Models\DailyVerse;
use App\Models\PushNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Relie un contenu quotidien national (verset, méditation) à sa notification
 * push et l'envoie.
 *
 * Un contenu n'a jamais plus d'une notification. Elle part sans worker, comme
 * le bouton « Envoyer » du panel, sauf si FCM_QUEUE=true.
 */
class DailyPushPublisher
{
    /** Statuts pour lesquels un nouvel envoi ferait doublon. */
    private const BUSY = ['PENDING', 'PROCESSING', 'SENT'];

    public function alreadySent(DailyVerse|DailyMeditation $item): bool
    {
        return PushNotification::query()
            ->where($this->foreignKey($item), $item->getKey())
            ->whereIn('status', self::BUSY)
            ->exists();
    }

    /**
     * Crée ou réarme la notification du contenu puis l'envoie. Ne fait rien si
     * elle est déjà partie ou en cours d'envoi.
     */
    public function publish(DailyVerse|DailyMeditation $item): ?PushNotification
    {
        $foreignKey = $this->foreignKey($item);

        $notification = DB::transaction(function () use ($item, $foreignKey): ?PushNotification {
            $item = $item->newQuery()->whereKey($item->getKey())->lockForUpdate()->first();
            if (! $item instanceof DailyVerse && ! $item instanceof DailyMeditation) {
                return null;
            }

            $notification = PushNotification::query()->where($foreignKey, $item->getKey())->first();
            if ($notification && in_array($notification->status, self::BUSY, true)) {
                return null;
            }

            $attributes = $this->message($item) + [
                'audience' => 'ALL',
                'district_id' => null,
                'church_id' => null,
                'status' => 'PENDING',
                'last_error' => null,
            ];

            if ($notification) {
                $notification->update($attributes);
            } else {
                $notification = PushNotification::create($attributes + [$foreignKey => $item->getKey()]);
            }

            $item->update(['notification_created_at' => now()]);
            AuditLog::record('queued', $notification);

            return $notification;
        });

        if (! $notification) {
            return null;
        }

        try {
            if (config()->boolean('fcm.queue')) {
                SendPushNotification::dispatch($notification->id);
            } else {
                // L'envoi doit aller au bout même si la page du panel se ferme.
                if (function_exists('ignore_user_abort')) {
                    ignore_user_abort(true);
                }
                if (function_exists('set_time_limit')) {
                    @set_time_limit(300);
                }

                SendPushNotification::dispatchSync($notification->id);
            }
        } catch (Throwable $e) {
            report($e);
            $notification->update([
                'status' => 'FAILED',
                'last_error' => mb_substr($e->getMessage(), 0, 1000),
                'retry_count' => $notification->retry_count + 1,
            ]);
        }

        return $notification->fresh();
    }

    private function foreignKey(DailyVerse|DailyMeditation $item): string
    {
        return $item instanceof DailyVerse ? 'daily_verse_id' : 'daily_meditation_id';
    }

    /**
     * @return array{title: string, body: string, type: string}
     */
    private function message(DailyVerse|DailyMeditation $item): array
    {
        return $item instanceof DailyVerse
            ? [
                'title' => 'Verset du jour · '.$item->reference,
                'body' => Str::limit($item->text, 300),
                'type' => 'DAILY_VERSE',
            ]
            : [
                'title' => 'Méditation du jour · '.$item->title,
                'body' => $item->excerpt(300),
                'type' => 'DAILY_MEDITATION',
            ];
    }
}
