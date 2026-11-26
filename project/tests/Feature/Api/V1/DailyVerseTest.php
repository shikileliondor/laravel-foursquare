<?php

use App\Jobs\SendPushNotification;
use App\Models\DailyVerse;
use App\Models\District;
use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Carbon::setTestNow('2026-11-26 08:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('lets national administrators schedule verses and rejects duplicate publication dates', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'NATIONAL_ADMIN', 'status' => 'ACTIVE']));

    $payload = [
        'text' => 'Texte fourni par l’administrateur.',
        'reference' => 'Référence 1',
        'bible_version' => 'Version fournie',
        'publication_date' => '2026-11-27',
    ];

    $this->postJson('/api/v1/admin/verses', $payload)
        ->assertCreated()
        ->assertJsonPath('data.reference', 'Référence 1')
        ->assertJsonPath('data.publication_date', '2026-11-27');

    $this->postJson('/api/v1/admin/verses', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('error.fields.publication_date.0', 'Un verset est déjà programmé à cette date.');
});

it('keeps verse management away from territorial administrators', function () {
    $district = District::create(['name' => 'District test', 'status' => 'ACTIVE']);
    Sanctum::actingAs(User::factory()->create([
        'role' => 'DISTRICT_ADMIN',
        'status' => 'ACTIVE',
        'district_id' => $district->id,
    ]));

    $this->getJson('/api/v1/admin/verses')->assertForbidden();
    $this->postJson('/api/v1/admin/verses', [
        'text' => 'Texte',
        'reference' => 'Ref',
        'bible_version' => 'Version',
        'publication_date' => '2026-11-26',
    ])->assertForbidden();
});

it('serves the same current verse to everyone and excludes future verses from history', function () {
    DailyVerse::create([
        'text' => 'Verset précédent fourni.',
        'reference' => 'Ref précédente',
        'bible_version' => 'Version',
        'publication_date' => '2026-11-25',
    ]);
    $today = DailyVerse::create([
        'text' => 'Verset du jour fourni.',
        'reference' => 'Ref du jour',
        'bible_version' => 'Version',
        'publication_date' => '2026-11-26',
    ]);
    DailyVerse::create([
        'text' => 'Verset futur fourni.',
        'reference' => 'Ref future',
        'bible_version' => 'Version',
        'publication_date' => '2026-11-27',
    ]);

    $this->getJson('/api/v1/verses/today')
        ->assertOk()
        ->assertJsonPath('data.id', $today->id)
        ->assertJsonMissingPath('data.notification_created_at');

    $this->getJson('/api/v1/verses?per_page=20')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissing(['reference' => 'Ref future']);

    $this->getJson('/api/v1/home')
        ->assertOk()
        ->assertJsonPath('data.daily_verse.id', $today->id);
});

it('queues the daily verse notification only once', function () {
    config()->set('fcm.queue', true);
    Bus::fake();
    $verse = DailyVerse::create([
        'text' => 'Verset du jour fourni.',
        'reference' => 'Ref du jour',
        'bible_version' => 'Version',
        'publication_date' => '2026-11-26',
    ]);

    $this->artisan('verses:publish-daily')->assertSuccessful();
    $this->artisan('verses:publish-daily')->assertSuccessful();

    expect(PushNotification::query()->where('daily_verse_id', $verse->id)->count())->toBe(1)
        ->and($verse->fresh()->notification_created_at)->not->toBeNull();
    Bus::assertDispatchedTimes(SendPushNotification::class, 1);
});

it('schedules the daily notification at 07:00 in Abidjan', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'verses:publish-daily'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 7 * * *')
        ->and($event->timezone)->toBe('Africa/Abidjan');
});
