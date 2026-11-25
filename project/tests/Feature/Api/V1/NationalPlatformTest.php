<?php

use App\Models\Church;
use App\Models\District;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\News;
use App\Models\PushNotification;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->cotiere = District::create(['name' => 'District de la Côtière']);
    $this->other = District::create(['name' => 'Autre district']);
    $this->niangon = Church::create(['district_id' => $this->cotiere->id, 'name' => 'Niangon Révélation']);
    $this->ananeraie = Church::create(['district_id' => $this->cotiere->id, 'name' => 'Ananeraie']);
    $this->far = Church::create(['district_id' => $this->other->id, 'name' => 'Église lointaine']);
});

function npAdmin(string $role, array $attributes = []): User
{
    return User::factory()->create(array_merge(['role' => $role, 'status' => 'ACTIVE'], $attributes));
}

function npNews(string $scope, array $attributes = []): News
{
    return News::create(array_merge([
        'title' => 'Annonce '.$scope.' '.fake()->unique()->numberBetween(1, 9999),
        'content' => '<p>Texte</p>',
        'scope_type' => $scope,
        'status' => 'PUBLISHED',
        'published_at' => now()->subHour(),
    ], $attributes));
}

function npEvent(array $attributes = []): Event
{
    return Event::create(array_merge([
        'title' => 'Convention',
        'description' => 'Programme',
        'scope_type' => 'NATIONAL',
        'start_at' => now()->addWeek(),
        'end_at' => now()->addWeek()->addDay(),
        'status' => 'PUBLISHED',
        'registration_enabled' => true,
    ], $attributes));
}

// ------------------------------------------------------------- cloisonnement

it('lets a church admin publish only for its own church', function () {
    Sanctum::actingAs(npAdmin('CHURCH_ADMIN', ['church_id' => $this->niangon->id]));

    $this->postJson('/api/v1/admin/news', ['title' => 'Culte', 'content' => 'x', 'scope_type' => 'CHURCH', 'church_id' => $this->niangon->id])
        ->assertCreated();

    $this->postJson('/api/v1/admin/news', ['title' => 'Pirate', 'content' => 'x', 'scope_type' => 'CHURCH', 'church_id' => $this->ananeraie->id])
        ->assertForbidden();

    $this->postJson('/api/v1/admin/news', ['title' => 'National', 'content' => 'x', 'scope_type' => 'NATIONAL'])
        ->assertForbidden();
});

it('never lets a church admin read, edit or delete another church data', function () {
    $foreign = npNews('CHURCH', ['church_id' => $this->ananeraie->id]);
    npNews('CHURCH', ['church_id' => $this->niangon->id, 'title' => 'Chez moi']);
    Sanctum::actingAs(npAdmin('CHURCH_ADMIN', ['church_id' => $this->niangon->id]));

    $this->getJson('/api/v1/admin/news')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Chez moi');
    $this->getJson("/api/v1/admin/news/{$foreign->id}")->assertForbidden();
    $this->patchJson("/api/v1/admin/news/{$foreign->id}", ['title' => 'Modifié'])->assertForbidden();
    $this->deleteJson("/api/v1/admin/news/{$foreign->id}")->assertForbidden();
    $this->patchJson("/api/v1/admin/churches/{$this->ananeraie->id}", ['name' => 'Piratée'])->assertForbidden();

    expect($foreign->fresh()->title)->not->toBe('Modifié')
        ->and($this->ananeraie->fresh()->name)->toBe('Ananeraie');
});

it('lets a church admin edit its church but not move or deactivate it', function () {
    Sanctum::actingAs(npAdmin('CHURCH_ADMIN', ['church_id' => $this->niangon->id]));

    $this->patchJson("/api/v1/admin/churches/{$this->niangon->id}", ['service_times' => 'Dimanche 8h'])->assertOk();
    $this->patchJson("/api/v1/admin/churches/{$this->niangon->id}", ['district_id' => $this->other->id])->assertForbidden();
    $this->patchJson("/api/v1/admin/churches/{$this->niangon->id}", ['status' => 'INACTIVE'])->assertForbidden();
});

it('keeps a district admin inside its district', function () {
    Sanctum::actingAs(npAdmin('DISTRICT_ADMIN', ['district_id' => $this->cotiere->id]));

    $this->postJson('/api/v1/admin/events', ['title' => 'Séminaire', 'description' => 'x', 'start_at' => now()->addDay(), 'end_at' => now()->addDays(2), 'scope_type' => 'DISTRICT', 'district_id' => $this->cotiere->id])
        ->assertCreated();
    $this->postJson('/api/v1/admin/news', ['title' => 'Local', 'content' => 'x', 'scope_type' => 'CHURCH', 'church_id' => $this->ananeraie->id])
        ->assertCreated();
    $this->postJson('/api/v1/admin/news', ['title' => 'Ailleurs', 'content' => 'x', 'scope_type' => 'DISTRICT', 'district_id' => $this->other->id])
        ->assertForbidden();
    $this->postJson('/api/v1/admin/news', ['title' => 'Ailleurs', 'content' => 'x', 'scope_type' => 'CHURCH', 'church_id' => $this->far->id])
        ->assertForbidden();

    $this->postJson('/api/v1/admin/churches', ['district_id' => $this->cotiere->id, 'name' => 'Nouvelle'])->assertCreated();
    $this->postJson('/api/v1/admin/churches', ['district_id' => $this->other->id, 'name' => 'Intruse'])->assertForbidden();
    $this->patchJson("/api/v1/admin/churches/{$this->niangon->id}", ['status' => 'INACTIVE'])->assertOk();
    $this->patchJson("/api/v1/admin/churches/{$this->far->id}", ['name' => 'x'])->assertForbidden();
    $this->deleteJson("/api/v1/admin/churches/{$this->niangon->id}")->assertForbidden();

    $this->getJson('/api/v1/admin/churches')->assertOk()->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/admin/districts')->assertOk()->assertJsonCount(1, 'data');
    $this->postJson('/api/v1/admin/districts', ['name' => 'Nouveau'])->assertForbidden();
});

it('limits notifications to the admin perimeter', function () {
    Sanctum::actingAs(npAdmin('DISTRICT_ADMIN', ['district_id' => $this->cotiere->id]));

    $this->postJson('/api/v1/admin/notifications', ['title' => 'Tous', 'body' => 'x', 'audience' => 'ALL'])->assertForbidden();
    $this->postJson('/api/v1/admin/notifications', ['title' => 'District', 'body' => 'x', 'audience' => 'DISTRICT', 'district_id' => $this->cotiere->id])->assertCreated();

    $foreign = PushNotification::create(['title' => 'Ailleurs', 'body' => 'x', 'audience' => 'DISTRICT', 'district_id' => $this->other->id, 'status' => 'DRAFT']);
    $this->postJson("/api/v1/admin/notifications/{$foreign->id}/send")->assertForbidden();
});

it('keeps national-only resources away from territorial admins', function () {
    Sanctum::actingAs(npAdmin('DISTRICT_ADMIN', ['district_id' => $this->cotiere->id]));

    $this->getJson('/api/v1/admin/banners')->assertForbidden();
    $this->getJson('/api/v1/admin/devices')->assertForbidden();
    $this->getJson('/api/v1/admin/users')->assertForbidden();
    $this->getJson('/api/v1/admin/settings')->assertForbidden();
    $this->postJson('/api/v1/admin/news-categories', ['name' => 'Jeunesse'])->assertForbidden();
    $this->getJson('/api/v1/admin/news-categories')->assertOk();
});

it('lets a national admin manage territorial admins but not super admins', function () {
    $national = npAdmin('NATIONAL_ADMIN');
    $super = npAdmin('SUPER_ADMIN');
    Sanctum::actingAs($national);

    $this->postJson('/api/v1/admin/users', ['name' => 'Secrétaire', 'email' => 's@foursquare.ci', 'password' => 'Solide-Mot2Passe', 'role' => 'CHURCH_ADMIN', 'church_id' => $this->niangon->id])
        ->assertCreated()
        ->assertJsonPath('data.church.name', 'Niangon Révélation');
    $this->postJson('/api/v1/admin/users', ['name' => 'Chef', 'email' => 'c@foursquare.ci', 'password' => 'Solide-Mot2Passe', 'role' => 'SUPER_ADMIN'])
        ->assertForbidden();
    $this->postJson('/api/v1/admin/users', ['name' => 'Sans district', 'email' => 'd@foursquare.ci', 'password' => 'Solide-Mot2Passe', 'role' => 'DISTRICT_ADMIN'])
        ->assertUnprocessable();
    $this->patchJson("/api/v1/admin/users/{$super->id}", ['name' => 'x'])->assertForbidden();
    $this->patchJson("/api/v1/admin/users/{$national->id}", ['role' => 'SUPER_ADMIN'])->assertForbidden();
    $this->deleteJson("/api/v1/admin/users/{$national->id}")->assertForbidden();
});

it('cuts the sessions of a suspended admin', function () {
    $church = npAdmin('CHURCH_ADMIN', ['church_id' => $this->niangon->id]);
    $church->createToken('panel');
    Sanctum::actingAs(npAdmin('SUPER_ADMIN'));

    $this->patchJson("/api/v1/admin/users/{$church->id}", ['status' => 'SUSPENDED'])->assertOk();

    expect($church->tokens()->count())->toBe(0);
});

it('scopes the dashboard to the admin perimeter', function () {
    npNews('NATIONAL');
    npNews('CHURCH', ['church_id' => $this->niangon->id]);
    npNews('CHURCH', ['church_id' => $this->far->id]);
    Sanctum::actingAs(npAdmin('DISTRICT_ADMIN', ['district_id' => $this->cotiere->id]));

    $this->getJson('/api/v1/admin/dashboard')
        ->assertOk()
        ->assertJsonPath('data.districts', 1)
        ->assertJsonPath('data.churches', 2)
        ->assertJsonPath('data.news', 1);
});

// ---------------------------------------------------------------- inscriptions

it('registers a participant without an account, once per phone number', function () {
    $event = npEvent();
    $payload = ['full_name' => 'Awa Koné', 'phone' => '07 07 07 07 07', 'church_id' => $this->niangon->id];

    $this->postJson("/api/v1/events/{$event->slug}/register", $payload)
        ->assertCreated()
        ->assertJsonPath('data.registered', true)
        ->assertJsonMissingPath('data.phone');
    $this->postJson("/api/v1/events/{$event->id}/register", $payload)
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'ALREADY_REGISTERED');

    expect(EventRegistration::first()->phone)->toBe('0707070707');
});

it('refuses registrations that are closed, past or filled by a robot', function () {
    $closed = npEvent(['registration_enabled' => false]);
    $past = npEvent(['start_at' => now()->subDays(3), 'end_at' => now()->subDay()]);
    $open = npEvent();

    $this->postJson("/api/v1/events/{$closed->id}/register", ['full_name' => 'Awa Koné', 'phone' => '0707070707'])
        ->assertStatus(409)->assertJsonPath('error.code', 'REGISTRATION_CLOSED');
    $this->postJson("/api/v1/events/{$past->id}/register", ['full_name' => 'Awa Koné', 'phone' => '0707070707'])
        ->assertStatus(409);
    $this->postJson("/api/v1/events/{$open->id}/register", ['full_name' => 'Robot', 'phone' => '0707070707', 'website' => 'http://spam'])
        ->assertUnprocessable();
    $this->postJson("/api/v1/events/{$open->id}/register", ['full_name' => 'Awa Koné', 'phone' => 'abc'])
        ->assertUnprocessable();

    expect(EventRegistration::count())->toBe(0);
});

it('shows and exports registrations only to the admins of the event', function () {
    $local = npEvent(['scope_type' => 'CHURCH', 'church_id' => $this->niangon->id]);
    $foreign = npEvent(['scope_type' => 'CHURCH', 'church_id' => $this->far->id]);
    EventRegistration::create(['event_id' => $local->id, 'full_name' => 'Awa Koné', 'phone' => '0707070707']);
    EventRegistration::create(['event_id' => $foreign->id, 'full_name' => 'Yao Kouassi', 'phone' => '0101010101']);

    Sanctum::actingAs(npAdmin('CHURCH_ADMIN', ['church_id' => $this->niangon->id]));

    $this->getJson('/api/v1/admin/registrations')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.full_name', 'Awa Koné');

    $csv = $this->get('/api/v1/admin/registrations/export')->assertOk()->streamedContent();
    expect($csv)->toContain('Awa Koné')->not->toContain('Yao Kouassi');

    $this->deleteJson('/api/v1/admin/registrations/'.EventRegistration::where('event_id', $foreign->id)->value('id'))->assertForbidden();
});

it('never exposes participants publicly', function () {
    $event = npEvent();
    EventRegistration::create(['event_id' => $event->id, 'full_name' => 'Awa Koné', 'phone' => '0707070707']);

    $this->getJson("/api/v1/events/{$event->slug}")
        ->assertOk()
        ->assertJsonPath('data.registration_open', true)
        ->assertJsonMissingPath('data.registrations_count')
        ->assertDontSee('0707070707');
});

// ----------------------------------------------------------- personnalisation

it('shows national news only on the home screen without a reference church', function () {
    npNews('NATIONAL', ['title' => 'National']);
    npNews('DISTRICT', ['district_id' => $this->cotiere->id, 'title' => 'Côtière']);

    $this->getJson('/api/v1/home')->assertOk()->assertJsonCount(1, 'data.latest_news')->assertJsonPath('data.latest_news.0.source.label', 'Foursquare Côte d\'Ivoire');
});

it('adds the district and church of the reference church', function () {
    npNews('NATIONAL', ['title' => 'National']);
    npNews('DISTRICT', ['district_id' => $this->cotiere->id, 'title' => 'Côtière']);
    npNews('CHURCH', ['church_id' => $this->niangon->id, 'title' => 'Niangon']);
    npNews('CHURCH', ['church_id' => $this->far->id, 'title' => 'Lointaine']);

    $this->getJson("/api/v1/home?for_church_id={$this->niangon->id}")
        ->assertOk()
        ->assertJsonCount(3, 'data.latest_news')
        ->assertJsonPath('data.church.name', 'Niangon Révélation');

    $this->getJson("/api/v1/news?for_church_id={$this->niangon->id}&scope=CHURCH")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.source.label', 'Niangon Révélation');
});

it('lists the sent notifications relevant to the reference church', function () {
    $news = npNews('NATIONAL');
    PushNotification::create(['title' => 'Tous', 'body' => 'x', 'audience' => 'ALL', 'type' => 'NEWS', 'news_id' => $news->id, 'status' => 'SENT', 'sent_at' => now()]);
    PushNotification::create(['title' => 'Côtière', 'body' => 'x', 'audience' => 'DISTRICT', 'district_id' => $this->cotiere->id, 'status' => 'SENT', 'sent_at' => now()]);
    PushNotification::create(['title' => 'Ailleurs', 'body' => 'x', 'audience' => 'CHURCH', 'church_id' => $this->far->id, 'status' => 'SENT', 'sent_at' => now()]);
    PushNotification::create(['title' => 'Brouillon', 'body' => 'x', 'audience' => 'ALL', 'status' => 'DRAFT']);

    $this->getJson('/api/v1/notifications')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.news_slug', $news->slug);
    $this->getJson("/api/v1/notifications?for_church_id={$this->niangon->id}")->assertOk()->assertJsonCount(2, 'data');
});

it('opens a news by id as well as by slug', function () {
    $news = npNews('NATIONAL');

    $this->getJson("/api/v1/news/{$news->id}")->assertOk()->assertJsonPath('data.slug', $news->slug);
});

// ------------------------------------------------------ catégories et paramètres

it('lets a national admin manage categories used by every admin', function () {
    Sanctum::actingAs(npAdmin('NATIONAL_ADMIN'));

    $category = $this->postJson('/api/v1/admin/event-categories', ['name' => 'Convention'])->assertCreated()->json('data.id');

    $this->postJson('/api/v1/admin/events', ['title' => 'Convention 2026', 'description' => 'x', 'start_at' => now()->addDay(), 'end_at' => now()->addDays(2), 'scope_type' => 'NATIONAL', 'category_id' => $category, 'status' => 'PUBLISHED'])
        ->assertCreated()
        ->assertJsonPath('data.category.name', 'Convention');
});

it('lets the super admin choose the launch church shown by the app', function () {
    Sanctum::actingAs(npAdmin('SUPER_ADMIN'));

    $this->putJson('/api/v1/admin/settings', ['launch_church_id' => $this->niangon->id])
        ->assertOk()
        ->assertJsonPath('data.launch_church_id', $this->niangon->id);

    $this->getJson('/api/v1/home')->assertOk()->assertJsonPath('data.launch_church.name', 'Niangon Révélation');

    Sanctum::actingAs(npAdmin('NATIONAL_ADMIN'));
    $this->putJson('/api/v1/admin/settings', ['launch_church_id' => null])->assertForbidden();
});
