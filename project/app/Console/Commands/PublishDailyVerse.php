<?php

namespace App\Console\Commands;

use App\Models\DailyVerse;
use App\Services\DailyVersePublisher;
use Illuminate\Console\Command;

class PublishDailyVerse extends Command
{
    protected $signature = 'verses:publish-daily';

    protected $description = 'Send today\'s national verse notification if it has not left yet';

    public function handle(DailyVersePublisher $publisher): int
    {
        $verse = DailyVerse::query()->whereDate('publication_date', today())->first();

        if (! $verse) {
            return self::SUCCESS;
        }

        $notification = $publisher->publish($verse);

        if ($notification?->status === 'FAILED') {
            $this->components->error('La notification du verset n’a pas pu partir : '.$notification->last_error);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
