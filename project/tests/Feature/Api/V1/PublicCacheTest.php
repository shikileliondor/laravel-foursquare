<?php

use App\Models\Church;
use App\Models\District;
use App\Models\Media;
use App\Models\News;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

function cachedNews(string $title): News
{
    return News::create([
        'title' => $title,
        'content' => 'Contenu',
        'scope_type' => 'NATIONAL',
        'status' => 'PUBLISHED',
        'published_at' => now()->subDay(),
    ]);
}

it('serves repeated public reads from the cache, before the rate limiter', function () {
    cachedNews('Convention nationale');

    $this->getJson('/api/v1/news')
        ->assertOk()
        ->assertHeader('X-Cache', 'MISS')
        ->assertHeader('X-RateLimit-Limit', '300');

    $this->getJson('/api/v1/news')
        ->assertOk()
        ->assertHeader('X-Cache', 'HIT')
        ->assertHeaderMissing('X-RateLimit-Limit')
        ->assertJsonPath('data.0.title', 'Convention nationale');
});

it('shows content changes immediately despite the cache', function () {
    $news = cachedNews('Avant');

    $this->getJson('/api/v1/news')->assertJsonPath('data.0.title', 'Avant');

    $news->update(['title' => 'Après']);

    $this->getJson('/api/v1/news')
        ->assertHeader('X-Cache', 'MISS')
        ->assertJsonPath('data.0.title', 'Après');

    $news->update(['status' => 'DRAFT']);

    $this->getJson('/api/v1/news')->assertJsonCount(0, 'data');
});

it('refreshes the home page when the launch church changes', function () {
    $district = District::create(['name' => 'District de test']);
    $church = Church::create(['district_id' => $district->id, 'name' => 'Église de test']);

    $this->getJson('/api/v1/home')->assertJsonPath('data.launch_church', null);

    Setting::write('launch_church_id', $church->id);

    $this->getJson('/api/v1/home')->assertJsonPath('data.launch_church.id', $church->id);
});

it('keeps one cached answer per query and per host', function () {
    cachedNews('Première');
    cachedNews('Seconde');

    $this->getJson('/api/v1/news?per_page=1')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/news?per_page=2')->assertHeader('X-Cache', 'MISS')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/news?per_page=1')->assertHeader('X-Cache', 'HIT')->assertJsonCount(1, 'data');

    // Les URL d'images suivent l'hôte appelé (127.0.0.1, IP Wi-Fi, domaine).
    $this->getJson('http://192.168.1.20/api/v1/news?per_page=1')->assertHeader('X-Cache', 'MISS');
});

it('never caches searches or errors', function () {
    cachedNews('Convention nationale');

    $this->getJson('/api/v1/news?search=Convention')->assertOk()->assertHeaderMissing('X-Cache');
    $this->getJson('/api/v1/news/inconnue')->assertNotFound()->assertHeaderMissing('X-Cache');
});

it('serves images without rate limiting', function () {
    Storage::fake('public');
    Storage::disk('public')->put('media/test.jpg', 'image-bytes');

    $media = Media::create([
        'original_name' => 'test.jpg',
        'stored_name' => 'test.jpg',
        'path' => 'media/test.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 11,
    ]);

    $this->get("/api/v1/media/{$media->id}/file")
        ->assertOk()
        ->assertHeaderMissing('X-RateLimit-Limit');
});
