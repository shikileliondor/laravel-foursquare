<?php

use App\Models\Media;
use App\Models\News;
use App\Models\User;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'SUPER_ADMIN', 'status' => 'ACTIVE']));
});

it('creates a banner whose display order was left empty in the panel', function () {
    $media = Media::create([
        'original_name' => 'convention.jpg',
        'stored_name' => 'convention.jpg',
        'path' => 'media/convention.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 11,
    ]);

    $this->postJson('/api/v1/admin/banners', [
        'media_id' => $media->id,
        'title' => 'Convention nationale',
        'button_text' => "S'inscrire",
        'display_order' => null,
        'link_type' => 'NONE',
        'starts_at' => '2026-11-26T09:53',
        'ends_at' => null,
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.display_order', 0);
});

it('creates a category whose display order was left empty in the panel', function () {
    $this->postJson('/api/v1/admin/news-categories', ['name' => 'Jeunesse', 'display_order' => null])
        ->assertCreated()
        ->assertJsonPath('data.display_order', 0);
});

it('keeps saving and serving content when the public cache cannot be written', function () {
    Cache::extend('broken', fn () => Cache::repository(new class extends ArrayStore
    {
        public function get($key)
        {
            throw new RuntimeException('cache en panne');
        }

        public function put($key, $value, $seconds)
        {
            throw new RuntimeException('cache en panne');
        }

        public function forever($key, $value)
        {
            throw new RuntimeException('cache en panne');
        }
    }));
    config(['cache.stores.broken' => ['driver' => 'broken'], 'foursquare.public_cache.store' => 'broken']);

    $news = News::create([
        'title' => 'Avant',
        'content' => 'Contenu',
        'scope_type' => 'NATIONAL',
        'status' => 'PUBLISHED',
        'published_at' => now()->subDay(),
    ]);
    $news->update(['title' => 'Après']);

    $this->getJson('/api/v1/news')
        ->assertOk()
        ->assertHeaderMissing('X-Cache')
        ->assertJsonPath('data.0.title', 'Après');
});
