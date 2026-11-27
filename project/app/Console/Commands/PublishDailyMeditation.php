<?php

namespace App\Console\Commands;

use App\Models\DailyMeditation;
use App\Services\DailyPushPublisher;
use Illuminate\Console\Command;

class PublishDailyMeditation extends Command
{
    protected $signature = 'meditations:publish-daily';

    protected $description = 'Send today\'s national meditation notification if it has not left yet';

    public function handle(DailyPushPublisher $publisher): int
    {
        $meditation = DailyMeditation::query()->whereDate('publication_date', today())->first();

        if (! $meditation) {
            return self::SUCCESS;
        }

        $notification = $publisher->publish($meditation);

        if ($notification?->status === 'FAILED') {
            $this->components->error('La notification de la méditation n’a pas pu partir : '.$notification->last_error);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
