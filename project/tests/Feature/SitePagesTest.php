<?php

use App\Models\Church;
use App\Models\DailyVerse;
use App\Models\Event;
use App\Models\News;
use Database\Seeders\ProgrammesSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(ProgrammesSeeder::class);
    Event::query()->update(['status' => 'PUBLISHED']);
    News::query()->update(['status' => 'PUBLISHED', 'published_at' => now()->subDay()]);
});

it('serves every public page without setting a cookie', function (string $path) {
    $response = $this->get($path)->assertOk();

    expect($response->headers->getCookies())->toBe([])
        ->and($response->headers->get('Cache-Control'))->toContain('public');
})->with([
    '/', '/actualites', '/actualites?portee=CHURCH', '/evenements', '/evenements?statut=PAST',
    '/notre-eglise', '/eglises', '/eglises?q=niangon', '/meditations', '/decouvrir', '/sitemap.xml',
]);

it('shows the content of the church, its events and the verse of the day', function () {
    DailyVerse::create(['text' => 'Car Dieu a tant aimé le monde', 'reference' => 'Jean 3:16', 'bible_version' => 'LSG', 'publication_date' => today()]);
    $event = Event::where('slug', 'autel-des-parfums-novembre-2026')->sole();
    $news = News::query()->first();

    $this->get('/')->assertSee('Jean 3:16')->assertSee('Foursquare Niangon Révélation');
    $this->get('/notre-eglise')->assertSee('Église Foursquare Niangon Révélation')->assertSee('"@type":"Church"', false);
    $this->get('/evenements/'.$event->slug)->assertOk()->assertSee($event->title)->assertSee('"@type":"Event"', false);
    $this->get('/actualites/'.$news->slug)->assertOk()->assertSee($news->title);
    $this->get('/eglises/'.Church::where('slug', 'eglise-foursquare-niangon-revelation')->value('slug'))->assertOk();
    $this->get('/sitemap.xml')->assertSee(route('site.events.show', $event->slug), false);
});

it('hides drafts and answers a branded 404', function () {
    $news = News::query()->first();
    $news->update(['status' => 'DRAFT']);

    $this->get('/actualites/'.$news->slug)->assertNotFound()->assertSee('Page introuvable');
    $this->get('/decouvrir/history')->assertNotFound();
});

it('switches to national mode without a site church', function () {
    config(['site.church' => null]);

    $this->get('/')->assertOk()->assertDontSee('Notre église');
    $this->get('/notre-eglise')->assertNotFound();
});
