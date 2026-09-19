<?php

use App\Models\Church;
use App\Models\Device;
use App\Models\District;
use App\Models\Event;
use App\Models\Media;
use App\Models\News;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Support\Facades\Storage;

function structure(): array
{
    $district = District::create(['name' => 'Abidjan Sud']);
    $zone = Zone::create(['district_id' => $district->id, 'name' => 'Yopougon']);
    $church = Church::create([
        'zone_id' => $zone->id,
        'name' => 'Église Yopougon Centre',
        'pastor_name' => 'Pasteur Koffi',
        'commune' => 'Yopougon',
    ]);

    return [$district, $zone, $church];
}

function publishedNews(array $attributes = []): News
{
    return News::create(array_merge([
        'title' => 'Convention nationale',
        'content' => 'Contenu de la convention',
        'scope_type' => 'NATIONAL',
        'status' => 'PUBLISHED',
        'published_at' => now()->subDay(),
    ], $attributes));
}

function publishedEvent(array $attributes = []): Event
{
    return Event::create(array_merge([
        'title' => 'Rencontre jeunesse',
        'description' => 'Grande rencontre',
        'scope_type' => 'NATIONAL',
        'status' => 'PUBLISHED',
        'start_at' => now()->addWeek(),
        'end_at' => now()->addWeek()->addHours(3),
    ], $attributes));
}

it('returns the home payload in one call', function () {
    publishedNews([
        'title' => 'Convention nationale',
        'slug' => 'convention-nationale',
        'is_featured' => true,
        'published_at' => now()->subDays(2),
    ]);
    publishedNews([
        'title' => 'Convocation jeunesse',
        'slug' => 'convocation-jeunesse',
        'is_featured' => true,
        'published_at' => now()->subDay(),
    ]);
    publishedNews([
        'title' => 'Actualite simple',
        'slug' => 'actualite-simple',
        'is_featured' => false,
    ]);
    publishedEvent(['is_featured' => true]);

    $this->getJson('/api/v1/home')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => ['banners', 'featured_news', 'featured_news_item', 'featured_event', 'latest_news', 'upcoming_events'],
        ])
        ->assertJsonCount(2, 'data.featured_news')
        ->assertJsonPath('data.featured_news.0.title', 'Convocation jeunesse')
        ->assertJsonPath('data.featured_news.1.title', 'Convention nationale')
        ->assertJsonPath('data.featured_news_item.title', 'Convocation jeunesse')
        ->assertJsonPath('data.featured_event.time_status', 'UPCOMING');
});

it('lists published news with pagination meta', function () {
    publishedNews();
    publishedNews(['title' => 'Brouillon', 'status' => 'DRAFT']);

    $this->getJson('/api/v1/news')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 1);
});

it('serves media files through the public api', function () {
    Storage::fake('public');
    Storage::disk('public')->put('media/test.jpg', 'image-bytes');

    $media = Media::create([
        'original_name' => 'test.jpg',
        'stored_name' => 'test.jpg',
        'path' => 'media/test.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 11,
    ]);

    expect($media->url())->toContain("/api/v1/media/{$media->id}/file");

    $this->get("/api/v1/media/{$media->id}/file")
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg')
        ->assertSee('image-bytes');
});

it('caps pagination at 100 per page', function () {
    publishedNews();

    $this->getJson('/api/v1/news?per_page=500')->assertJsonPath('meta.per_page', 100);
});

it('searches news', function () {
    publishedNews();
    publishedNews(['title' => 'Autre sujet', 'slug' => 'autre-sujet', 'content' => 'rien']);

    $this->getJson('/api/v1/news?search=convention')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('shows a news item by slug', function () {
    $news = publishedNews();

    $this->getJson("/api/v1/news/{$news->slug}")
        ->assertOk()
        ->assertJsonPath('data.content', 'Contenu de la convention');
});

it('returns a typed error when the news is missing', function () {
    $this->getJson('/api/v1/news/inconnu')
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NEWS_NOT_FOUND');
});

it('lists events and filters by computed status', function () {
    publishedEvent();
    publishedEvent([
        'title' => 'Ancien événement',
        'start_at' => now()->subMonth(),
        'end_at' => now()->subMonth()->addHours(2),
    ]);

    $this->getJson('/api/v1/events?status=upcoming')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.time_status', 'UPCOMING');
});

it('shows an event by slug', function () {
    $event = publishedEvent();

    $this->getJson("/api/v1/events/{$event->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $event->slug);
});

it('lists districts', function () {
    structure();

    $this->getJson('/api/v1/districts')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.zones_count', 1);
});

it('filters zones by district', function () {
    [$district] = structure();
    $other = District::create(['name' => 'Abidjan Nord']);
    Zone::create(['district_id' => $other->id, 'name' => 'Abobo']);

    $this->getJson("/api/v1/zones?district_id={$district->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Yopougon');
});

it('searches churches by commune and zone', function () {
    structure();

    $this->getJson('/api/v1/churches?search=yopougon')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/churches?search=introuvable')->assertOk()->assertJsonCount(0, 'data');
});

it('shows a church without leaking a personal number', function () {
    [, , $church] = structure();

    $this->getJson("/api/v1/churches/{$church->slug}")
        ->assertOk()
        ->assertJsonPath('data.pastor_name', 'Pasteur Koffi')
        ->assertJsonMissingPath('data.pastor_phone');
});

it('registers a device once per token', function () {
    $payload = ['fcm_token' => 'token-123', 'platform' => 'android', 'app_version' => '1.0.0'];

    $this->postJson('/api/v1/devices', $payload)->assertCreated();
    $this->postJson('/api/v1/devices', $payload)->assertOk();

    expect(Device::count())->toBe(1);
});

it('rejects an unknown device platform', function () {
    $this->postJson('/api/v1/devices', ['fcm_token' => 'x', 'platform' => 'symbian'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('never exposes the fcm token', function () {
    $this->postJson('/api/v1/devices', ['fcm_token' => 'secret-token', 'platform' => 'ios'])
        ->assertCreated()
        ->assertJsonMissingPath('data.fcm_token');
});

it('blocks admin routes without a token', function () {
    $this->getJson('/api/v1/admin/news')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHENTICATED');
});

it('blocks suspended admins', function () {
    $user = User::factory()->create(['role' => 'SUPER_ADMIN', 'status' => 'SUSPENDED']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/news')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'ACCOUNT_SUSPENDED');
});
