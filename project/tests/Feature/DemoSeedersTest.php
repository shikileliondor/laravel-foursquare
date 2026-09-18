<?php

use App\Models\Banner;
use App\Models\Church;
use App\Models\District;
use App\Models\Event;
use App\Models\Media;
use App\Models\News;
use App\Models\Zone;
use Illuminate\Support\Facades\Storage;

it('seeds confirmed facts and images without duplicates or invented church data', function () {
    config()->set('media.disk', 'public');
    Storage::fake('public');

    $this->seed();
    $this->seed();

    expect(District::query()->count())->toBe(12)
        ->and(Zone::query()->count())->toBe(2)
        ->and(Church::query()->count())->toBe(17)
        ->and(Media::query()->count())->toBe(8)
        ->and(Event::query()->count())->toBe(3)
        ->and(News::query()->count())->toBe(4)
        ->and(Banner::query()->where('is_active', true)->count())->toBe(3);

    $niangon = Church::query()->where('slug', 'eglise-foursquare-niangon-revelation')->firstOrFail();
    $ananeraie = Church::query()->where('slug', 'eglise-foursquare-ananeraie')->firstOrFail();
    $unconfirmed = Church::query()->where('slug', 'foursquare-yopougon-sideci')->firstOrFail();

    expect($niangon->zone->slug)->toBe('zone-revelation')
        ->and($niangon->zone->district->slug)->toBe('district-de-la-cotiere')
        ->and($niangon->pastor_name)->toBeNull()
        ->and($niangon->secretariat_phone)->toBeNull()
        ->and($niangon->latitude)->toBeNull()
        ->and($ananeraie->zone_id)->toBe($niangon->zone_id)
        ->and($ananeraie->secretariat_phone)->toBe('0757014548')
        ->and($unconfirmed->zone->slug)->toBe('zone-a-confirmer')
        ->and($unconfirmed->pastor_name)->toBeNull();

    expect(Storage::disk('public')->exists('media/seed/convention.jpg'))->toBeTrue()
        ->and(Storage::disk('public')->exists('media/seed/morasha.jpg'))->toBeTrue();

    expect(Event::query()->where('slug', 'morasha-le-jet-du-manteau-2026')->firstOrFail()->start_at->year)->toBe(2026)
        ->and(Event::query()->where('title', 'Semaine Spirituelle de la Jeunesse')->exists())->toBeFalse();
});
