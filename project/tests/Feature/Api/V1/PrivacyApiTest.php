<?php

use App\Models\PrivacyConsentEvent;
use App\Models\PrivacyDataRequest;
use Illuminate\Support\Str;

it('records an explicit privacy consent without exposing the subject identifier', function () {
    $payload = [
        'action_id' => (string) Str::uuid(),
        'subject_id' => (string) Str::uuid(),
        'purpose' => 'PUSH_NOTIFICATIONS',
        'policy_version' => '2026-11-26',
        'platform' => 'android',
    ];

    $this->postJson('/api/v1/privacy/consents', $payload)
        ->assertCreated()
        ->assertJsonPath('data.action_id', $payload['action_id'])
        ->assertJsonPath('data.action', 'GRANTED')
        ->assertJsonMissingPath('data.subject_id');

    $this->assertDatabaseHas('privacy_consent_events', [
        'action_id' => $payload['action_id'],
        'subject_id' => $payload['subject_id'],
        'action' => 'GRANTED',
    ]);
});

it('records a withdrawal even when the original grant is unavailable', function () {
    $payload = [
        'action_id' => (string) Str::uuid(),
        'subject_id' => (string) Str::uuid(),
        'purpose' => 'CHURCH_PERSONALIZATION',
        'policy_version' => '2026-11-26',
        'platform' => 'ios',
    ];

    $this->postJson('/api/v1/privacy/consents/withdrawals', $payload)
        ->assertCreated()
        ->assertJsonPath('data.action', 'WITHDRAWN');

    expect(PrivacyConsentEvent::query()->firstOrFail()->action)->toBe('WITHDRAWN');
});

it('makes consent recording idempotent with the client action identifier', function () {
    $payload = [
        'action_id' => (string) Str::uuid(),
        'subject_id' => (string) Str::uuid(),
        'purpose' => 'PUSH_NOTIFICATIONS',
        'policy_version' => '2026-11-26',
        'platform' => 'android',
    ];

    $this->postJson('/api/v1/privacy/consents', $payload)->assertCreated();
    $this->postJson('/api/v1/privacy/consents', $payload)->assertOk();

    expect(PrivacyConsentEvent::query()->count())->toBe(1);
});

it('rejects unknown consent purposes and honeypot submissions', function () {
    $payload = [
        'action_id' => (string) Str::uuid(),
        'subject_id' => (string) Str::uuid(),
        'purpose' => 'ADVERTISING',
        'policy_version' => '2026-11-26',
        'platform' => 'android',
        'website' => 'https://spam.example',
    ];

    $this->postJson('/api/v1/privacy/consents', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.fields.purpose.0', fn (string $message) => $message !== '')
        ->assertJsonPath('error.fields.website.0', fn (string $message) => $message !== '');
});

it('creates a data rights request and only returns a tracking reference', function () {
    $this->postJson('/api/v1/privacy/data-requests', [
        'type' => 'DELETION',
        'full_name' => 'Jean Kouassi',
        'phone' => '07 08 09 10 11',
        'details' => 'Inscription au rassemblement national.',
        'privacy_notice_version' => '2026-11-26',
    ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'DELETION')
        ->assertJsonPath('data.status', 'RECEIVED')
        ->assertJsonStructure(['data' => ['reference', 'submitted_at']])
        ->assertJsonMissingPath('data.phone')
        ->assertJsonMissingPath('data.email');

    $stored = PrivacyDataRequest::query()->firstOrFail();

    expect($stored->reference)->toStartWith('DCP-')
        ->and($stored->phone)->toBe('0708091011');
});

it('requires at least one contact channel for a data request', function () {
    $this->postJson('/api/v1/privacy/data-requests', [
        'type' => 'ACCESS',
        'full_name' => 'Jean Kouassi',
        'privacy_notice_version' => '2026-11-26',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.fields.phone.0', fn (string $message) => $message !== '')
        ->assertJsonPath('error.fields.email.0', fn (string $message) => $message !== '');
});
