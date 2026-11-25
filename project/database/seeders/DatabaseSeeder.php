<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Le lancement (Côtière, Niangon Révélation, catégories) partout ; la
     * démonstration seulement en local, pour ne jamais présenter de fausses
     * actualités comme officielles en production.
     */
    public function run(): void
    {
        $this->call(LaunchSeeder::class);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoSeeder::class);

            return;
        }

        $this->command->info('Données de démonstration ignorées hors environnement local (php artisan db:seed --class=DemoSeeder pour les charger).');
    }
}
