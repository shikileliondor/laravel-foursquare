<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Church;
use App\Models\District;
use App\Models\Event;
use App\Models\Media;
use App\Models\News;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration : développement uniquement. Hors environnement
 * local, elles ne sont chargées que sur demande explicite :
 * `php artisan db:seed --class=DemoSeeder`.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            DistrictSeeder::class,
            ChurchSeeder::class,
            MediaSeeder::class,
            EventSeeder::class,
            NewsSeeder::class,
            BannerSeeder::class,
        ]);

        $this->command->table(['Données', 'Total après seeding'], [
            ['Administrateurs', User::query()->count()],
            ['Districts', District::query()->count()],
            ['Églises', Church::query()->count()],
            ['Images', Media::query()->count()],
            ['Événements', Event::query()->count()],
            ['Actualités', News::query()->count()],
            ['Bannières', Banner::query()->count()],
        ]);
        $this->command->warn('À valider : district des 15 autres églises ; noms des districts non listés parmi les 26 ; année des affiches Semaine Spirituelle, Offrandes et Autel des Parfums.');
    }
}
