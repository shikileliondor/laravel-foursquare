<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $cotiere = District::query()->where('slug', 'district-de-la-cotiere')->firstOrFail();

        Zone::updateOrCreate(['slug' => 'zone-revelation'], [
            'district_id' => $cotiere->id,
            'name' => 'Zone Révélation',
            'status' => 'ACTIVE',
        ]);

        $provisional = District::query()->where('slug', 'rattachements-a-confirmer')->firstOrFail();

        // Cette zone technique ne représente pas une zone officielle de l'église.
        Zone::updateOrCreate(['slug' => 'zone-a-confirmer'], [
            'district_id' => $provisional->id,
            'name' => 'Zone à confirmer',
            'description' => 'Rattachements provisoires à valider avant publication comme organisation officielle.',
            'status' => 'ACTIVE',
        ]);
    }
}
