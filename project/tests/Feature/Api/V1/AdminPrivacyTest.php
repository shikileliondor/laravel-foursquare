<?php

use App\Models\District;
use App\Models\PrivacyConsentEvent;
use App\Models\PrivacyDataRequest;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function privacyRequest(array $overrides = []): PrivacyDataRequest
{
    return PrivacyDataRequest::create($overrides + [
        'reference' => 'DCP-20261126-'.Str::upper(Str::random(8)),
        'type' => 'DELETION',
        'full_name' => 'Jean Kouassi',
        'phone' => '0708091011',
        'privacy_notice_version' => '2026-11-26',
        'status' => 'RECEIVED',
    ]);
}

it('lets a national admin list and search data requests', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'NATIONAL_ADMIN', 'status' => 'ACTIVE']));
    privacyRequest();
    privacyRequest(['full_name' => 'Awa Traoré', 'phone' => '0102030405']);

    $this->getJson('/api/v1/admin/privacy-requests?search=Awa')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.full_name', 'Awa Traoré')
        ->assertJsonPath('data.0.phone', '0102030405');
});

it('closes a data request with the handling admin and a timestamp', function () {
    $admin = User::factory()->create(['role' => 'SUPER_ADMIN', 'status' => 'ACTIVE']);
    Sanctum::actingAs($admin);
    $request = privacyRequest();

    $this->patchJson("/api/v1/admin/privacy-requests/{$request->id}", [
        'status' => 'COMPLETED',
        'resolution_note' => 'Identité vérifiée, inscription supprimée.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'COMPLETED')
        ->assertJsonPath('data.handler.id', $admin->id)
        ->assertJsonPath('data.completed_at', fn (?string $at) => $at !== null);

    $this->patchJson("/api/v1/admin/privacy-requests/{$request->id}", ['status' => 'IN_PROGRESS'])
        ->assertOk()
        ->assertJsonPath('data.completed_at', null);

    $this->assertDatabaseHas('audit_logs', ['action' => 'updated', 'entity_id' => $request->id]);
});

it('never deletes consent proofs or data requests from the admin API', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'SUPER_ADMIN', 'status' => 'ACTIVE']));
    $request = privacyRequest();
    $consent = PrivacyConsentEvent::create([
        'action_id' => (string) Str::uuid(),
        'subject_id' => (string) Str::uuid(),
        'purpose' => 'PUSH_NOTIFICATIONS',
        'action' => 'GRANTED',
        'policy_version' => '2026-11-26',
        'platform' => 'android',
        'occurred_at' => now(),
    ]);

    $this->getJson('/api/v1/admin/privacy-consents')
        ->assertOk()
        ->assertJsonPath('data.0.purpose', 'PUSH_NOTIFICATIONS');

    $this->deleteJson("/api/v1/admin/privacy-consents/{$consent->id}")->assertStatus(405);
    $this->patchJson("/api/v1/admin/privacy-consents/{$consent->id}", ['action' => 'WITHDRAWN'])->assertStatus(405);
    $this->deleteJson("/api/v1/admin/privacy-requests/{$request->id}")->assertStatus(405);

    expect(PrivacyConsentEvent::query()->count())->toBe(1)
        ->and(PrivacyDataRequest::query()->count())->toBe(1);
});

it('keeps privacy screens away from district admins', function () {
    $district = District::create(['name' => 'District test', 'status' => 'ACTIVE']);
    Sanctum::actingAs(User::factory()->create([
        'role' => 'DISTRICT_ADMIN',
        'status' => 'ACTIVE',
        'district_id' => $district->id,
    ]));
    $request = privacyRequest();

    $this->getJson('/api/v1/admin/privacy-requests')->assertForbidden();
    $this->getJson('/api/v1/admin/privacy-consents')->assertForbidden();
    $this->patchJson("/api/v1/admin/privacy-requests/{$request->id}", ['status' => 'REJECTED'])->assertForbidden();
});
