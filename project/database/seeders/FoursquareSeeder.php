<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Compatibilité avec l'ancienne commande --class=FoursquareSeeder.
 */
class FoursquareSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
    }
}
