<?php

use App\Models\District;
use App\Models\News;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@foursquare.ci',
        'password' => Hash::make('secret-password'),
        'role' => 'SUPER_ADMIN',
        'status' => 'ACTIVE',
    ]);
});

it('logs an admin in and returns a token', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@foursquare.ci',
        'password' => 'secret-password',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.email', 'admin@foursquare.ci')
        ->assertJsonStructure(['data' => ['user', 'token']]);

    expect($this->admin->fresh()->last_login_at)->not->toBeNull();
});

it('rejects bad credentials', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@foursquare.ci',
        'password' => 'wrong',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
});

it('returns the authenticated admin and revokes the token on logout', function () {
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@foursquare.ci',
        'password' => 'secret-password',
    ])->json('data.token');

    $headers = ['Authorization' => "Bearer {$token}"];

    $this->getJson('/api/v1/auth/me', $headers)->assertOk()->assertJsonPath('data.role', 'SUPER_ADMIN');
    $this->postJson('/api/v1/auth/logout', [], $headers)->assertOk();

    expect($this->admin->tokens()->count())->toBe(0);

    // The guard caches the resolved user for the lifetime of the test app.
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/v1/auth/me', $headers)->assertUnauthorized();
});

it('creates news and sanitizes dangerous html', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->postJson('/api/v1/admin/news', [
            'title' => 'Convention',
            'content' => '<p>Bonjour</p><script>alert(1)</script><iframe src="x"></iframe>',
            'scope_type' => 'NATIONAL',
            'status' => 'PUBLISHED',
        ])
        ->assertCreated();

    $news = News::first();

    expect($news->content)->toBe('<p>Bonjour</p>alert(1)')
        ->and($news->published_at)->not->toBeNull()
        ->and($news->created_by)->toBe($this->admin->id)
        ->and($news->slug)->toBe('convention');
});

it('validates news scope targets', function () {
    $district = District::create(['name' => 'Abidjan Sud']);

    $this->actingAs($this->admin, 'sanctum')
        ->postJson('/api/v1/admin/news', [
            'title' => 'Portée nationale',
            'content' => 'Texte',
            'scope_type' => 'NATIONAL',
            'district_id' => $district->id,
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.fields.district_id.0', "Le champ district_id n'est pas autorisé pour ce scope.");

    $this->actingAs($this->admin, 'sanctum')
        ->postJson('/api/v1/admin/news', [
            'title' => 'Portée district',
            'content' => 'Texte',
            'scope_type' => 'DISTRICT',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.fields.district_id.0', 'Le champ district_id est requis pour ce scope.');
});

it('requires a title and content on news creation', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->postJson('/api/v1/admin/news', ['scope_type' => 'NATIONAL'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['fields' => ['title', 'content']]]);
});

it('rejects an event ending before it starts', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->postJson('/api/v1/admin/events', [
            'title' => 'Rencontre',
            'description' => 'Texte',
            'scope_type' => 'NATIONAL',
            'start_at' => now()->addWeek()->toIso8601String(),
            'end_at' => now()->addDay()->toIso8601String(),
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['error' => ['fields' => ['end_at']]]);
});

it('runs the district crud', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->postJson('/api/v1/admin/districts', ['name' => 'Abidjan Sud'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'abidjan-sud');

    $id = $response->json('data.id');

    $this->actingAs($this->admin, 'sanctum')
        ->patchJson("/api/v1/admin/districts/{$id}", ['status' => 'INACTIVE'])
        ->assertOk()
        ->assertJsonPath('data.status', 'INACTIVE');

    // Inactive structures disappear from the public API but still exist.
    $this->getJson('/api/v1/districts')->assertJsonCount(0, 'data');

    $this->actingAs($this->admin, 'sanctum')
        ->deleteJson("/api/v1/admin/districts/{$id}")
        ->assertOk();

    expect(District::count())->toBe(0);
});

it('creates a zone attached to a district', function () {
    $district = District::create(['name' => 'Abidjan Sud']);

    $this->actingAs($this->admin, 'sanctum')
        ->postJson('/api/v1/admin/zones', ['district_id' => $district->id, 'name' => 'Yopougon'])
        ->assertCreated();

    expect(Zone::first()->district->name)->toBe('Abidjan Sud');
});

it('returns a typed 404 for a missing admin record', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/districts/'.fake()->uuid())
        ->assertNotFound()
        ->assertJsonPath('error.code', 'DISTRICT_NOT_FOUND');
});
