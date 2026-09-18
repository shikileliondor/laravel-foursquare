<?php

namespace Database\Seeders;

use App\Models\Church;
use App\Models\District;
use App\Models\Event;
use App\Models\News;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FoursquareSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => (string) config('foursquare.admin.email')],
            [
                'name' => 'Super Admin',
                'password' => Hash::make((string) config('foursquare.admin.password')),
                'role' => 'SUPER_ADMIN',
                'status' => 'ACTIVE',
            ],
        );

        $district = District::firstOrCreate(['slug' => 'abidjan-sud'], ['name' => 'Abidjan Sud']);
        $zone = Zone::firstOrCreate(['slug' => 'yopougon'], ['name' => 'Yopougon', 'district_id' => $district->id]);

        Church::firstOrCreate(['slug' => 'yopougon-centre'], [
            'zone_id' => $zone->id,
            'name' => 'Église Yopougon Centre',
            'pastor_name' => 'Pasteur Koffi',
            'commune' => 'Yopougon',
            'quartier' => 'Niangon',
            'secretariat_phone' => '+2250100000000',
            'main_service_day' => 'Dimanche',
            'main_service_time' => '09:00',
        ]);

        News::firstOrCreate(['slug' => 'convention-nationale'], [
            'title' => 'Convention nationale',
            'excerpt' => 'La convention nationale se tiendra bientôt.',
            'content' => 'Programme complet de la convention nationale.',
            'scope_type' => 'NATIONAL',
            'priority' => 'IMPORTANT',
            'is_featured' => true,
            'status' => 'PUBLISHED',
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        Event::firstOrCreate(['slug' => 'rencontre-jeunesse'], [
            'title' => 'Rencontre jeunesse',
            'description' => 'Grande rencontre de la jeunesse Foursquare.',
            'scope_type' => 'NATIONAL',
            'start_at' => now()->addWeek(),
            'end_at' => now()->addWeek()->addHours(4),
            'venue_name' => 'Temple central',
            'commune' => 'Yopougon',
            'is_featured' => true,
            'status' => 'PUBLISHED',
            'created_by' => $admin->id,
        ]);
    }
}
