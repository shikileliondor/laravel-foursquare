<?php

use App\Models\DiscoverSection;
use App\Models\District;
use App\Models\NationalLeader;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('installs the six fixed unpublished institutional sections without invented content', function () {
    expect(DiscoverSection::query()->orderBy('display_order')->pluck('key')->all())->toBe([
        'history',
        'vision-missions',
        'doctrine',
        'national-office',
        'organization',
        'districts-churches',
    ]);

    $this->getJson('/api/v1/discover')->assertOk()->assertJsonCount(0, 'data');
});

it('lets a national admin publish sections and ordered national leaders', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'SUPER_ADMIN', 'status' => 'ACTIVE']));
    $section = DiscoverSection::where('key', 'national-office')->firstOrFail();

    $this->getJson('/api/v1/admin/discover-sections')
        ->assertOk()
        ->assertJsonPath('meta.total', 6);

    $this->patchJson("/api/v1/admin/discover-sections/{$section->id}", [
        'content' => '<p>Contenu institutionnel fourni.</p>',
        'status' => 'PUBLISHED',
    ])->assertOk()->assertJsonPath('data.status', 'PUBLISHED');

    $second = $this->postJson('/api/v1/admin/national-leaders', [
        'name' => 'Responsable Deux',
        'position' => 'Fonction Deux',
        'display_order' => 2,
        'is_published' => true,
    ])->assertCreated()->json('data.id');
    $defaults = $this->postJson('/api/v1/admin/national-leaders', [
        'name' => 'Responsable non publié',
        'position' => 'Fonction',
    ])->assertCreated()
        ->assertJsonPath('data.display_order', 0)
        ->assertJsonPath('data.is_published', false)
        ->json('data.id');
    $first = $this->postJson('/api/v1/admin/national-leaders', [
        'name' => 'Responsable Un',
        'position' => 'Fonction Un',
        'display_order' => 1,
        'is_published' => true,
    ])->assertCreated()->json('data.id');

    $this->getJson('/api/v1/discover/national-office')
        ->assertOk()
        ->assertJsonPath('data.content', '<p>Contenu institutionnel fourni.</p>')
        ->assertJsonPath('data.leaders.0.id', $first)
        ->assertJsonPath('data.leaders.1.id', $second)
        ->assertJsonMissing(['id' => $defaults]);
});

it('hides drafts and unpublished leaders from visitors', function () {
    $section = DiscoverSection::where('key', 'history')->firstOrFail();
    NationalLeader::create([
        'name' => 'Responsable masqué',
        'position' => 'Fonction',
        'display_order' => 1,
        'is_published' => false,
    ]);

    $this->getJson("/api/v1/discover/{$section->key}")->assertNotFound();
});

it('keeps institutional management away from territorial administrators', function () {
    $district = District::create(['name' => 'District test', 'status' => 'ACTIVE']);
    Sanctum::actingAs(User::factory()->create([
        'role' => 'DISTRICT_ADMIN',
        'status' => 'ACTIVE',
        'district_id' => $district->id,
    ]));
    $section = DiscoverSection::where('key', 'history')->firstOrFail();

    $this->getJson('/api/v1/admin/discover-sections')->assertForbidden();
    $this->patchJson("/api/v1/admin/discover-sections/{$section->id}", [
        'status' => 'PUBLISHED',
    ])->assertForbidden();
    $this->postJson('/api/v1/admin/national-leaders', [
        'name' => 'Interdit',
        'position' => 'Interdit',
    ])->assertForbidden();
});
