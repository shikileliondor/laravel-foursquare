<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            'district-de-la-cotiere' => 'District de la Côtière',
            'district-abidjan-1' => 'District Abidjan 1',
            'district-abidjan-2' => 'District Abidjan 2',
            'district-abidjan-3' => 'District Abidjan 3',
            'district-abidjan-4' => 'District Abidjan 4',
            'district-bouake' => 'District Bouaké',
            'district-yamoussoukro' => 'District Yamoussoukro',
            'district-san-pedro' => 'District San-Pédro',
            'district-daloa-1' => 'District Daloa 1',
            'district-daloa-2' => 'District Daloa 2',
            'district-korhogo' => 'District Korhogo',
        ];

        foreach ($districts as $slug => $name) {
            District::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'status' => 'ACTIVE',
            ]);
        }

        // Regroupement technique pour les églises dont le district réel reste à valider.
        District::updateOrCreate(['slug' => 'rattachements-a-confirmer'], [
            'name' => 'Rattachements à confirmer',
            'description' => 'Regroupement provisoire de démonstration ; aucun rattachement officiel implicite.',
            'status' => 'ACTIVE',
        ]);
    }
}
