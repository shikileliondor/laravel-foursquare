<?php

namespace Database\Seeders;

use App\Models\Church;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ChurchSeeder extends Seeder
{
    public function run(): void
    {
        $revelation = Zone::query()->where('slug', 'zone-revelation')->firstOrFail();

        Church::updateOrCreate(['slug' => 'eglise-foursquare-niangon-revelation'], [
            'zone_id' => $revelation->id,
            'name' => 'Église Foursquare Niangon Révélation',
            'commune' => 'Yopougon',
            'quartier' => 'Niangon',
            'address' => 'Secteur Maroc, non loin du marché Bagnon, rue S22',
            'description' => 'Compte social : @foursquareniangonrevelation.',
            'status' => 'ACTIVE',
        ]);

        Church::updateOrCreate(['slug' => 'eglise-foursquare-ananeraie'], [
            'zone_id' => $revelation->id,
            'name' => 'Église Foursquare Ananeraie',
            'commune' => 'Yopougon',
            'quartier' => 'Ananeraie',
            'address' => 'Non loin du Carrefour Oasis',
            'secretariat_phone' => '0757014548',
            'description' => 'Temple de la Grâce.',
            'status' => 'ACTIVE',
        ]);

        $provisional = Zone::query()->where('slug', 'zone-a-confirmer')->firstOrFail();

        // Ces églises sont identifiées, mais leur zone et leur district restent à valider.
        $churches = [
            'foursquare-yopougon-sideci' => 'Foursquare Yopougon Sideci',
            'foursquare-yopougon-camp-militaire' => 'Foursquare Yopougon Camp Militaire',
            'foursquare-niangon-sud' => 'Foursquare Niangon Sud',
            'foursquare-riviera-mbadon-philadelphie' => "Foursquare Riviera M'Badon / Philadelphie",
            'foursquare-atci' => 'Foursquare ATCI',
            'foursquare-la-porte-des-cieux' => 'Foursquare La Porte des Cieux',
            'foursquare-angre-chateau' => 'Foursquare Angré Château',
            'foursquare-marcory-temple-shalom' => 'Foursquare Marcory Temple Shalom',
            'foursquare-bonoumin' => 'Foursquare Bonoumin',
            'foursquare-riviera-2-anono' => 'Foursquare Riviera 2 Anono',
            'foursquare-bouake' => 'Foursquare Bouaké',
            'foursquare-yamoussoukro' => 'Foursquare Yamoussoukro',
            'foursquare-san-pedro' => 'Foursquare San-Pédro',
            'foursquare-daloa' => 'Foursquare Daloa',
            'foursquare-korhogo' => 'Foursquare Korhogo',
        ];

        foreach ($churches as $slug => $name) {
            $existingZoneId = Church::query()->where('slug', $slug)->value('zone_id');

            Church::updateOrCreate(['slug' => $slug], [
                // Conserver tout rattachement corrigé manuellement lors d'un nouveau seeding.
                'zone_id' => $existingZoneId ?: $provisional->id,
                'name' => $name,
                'status' => 'ACTIVE',
            ]);
        }
    }
}
