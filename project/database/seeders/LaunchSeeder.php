<?php

namespace Database\Seeders;

use App\Models\Church;
use App\Models\District;
use App\Models\EventCategory;
use App\Models\NewsCategory;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Données du premier lancement, sûres en production : le District de la
 * Côtière, l'église Foursquare Niangon Révélation et les catégories.
 * Rien d'autre n'est inventé ; tout reste modifiable depuis le back-office.
 * Idempotent : relancer le seeder ne crée pas de doublon et ne remplace pas
 * ce qui a été corrigé depuis le back-office.
 */
class LaunchSeeder extends Seeder
{
    /** Catégories de départ, renommables depuis le back-office. */
    private const NEWS_CATEGORIES = ['Communiqué officiel', 'Vie des églises', 'Jeunesse', 'Formation', 'Mission et évangélisation'];

    private const EVENT_CATEGORIES = ['Culte', 'Convention', 'Conférence', 'Séminaire et formation', 'Jeunesse', 'Évangélisation'];

    public function run(): void
    {
        $cotiere = District::query()->firstOrCreate(
            ['slug' => 'district-de-la-cotiere'],
            ['name' => 'District de la Côtière', 'status' => 'ACTIVE'],
        );

        $niangon = Church::query()->firstOrCreate(
            ['slug' => 'eglise-foursquare-niangon-revelation'],
            [
                'district_id' => $cotiere->id,
                'name' => 'Église Foursquare Niangon Révélation',
                'commune' => 'Yopougon',
                'quartier' => 'Niangon',
                'status' => 'ACTIVE',
            ],
        );

        foreach (self::NEWS_CATEGORIES as $order => $name) {
            NewsCategory::query()->firstOrCreate(['name' => $name], ['display_order' => $order]);
        }

        foreach (self::EVENT_CATEGORIES as $order => $name) {
            EventCategory::query()->firstOrCreate(['name' => $name], ['display_order' => $order]);
        }

        // Église proposée au premier lancement de l'application ; changeable
        // dans les paramètres sans toucher au code.
        if (Setting::read('launch_church_id') === null) {
            Setting::write('launch_church_id', $niangon->id);
        }
    }
}
