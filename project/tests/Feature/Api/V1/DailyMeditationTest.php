<?php

use App\Jobs\SendPushNotification;
use App\Models\DailyMeditation;
use App\Models\District;
use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Carbon::setTestNow('2026-11-27 08:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function meditationOn(string $date, array $attributes = []): DailyMeditation
{
    return DailyMeditation::create(array_merge([
        'title' => 'Titre fourni',
        'content' => '<p>Contenu fourni par l’administrateur.</p>',
        'author' => 'Auteur fourni',
        'scripture_reference' => 'Référence fournie',
        'publication_date' => $date,
    ], $attributes));
}

it('lets national administrators schedule meditations, sanitized and one per day', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'NATIONAL_ADMIN', 'status' => 'ACTIVE']));

    $payload = [
        'title' => 'Titre fourni',
        'content' => '<p>Contenu fourni.</p><script>alert(1)</script>',
        'author' => 'Auteur fourni',
        'scripture_reference' => 'Référence fournie',
        'publication_date' => '2026-11-28',
    ];

    $this->postJson('/api/v1/admin/meditations', $payload)
        ->assertCreated()
        ->assertJsonPath('data.title', 'Titre fourni')
        ->assertJsonPath('data.content', fn (string $html) => str_starts_with($html, '<p>Contenu fourni.</p>') && ! str_contains($html, '<script'))
        ->assertJsonPath('data.notification', null);

    $this->postJson('/api/v1/admin/meditations', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('error.fields.publication_date.0', 'Une méditation est déjà programmée à cette date.');

    expect(PushNotification::count())->toBe(0);
});

it('keeps meditation management away from territorial administrators', function () {
    $district = District::create(['name' => 'District test', 'status' => 'ACTIVE']);
    Sanctum::actingAs(User::factory()->create([
        'role' => 'DISTRICT_ADMIN',
        'status' => 'ACTIVE',
        'district_id' => $district->id,
    ]));

    $this->getJson('/api/v1/admin/meditations')->assertForbidden();
    $this->postJson('/api/v1/admin/meditations', [
        'title' => 'Titre',
        'content' => 'Contenu',
        'publication_date' => '2026-11-27',
    ])->assertForbidden();
});

it('serves the same meditation to everyone and hides scheduled ones', function () {
    meditationOn('2026-11-26', ['title' => 'Méditation précédente']);
    $today = meditationOn('2026-11-27', ['title' => 'Méditation du jour']);
    $future = meditationOn('2026-11-28', ['title' => 'Méditation future']);

    $this->getJson('/api/v1/meditations/today')
        ->assertOk()
        ->assertJsonPath('data.id', $today->id)
        ->assertJsonPath('data.content', '<p>Contenu fourni par l’administrateur.</p>')
        ->assertJsonMissingPath('data.notification');

    $this->getJson('/api/v1/meditations?per_page=20')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.excerpt', 'Contenu fourni par l’administrateur.')
        ->assertJsonMissingPath('data.0.content')
        ->assertJsonMissing(['title' => 'Méditation future']);

    $this->getJson("/api/v1/meditations/{$today->id}")->assertOk()->assertJsonPath('data.title', 'Méditation du jour');
    $this->getJson("/api/v1/meditations/{$future->id}")->assertNotFound();

    $this->getJson('/api/v1/home')
        ->assertOk()
        ->assertJsonPath('data.daily_meditation.id', $today->id)
        ->assertJsonMissingPath('data.daily_meditation.content');
});

it('queues the daily meditation notification only once', function () {
    config()->set('fcm.queue', true);
    Bus::fake();
    $meditation = meditationOn('2026-11-27');

    $this->artisan('meditations:publish-daily')->assertSuccessful();
    $this->artisan('meditations:publish-daily')->assertSuccessful();

    $notification = PushNotification::query()->where('daily_meditation_id', $meditation->id)->sole();
    expect($notification->type)->toBe('DAILY_MEDITATION')
        ->and($notification->title)->toBe('Méditation du jour · Titre fourni')
        ->and($notification->body)->toBe('Contenu fourni par l’administrateur.')
        ->and($meditation->fresh()->notification_created_at)->not->toBeNull();
    Bus::assertDispatchedTimes(SendPushNotification::class, 1);
});

it('schedules the daily meditation notification at 07:00 in Abidjan', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'meditations:publish-daily'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 7 * * *')
        ->and($event->timezone)->toBe('Africa/Abidjan');
});
