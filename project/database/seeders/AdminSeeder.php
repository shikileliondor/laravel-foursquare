<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Le compte SUPER_ADMIN utilisé par le panel `/admin/`, pris dans
 * config/foursquare.php (ADMIN_EMAIL / ADMIN_PASSWORD).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('foursquare.admin.email');
        $password = (string) config('foursquare.admin.password');

        if ($email === '' || $password === '') {
            $this->command->warn('ADMIN_EMAIL / ADMIN_PASSWORD absents : aucun administrateur créé.');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrateur',
                'password' => $password,
                'role' => 'SUPER_ADMIN',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
            ],
        );
    }
}
