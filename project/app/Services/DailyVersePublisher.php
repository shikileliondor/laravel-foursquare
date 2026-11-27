<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\AuditLog;
use App\Models\DailyVerse;
use App\Models\PushNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Relie un verset à sa notification push nationale et l'envoie.
 *
 * Un verset n'a jamais plus d'une notification. Elle part sans worker, comme
 * le bouton « Envoyer » du panel, sauf si FCM_QUEUE=true.
 */
class DailyVersePublisher
{
    /** Statuts pour lesquels un nouvel envoi ferait doublon. */
    private const BUSY = ['PENDING', 'PROCESSING', 'SENT'];

    public function alreadySent(DailyVerse $verse): bool
    {
        return PushNotification::query()
            ->where('daily_verse_id', $verse->id)
            ->whereIn('status', self::BUSY)
            ->exists();
    }

    /**
     * Crée ou réarme la notification du verset puis l'envoie. Ne fait rien si
     * elle est déjà partie ou en cours d'envoi.
     */
    public function publish(DailyVerse $verse): ?PushNotification
    {
        $notification = DB::transaction(function () use ($verse): ?PushNotification {
            $verse = DailyVerse::query()->whereKey($verse->id)->lockForUpdate()->first();
            if (! $verse) {
                return null;
            }

            $notification = PushNotification::query()->where('daily_verse_id', $verse->id)->first();
            if ($notification && in_array($notification->status, self::BUSY, true)) {
                return null;
            }

            $attributes = [
                'title' => 'Verset du jour · '.$verse->reference,
                'body' => Str::limit($verse->text, 300),
                'type' => 'DAILY_VERSE',
                'audience' => 'ALL',
                'district_id' => null,
                'church_id' => null,
                'status' => 'PENDING',
                'last_error' => null,
            ];

            if ($notification) {
                $notification->update($attributes);
            } else {
                $notification = PushNotification::create($attributes + ['daily_verse_id' => $verse->id]);
            }

            $verse->update(['notification_created_at' => now()]);
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
}
