<?php

use App\Models\Banner;
use App\Models\Media;
use App\Models\News;
use App\Models\PushNotification;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Covers what the admin panel (public/admin/index.html) relies on: the media
 * ids it needs to prefill its image pickers, and sending an explicit null for
 * every conditional target it hides.
 */
beforeEach(function () {
    Sanctum::actingAs(User::factory()->create([
        'role' => 'SUPER_ADMIN',
        'status' => 'ACTIVE',
    ]));

    $this->media = Media::create([
        'original_name' => 'banner.jpg',
        'stored_name' => 'banner.jpg',
        'path' => 'media/banner.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1024,
    ]);
});

function makeNews(): News
{
    return News::create([
        'title' => 'Culte de rentrée',
        'content' => '<p>Bienvenue</p>',
        'scope_type' => 'NATIONAL',
        'status' => 'PUBLISHED',
    ]);
}

it('exposes the cover id so the panel can prefill its image picker', function () {
    $news = makeNews();
    $news->update(['cover_media_id' => $this->media->id]);

    $this->getJson("/api/v1/admin/news/{$news->id}")
        ->assertOk()
        ->assertJsonPath('data.cover_media_id', $this->media->id)
        ->assertJsonPath('data.cover.url', $this->media->url());
});

it('creates a banner when the unused link targets are sent as null', function () {
    $this->postJson('/api/v1/admin/banners', [
        'title' => 'Convention 2026',
        'subtitle' => 'Du 3 au 5 avril',
        'media_id' => $this->media->id,
        'button_text' => 'En savoir plus',
        'link_type' => 'NONE',
        'news_id' => null,
        'event_id' => null,
        'church_id' => null,
        'external_url' => null,
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.media_id', $this->media->id)
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.media.url', $this->media->url());
});

it('clears a link target when the banner stops pointing at it', function () {
    $news = makeNews();

    $banner = Banner::create([
        'title' => 'À la une',
        'media_id' => $this->media->id,
        'link_type' => 'NEWS',
        'news_id' => $news->id,
    ]);

    $this->patchJson("/api/v1/admin/banners/{$banner->id}", [
        'link_type' => 'NONE',
        'news_id' => null,
        'event_id' => null,
        'church_id' => null,
        'external_url' => null,
    ])->assertOk()->assertJsonPath('data.news_id', null);

    expect($banner->fresh()->news_id)->toBeNull();
});

it('still refuses a link target that contradicts the link type', function () {
    $news = makeNews();

    $this->postJson('/api/v1/admin/banners', [
        'title' => 'Bannière',
        'media_id' => $this->media->id,
        'link_type' => 'NONE',
        'news_id' => $news->id,
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('queues a notification the way the panel does', function () {
    $notification = PushNotification::create([
        'title' => 'Culte spécial',
        'body' => 'Rendez-vous dimanche à 9h.',
        'audience' => 'ALL',
        'status' => 'DRAFT',
    ]);

    $this->patchJson("/api/v1/admin/notifications/{$notification->id}", [
        'status' => 'PENDING',
        'district_id' => null,
        'zone_id' => null,
        'church_id' => null,
    ])->assertOk()->assertJsonPath('data.status', 'PENDING');
});

it('counts the children shown in the structure lists', function () {
    $this->getJson('/api/v1/admin/districts')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total', 'last_page']]);
});

it('rejects a null on an enum column instead of crashing on the insert', function () {
    $banner = Banner::create([
        'title' => 'A la une',
        'media_id' => $this->media->id,
        'link_type' => 'EXTERNAL',
        'external_url' => 'https://foursquare.ci',
    ]);

    // link_type est NOT NULL avec un defaut : absent, la base decide ;
    // present, la valeur doit etre valide.
    $this->patchJson("/api/v1/admin/banners/{$banner->id}", [
        'title' => 'Titre corrige',
        'link_type' => null,
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.fields.link_type.0', fn (string $m) => $m !== '');

    expect($banner->fresh()->link_type)->toBe('EXTERNAL');

    $this->patchJson("/api/v1/admin/banners/{$banner->id}", ['title' => 'Titre corrige'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Titre corrige')
        ->assertJsonPath('data.link_type', 'EXTERNAL');
});
