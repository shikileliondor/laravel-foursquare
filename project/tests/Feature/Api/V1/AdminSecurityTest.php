<?php

use App\Models\Church;
use App\Models\District;
use App\Models\User;

it('does not let the public create an account', function () {
    $this->post('/register', [
        'name' => 'Inconnu',
        'email' => 'inconnu@example.com',
        'password' => 'MotDePasse-Tres-Long-123',
        'password_confirmation' => 'MotDePasse-Tres-Long-123',
    ])->assertNotFound();

    expect(User::where('email', 'inconnu@example.com')->exists())->toBeFalse();
});

it('gives no admin role to a new account by default', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    expect($user->fresh()->role)->toBeNull();

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-password'])
        ->assertForbidden();

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/districts')->assertForbidden();
});

it('creates an admin from the command with a strong password only', function () {
    $this->artisan('foursquare:create-admin', ['email' => 'chef@foursquare.ci', '--name' => 'Chef', '--password' => 'faible'])
        ->assertFailed();

    expect(User::where('email', 'chef@foursquare.ci')->exists())->toBeFalse();

    $this->artisan('foursquare:create-admin', ['email' => 'chef@foursquare.ci', '--name' => 'Chef', '--password' => 'Solide-Mot2Passe'])
        ->assertSuccessful();

    expect(User::where('email', 'chef@foursquare.ci')->value('role'))->toBe('SUPER_ADMIN');
});

it('attaches a church admin to its church from the command', function () {
    $district = District::create(['name' => 'District de la Côtière']);
    $church = Church::create(['district_id' => $district->id, 'name' => 'Église Niangon Révélation']);

    $this->artisan('foursquare:create-admin', [
        'email' => 'eglise@foursquare.ci',
        '--name' => 'Secrétariat',
        '--role' => 'CHURCH_ADMIN',
        '--church' => $church->slug,
        '--password' => 'Solide-Mot2Passe',
    ])->assertSuccessful();

    $admin = User::where('email', 'eglise@foursquare.ci')->firstOrFail();

    expect($admin->church_id)->toBe($church->id)
        ->and($admin->perimeterDistrictId())->toBe($district->id);
});
