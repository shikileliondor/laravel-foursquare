<?php

use App\Console\Commands\PublishDailyVerse;
use App\Console\Commands\ReclaimStuckNotifications;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

// Une notification dont le worker est mort reste bloquee et devient
// irrecuperable depuis le panel : on la libere pour qu'elle soit renvoyable.
Schedule::command(ReclaimStuckNotifications::class)
    ->everyFifteenMinutes()
    ->description('Release push notifications stuck in PENDING or PROCESSING');

Schedule::command(PublishDailyVerse::class)
    ->dailyAt('07:00')
    ->timezone('Africa/Abidjan')
    ->withoutOverlapping()
    ->description('Publish the daily national verse notification');
