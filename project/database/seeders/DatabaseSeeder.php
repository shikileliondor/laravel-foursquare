<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Church;
use App\Models\District;
use App\Models\Event;
use App\Models\Media;
use App\Models\News;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DistrictSeeder::class,
            ZoneSeeder::class,
            ChurchSeeder::class,
            MediaSeeder::class,
            EventSeeder::class,
            NewsSeeder::class,
            BannerSeeder::class,
        ]);

        $this->command->table(['Données', 'Total après seeding'], [
            ['Districts', District::query()->count()],
            ['Zones', Zone::query()->count()],
            ['Églises', Church::query()->count()],
            ['Images', Media::query()->count()],
            ['Événements', Event::query()->count()],
            ['Actualités', News::query()->count()],
            ['Bannières', Banner::query()->count()],
        ]);
        $this->command->warn('À valider : district et zone des 15 autres églises ; noms des districts non listés parmi les 26 ; année des affiches Semaine Spirituelle, Offrandes et Autel des Parfums.');
    }
}
