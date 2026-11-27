<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('exposes the national donation link on the home screen', function () {
    $this->getJson('/api/v1/home')
        ->assertOk()
        ->assertJsonPath('data.donation_url', 'https://pay.wave.com/m/M_ci_XF7Fts2YKKpD/c/ci/');
});

it('lets the super admin change or hide the donation link', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'SUPER_ADMIN', 'status' => 'ACTIVE']));

    $this->putJson('/api/v1/admin/settings', ['donation_url' => 'http://pas-securise.example'])
        ->assertUnprocessable();

    $this->putJson('/api/v1/admin/settings', ['donation_url' => null])
        ->assertOk()
        ->assertJsonPath('data.donation_url', null);

    $this->getJson('/api/v1/home')->assertOk()->assertJsonPath('data.donation_url', null);
});
