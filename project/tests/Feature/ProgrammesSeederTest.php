<?php

use App\Models\Church;
use App\Models\Event;
use App\Models\Media;
use App\Models\News;
use Database\Seeders\ProgrammesSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

afterEach(function () {
    Carbon::setTestNow();
});

it('imports every programme poster once, with their events and news', function () {
    Storage::fake('public');

    $this->seed(ProgrammesSeeder::class);

    expect(Media::count())->toBe(14)
        ->and(Event::count())->toBe(7)
        ->and(News::count())->toBe(5);

    Storage::disk('public')->assertExists('media/seed/programmes/programme-autel-des-parfums-novembre-2026.jpeg');
    Storage::disk('public')->assertExists('media/seed/morasha.jpg');

    $panafricaine = Event::where('slug', 'convocation-panafricaine-2026')->sole();
    expect($panafricaine->title)->toBe('Convention Panafricaine 2026')
        ->and($panafricaine->cover?->path)->toBe('media/seed/asok.jpg')
        ->and($panafricaine->scope_type)->toBe('NATIONAL');

    $autel = Event::where('slug', 'autel-des-parfums-novembre-2026')->sole();
    expect($autel->church?->slug)->toBe('eglise-foursquare-niangon-revelation');

    expect(Church::where('slug', 'eglise-foursquare-niangon-revelation')->value('main_service_day'))->toBe('Dimanche');

    $this->seed(ProgrammesSeeder::class);

    expect(Media::count())->toBe(14)
        ->and(Event::count())->toBe(7)
        ->and(News::count())->toBe(5);
});

it('enriches untouched older records but never overwrites panel edits', function () {
    Storage::fake('public');
    Carbon::setTestNow('2026-09-01 10:00:00');

    $this->seed(ProgrammesSeeder::class);

    // Ancienne fiche jamais modifiée (laissée par le seeder de démonstration).
    $old = Event::where('slug', 'convocation-panafricaine-2026')->sole();
    $old->timestamps = false;
    $old->forceFill(['title' => 'Convocation Panafricaine 2026', 'contact_phone' => null])->save();

    // Fiche corrigée plus tard dans le panel.
    Carbon::setTestNow('2026-09-02 10:00:00');
    Event::where('slug', 'morasha-le-jet-du-manteau-2026')->sole()->update(['title' => 'Titre corrigé dans le panel']);
    Church::where('slug', 'eglise-foursquare-niangon-revelation')->sole()->update(['main_service_time' => '07h30']);

    $this->seed(ProgrammesSeeder::class);

    expect($old->fresh()->title)->toBe('Convention Panafricaine 2026')
        ->and($old->fresh()->contact_phone)->toBe('0707853699 / 0747291413')
        ->and(Event::where('slug', 'morasha-le-jet-du-manteau-2026')->value('title'))->toBe('Titre corrigé dans le panel')
        ->and(Church::where('slug', 'eglise-foursquare-niangon-revelation')->value('main_service_time'))->toBe('07h30');
});
