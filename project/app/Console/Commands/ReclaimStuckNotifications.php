<?php

namespace App\Console\Commands;

use App\Models\PushNotification;
use Illuminate\Console\Command;

/**
 * Libère les notifications qu'aucun job ne viendra plus terminer.
 *
 * `failed()` couvre un job qui lève ou dépasse son temps, mais pas un worker
 * tué net : la ligne reste alors en PENDING ou PROCESSING, et `send()` refuse
 * ces deux statuts. Sans ce rattrapage, la notification est perdue pour de bon.
 *
 * Le cas est courant en hébergement mutualisé, où le cron interrompt
 * `queue:work` au milieu d'un envoi.
 */
class ReclaimStuckNotifications extends Command
{
    protected $signature = 'notifications:reclaim
        {--minutes=15 : Age au-dela duquel un envoi en cours est considere perdu}
        {--dry-run : Montre ce qui serait libere sans rien modifier}';

    protected $description = 'Repasse en FAILED les notifications bloquees en PENDING ou PROCESSING';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $limite = now()->subMinutes($minutes);

        $stuck = PushNotification::query()
            ->whereIn('status', ['PENDING', 'PROCESSING'])
            ->where('updated_at', '<', $limite)
            ->get();

        if ($stuck->isEmpty()) {
            $this->components->info('Aucune notification bloquée.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->table(
            ['Titre', 'Statut', 'Immobile depuis'],
            $stuck->map(fn (PushNotification $n) => [
                $n->title,
                $n->status,
                $n->updated_at?->diffForHumans(syntax: true) ?? '?',
            ])->all(),
        );

        if ($dryRun) {
            $this->components->warn($stuck->count().' notification(s) seraient libérée(s). Aucune modification.');

            return self::SUCCESS;
        }

        foreach ($stuck as $notification) {
            $notification->update([
                'status' => 'FAILED',
                'last_error' => "L'envoi ne s'est jamais terminé (bloqué en {$notification->status} plus de {$minutes} min). "
                    .'Le worker a probablement été interrompu. Relancez l\'envoi.',
                'retry_count' => $notification->retry_count + 1,
            ]);
        }

        $this->components->info($stuck->count().' notification(s) libérée(s) : elles peuvent être renvoyées.');

        return self::SUCCESS;
    }
}
