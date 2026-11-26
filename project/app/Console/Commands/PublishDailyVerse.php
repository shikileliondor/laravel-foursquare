<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\DailyVerse;
use App\Models\PushNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class PublishDailyVerse extends Command
{
    protected $signature = 'verses:publish-daily';

    protected $description = 'Create and send today\'s national verse notification once';

    public function handle(): int
    {
        $result = DB::transaction(function (): ?array {
            $verse = DailyVerse::query()->whereDate('publication_date', today())->lockForUpdate()->first();

            if (! $verse || $verse->notification_created_at !== null) {
                return null;
            }

            $notification = PushNotification::query()->firstOrCreate(
                ['daily_verse_id' => $verse->id],
                [
                    'title' => 'Verset du jour',
                    'body' => $verse->reference.' — '.$verse->bible_version,
                    'type' => 'DAILY_VERSE',
                    'audience' => 'ALL',
                    'status' => 'PENDING',
                ],
            );

            if (! $notification->wasRecentlyCreated) {
                $notification->update(['status' => 'PENDING', 'last_error' => null]);
            }

            $verse->update(['notification_created_at' => now()]);

            return [$verse->id, $notification];
        });

        if (! $result) {
            return self::SUCCESS;
        }

        [$verseId, $notification] = $result;

        try {
            config()->boolean('fcm.queue')
                ? SendPushNotification::dispatch($notification->id)
                : SendPushNotification::dispatchSync($notification->id);
        } catch (Throwable $e) {
            DB::transaction(function () use ($verseId, $notification, $e): void {
                DailyVerse::query()->whereKey($verseId)->update(['notification_created_at' => null]);
                $notification->update([
                    'status' => 'FAILED',
                    'last_error' => mb_substr($e->getMessage(), 0, 1000),
                    'retry_count' => $notification->retry_count + 1,
                ]);
            });

            report($e);
            $this->components->error('La notification du verset n’a pas pu être mise en file. Une prochaine exécution réessaiera.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
