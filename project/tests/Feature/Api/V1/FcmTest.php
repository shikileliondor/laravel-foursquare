<?php

use App\Jobs\SendPushNotification;
use App\Models\Church;
use App\Models\Device;
use App\Models\District;
use App\Models\PushNotification;
use App\Models\User;
use App\Models\Zone;
use App\Services\FcmClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create([
        'role' => 'SUPER_ADMIN',
        'status' => 'ACTIVE',
    ]));

    $this->district = District::create(['name' => 'District Abidjan Nord']);
    $this->zone = Zone::create(['district_id' => $this->district->id, 'name' => 'Zone Abobo']);
    $this->church = Church::create(['zone_id' => $this->zone->id, 'name' => 'Église Centrale']);
});

/** Cle RSA de test : openssl_sign n'a besoin d'aucune configuration systeme. */
const TEST_PRIVATE_KEY = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDVNz20CVqcuvFG
hObY0EGktxGsESwC+eTFaxmGFpIqAGmmYb0JF8eLmk6FY3BmvfprAgT2Dm9zct27
EhIWA0Rf5Qm1X7A1Jsew6MdAqV8g0YfDC9md782ye3Ycxw9G5+c3CpcQCRENpVYc
BezgpVdjEawE8df1lKDh8CvCmATiIXvaRGqjDBeZVi2FaBIlpOfPM6QlR954sWxy
IJ4i5qm9KjOb70ZZgWjqGCebAW9Sfif0/w0n+zbzr+/G7VbTp9TMdkaKYFygIBlj
xCfVXgE8mG5sEuFb5yJ8r2bepaSKN24snRG1TQaCXSt4zrctmwzrGajtWi8RGn/A
ZPH28Uv1AgMBAAECggEAChR0Yzk/L08UaANeQukswuPXi708fOy7RYZuLJPCZKAk
K0/OML3CJ8kepcy3lb8TTyrHX3b7cUeAnXImjvB0zSnJUmmyYJjmpbTwWPZVsnn/
A2ThUvrXmSgGdsIGAitySvzCFOmw0WaiUjnV4u4kjWD0M0ALUY3HKCQc4k3u8Sr4
+lmqsSUG+EZI9beryJ2YmWdnFhIyw2vOKVB6S9Lv6XnUcnDpJc3ymyxIvUPe3FBo
BnMgSouL9TaAP16nL+9ThvpfdJHT7DW+VkZY387K3ZNfn0XneC1IXS1C+HNo559D
ISBECy73Hu68p7lvNZ+zkRpelvAZCQrSEpIgCBNgUwKBgQDwh50dgsCbi3QfOxcT
JVpbTqEHclE7+Ob+FHZ5dwzR4Z3599BgCi+b+XnHQRbCsa1L2yAjp+FVVVkOfTNC
lgMXyqZAGxMYkqHsJBmAOIE78Duh+eCnRa4+TTuMtO4IK8RZaE6327EyUAN7qwDU
45eX8973W8HHS4t2sq7kXBAJjwKBgQDi7eVO/hgqFtGRsK3b/ECh6zwBH/TjdUZp
rep90PjXayoryYERh7ymzE4rm2Wen9XY3aHXAtZGygCY1fFs7JpUoBdDMcNuCkPW
xVcXT2t5550KXLP4jscHhx/1SbyFwGfD71AyL31IHv6b8Z+aM5ApUkFamJYm4dp/
Fd/xvlNoOwKBgEGWyWo1sG357hViGJ247tW9MD4Gl05CRkL8s33Vz+IfouN6BxFZ
VbgzpFiIUDuRFc98llwCuHh7iIhh7at3mqpPMVyDxYZK3eq1wVpsjhkZHjnCGNek
Map9hwKMh+gkfytePTD3sG8m0HxVmilzUnA2KRPMqp+84u/gM77Dt//TAoGAdied
rJp6ZHfLGEtgyti1olkDviYwcLogNmgaXvYOisM+itv5OvJs1IAt92CK8aORScBk
Qro0bVlCJFHIyYkL9iIA2rivY70ug0XuybFNaYSh7MJF5pYYpR/DEQkagp2iqY8S
N38ogQO6V+Hf0v8kAY/VfWFTVN1l0aPHHWyWqv8CgYAQFdAPiysg6zgueXlja8l7
dlB1mo7KO4EANBwH6jtMZke2dXrKvD+tu8al3MmiuM8GGZtekp3bFjJs06+qL0iY
s1+bQ7qSZttGPO0teeYISu3jtJnv8zGVdU7Ee3iUGPobwXMcRLtVe15PLww1ZuLR
J5dJ3cpz4/CH1qdSol7tOg==
-----END PRIVATE KEY-----
PEM;

/** Un compte de service jetable, pour que la signature RS256 soit reellement exercee. */
function fakeServiceAccount(): string
{
    $path = storage_path('framework/testing/service-account.json');
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, json_encode([
        'client_email' => 'push@foursquare.iam.gserviceaccount.com',
        'private_key' => TEST_PRIVATE_KEY,
        'project_id' => 'foursquare-ci',
    ]));

    config(['fcm.credentials' => $path, 'fcm.project_id' => 'foursquare-ci']);

    return $path;
}

function fakeFcm(array $sendResponse = ['name' => 'projects/foursquare-ci/messages/1'], int $status = 200): void
{
    Http::fake([
        'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.fake', 'expires_in' => 3600]),
        'fcm.googleapis.com/*' => Http::response($sendResponse, $status),
    ]);
}

function makeNotification(array $attributes = []): PushNotification
{
    return PushNotification::create(array_merge([
        'title' => 'Culte de rentrée',
        'body' => 'Rendez-vous dimanche à 9h.',
        'audience' => 'ALL',
        'status' => 'DRAFT',
    ], $attributes));
}

it('registers a device and derives its zone and district from its church', function () {
    $this->postJson('/api/v1/devices', [
        'fcm_token' => 'token-abc',
        'platform' => 'android',
        'church_id' => $this->church->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.church_id', $this->church->id)
        ->assertJsonPath('data.zone_id', $this->zone->id)
        ->assertJsonPath('data.district_id', $this->district->id)
        ->assertJsonPath('data.notifications_enabled', true);
});

it('queues the notification when the admin hits send', function () {
    Queue::fake();
    $notification = makeNotification();

    $this->postJson("/api/v1/admin/notifications/{$notification->id}/send")
        ->assertOk()
        ->assertJsonPath('data.status', 'PENDING');

    Queue::assertPushed(SendPushNotification::class);
});

it('accepts a firebase credentials directory', function () {
    $directory = storage_path('framework/testing/firebase');
    @mkdir($directory, 0777, true);
    file_put_contents($directory.'/firebase-admin.json', json_encode([
        'client_email' => 'push@foursquare.iam.gserviceaccount.com',
        'private_key' => TEST_PRIVATE_KEY,
        'project_id' => 'foursquare-ci',
    ]));

    config(['fcm.credentials' => $directory, 'fcm.project_id' => null]);

    expect(app(FcmClient::class)->configured())->toBeTrue();
});

it('refuses to send the same notification twice', function () {
    Queue::fake();
    $notification = makeNotification(['status' => 'SENT']);

    $this->postJson("/api/v1/admin/notifications/{$notification->id}/send")
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'NOTIFICATION_NOT_SENDABLE');

    Queue::assertNothingPushed();
});

it('only reaches the devices of the targeted church', function () {
    fakeServiceAccount();
    fakeFcm();

    Device::create(['fcm_token' => 'inside', 'platform' => 'android', 'church_id' => $this->church->id, 'zone_id' => $this->zone->id, 'district_id' => $this->district->id]);
    Device::create(['fcm_token' => 'outside', 'platform' => 'ios']);

    $notification = makeNotification([
        'audience' => 'CHURCH',
        'church_id' => $this->church->id,
        'status' => 'PENDING',
    ]);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    expect($notification->fresh()->status)->toBe('SENT')
        ->and($notification->fresh()->sent_at)->not->toBeNull();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'fcm.googleapis.com')
        && $request['message']['token'] === 'inside');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'fcm.googleapis.com')
        && $request['message']['token'] === 'outside');
});

it('drops a device whose token FCM no longer knows', function () {
    fakeServiceAccount();
    fakeFcm(['error' => ['status' => 'UNREGISTERED', 'message' => 'Requested entity was not found.']], 404);

    Device::create(['fcm_token' => 'dead-token', 'platform' => 'android']);
    $notification = makeNotification(['status' => 'PENDING']);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    // Rien n'a echoue de notre cote : le token etait perime, il est purge.
    // Rejouer l'envoi ne changerait rien, la notification reste SENT.
    expect(Device::where('fcm_token', 'dead-token')->exists())->toBeFalse()
        ->and($notification->fresh()->status)->toBe('SENT');
});

it('marks the notification failed when FCM itself errors out', function () {
    fakeServiceAccount();
    fakeFcm(['error' => ['status' => 'UNAVAILABLE', 'message' => 'The service is unavailable.']], 503);

    Device::create(['fcm_token' => 'live-token', 'platform' => 'android']);
    $notification = makeNotification(['status' => 'PENDING']);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    $notification = $notification->fresh();

    expect($notification->status)->toBe('FAILED')
        ->and($notification->last_error)->toContain('unavailable')
        ->and($notification->retry_count)->toBe(1)
        ->and(Device::where('fcm_token', 'live-token')->exists())->toBeTrue();
});

it('skips devices that turned notifications off', function () {
    fakeServiceAccount();
    fakeFcm();

    Device::create(['fcm_token' => 'muted', 'platform' => 'android', 'notifications_enabled' => false]);
    $notification = makeNotification(['status' => 'PENDING']);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'fcm.googleapis.com'));
});

it('fails loudly when no service account is configured', function () {
    config(['fcm.credentials' => null]);

    Device::create(['fcm_token' => 'token', 'platform' => 'android']);
    $notification = makeNotification(['status' => 'PENDING']);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    $notification = $notification->fresh();

    expect($notification->status)->toBe('FAILED')
        ->and($notification->last_error)->toContain('FCM non configuré')
        ->and($notification->retry_count)->toBe(1);
});

it('records how many devices were actually reached', function () {
    fakeServiceAccount();
    fakeFcm();

    Device::create(['fcm_token' => 'live-1', 'platform' => 'android']);
    Device::create(['fcm_token' => 'live-2', 'platform' => 'android']);
    $notification = makeNotification(['status' => 'PENDING']);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    $notification = $notification->fresh();

    expect($notification->status)->toBe('SENT')
        ->and($notification->delivered_count)->toBe(2)
        ->and($notification->pruned_count)->toBe(0)
        ->and($notification->failed_count)->toBe(0);
});

it('shows zero deliveries when every token was dead', function () {
    fakeServiceAccount();
    fakeFcm(['error' => ['status' => 'UNREGISTERED', 'message' => 'Requested entity was not found.']], 404);

    Device::create(['fcm_token' => 'dead-token', 'platform' => 'android']);
    $notification = makeNotification(['status' => 'PENDING']);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    $notification = $notification->fresh();

    // Le statut reste SENT — rien n'a echoue de notre cote — mais le bilan
    // empeche de croire qu'un telephone a recu quoi que ce soit.
    expect($notification->status)->toBe('SENT')
        ->and($notification->delivered_count)->toBe(0)
        ->and($notification->pruned_count)->toBe(1);
});

it('counts the failures when FCM rejects the send', function () {
    fakeServiceAccount();
    fakeFcm(['error' => ['status' => 'UNAVAILABLE', 'message' => 'The service is unavailable.']], 503);

    Device::create(['fcm_token' => 'live-token', 'platform' => 'android']);
    $notification = makeNotification(['status' => 'PENDING']);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    $notification = $notification->fresh();

    expect($notification->status)->toBe('FAILED')
        ->and($notification->delivered_count)->toBe(0)
        ->and($notification->failed_count)->toBe(1);
});

it('clears the previous tally when a failed notification is sent again', function () {
    fakeServiceAccount();
    fakeFcm();

    Device::create(['fcm_token' => 'live-token', 'platform' => 'android']);
    $notification = makeNotification([
        'status' => 'PENDING',
        'delivered_count' => 0,
        'failed_count' => 7,
        'pruned_count' => 3,
    ]);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    $notification = $notification->fresh();

    expect($notification->delivered_count)->toBe(1)
        ->and($notification->failed_count)->toBe(0)
        ->and($notification->pruned_count)->toBe(0);
});

it('sends a diagnostic push to an explicit token without touching the database', function () {
    fakeServiceAccount();
    fakeFcm();

    $this->artisan('fcm:test', ['--token' => ['token-du-telephone']])
        ->expectsOutputToContain('foursquare-ci')
        ->assertExitCode(0);

    // Un diagnostic ne laisse aucune trace dans l'historique du panel.
    expect(PushNotification::count())->toBe(0);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'fcm.googleapis.com')
        && $request['message']['token'] === 'token-du-telephone');
});

it('refuses the diagnostic push when no service account is configured', function () {
    config(['fcm.credentials' => null]);

    $this->artisan('fcm:test', ['--token' => ['token-du-telephone']])
        ->expectsOutputToContain('FCM non configuré')
        ->assertExitCode(1);
});

it('keeps a dead token in place when only diagnosing', function () {
    fakeServiceAccount();
    fakeFcm(['error' => ['status' => 'UNREGISTERED', 'message' => 'Requested entity was not found.']], 404);

    Device::create(['fcm_token' => 'dead-token', 'platform' => 'android']);

    $this->artisan('fcm:test', ['--force' => true])->assertExitCode(1);

    // Contrairement a un envoi reel, le diagnostic ne purge rien : il repond
    // a une question, il ne modifie pas l'etat du parc.
    expect(Device::where('fcm_token', 'dead-token')->exists())->toBeTrue();
});

it('fails with an actionable reason when the audience has no device at all', function () {
    fakeServiceAccount();
    fakeFcm();

    $notification = makeNotification(['status' => 'PENDING']);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    $notification = $notification->fresh();

    // SENT serait un mensonge : personne n'a ete joint, et surtout `send()`
    // refuse SENT — la notification serait definitivement inrenvoyable.
    expect($notification->status)->toBe('FAILED')
        ->and($notification->last_error)->toContain('Aucun appareil enregistré');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'fcm.googleapis.com'));
});

it('points at the missing church link when a targeted audience is empty', function () {
    fakeServiceAccount();
    fakeFcm();

    Device::create(['fcm_token' => 'sans-eglise', 'platform' => 'android']);

    $notification = makeNotification([
        'audience' => 'CHURCH',
        'church_id' => $this->church->id,
        'status' => 'PENDING',
    ]);

    (new SendPushNotification($notification->id))->handle(app(FcmClient::class));

    expect($notification->fresh()->last_error)->toContain('church_id');
});

it('releases a notification whose job died mid-flight', function () {
    $notification = makeNotification(['status' => 'PROCESSING']);

    // Ce que la file appelle quand le job leve ou depasse son temps.
    (new SendPushNotification($notification->id))->failed(new RuntimeException('worker tué'));

    $notification = $notification->fresh();

    expect($notification->status)->toBe('FAILED')
        ->and($notification->last_error)->toContain('worker tué')
        ->and($notification->retry_count)->toBe(1);
});

it('leaves a finished notification alone when the queue reports a failure', function () {
    $notification = makeNotification(['status' => 'SENT', 'delivered_count' => 3]);

    (new SendPushNotification($notification->id))->failed(new RuntimeException('trop tard'));

    // L'envoi avait abouti : le rapport tardif de la file ne doit pas
    // reecrire un resultat acquis.
    expect($notification->fresh()->status)->toBe('SENT')
        ->and($notification->fresh()->delivered_count)->toBe(3);
});

it('reclaims a notification stuck longer than the grace period', function () {
    $stuck = makeNotification(['status' => 'PROCESSING']);
    $stuck->forceFill(['updated_at' => now()->subHour()])->saveQuietly();

    $recent = makeNotification(['status' => 'PENDING']);

    $this->artisan('notifications:reclaim', ['--minutes' => 15])
        ->expectsOutputToContain('1 notification(s) libérée(s)')
        ->assertExitCode(0);

    expect($stuck->fresh()->status)->toBe('FAILED')
        ->and($stuck->fresh()->last_error)->toContain('ne s\'est jamais terminé')
        // Un envoi tout juste mis en file ne doit pas etre tue au passage.
        ->and($recent->fresh()->status)->toBe('PENDING');
});

it('changes nothing in dry run', function () {
    $stuck = makeNotification(['status' => 'PROCESSING']);
    $stuck->forceFill(['updated_at' => now()->subHour()])->saveQuietly();

    $this->artisan('notifications:reclaim', ['--minutes' => 15, '--dry-run' => true])
        ->assertExitCode(0);

    expect($stuck->fresh()->status)->toBe('PROCESSING');
});

it('lets the admin send again once a failed notification is back to draft', function () {
    Queue::fake();
    $notification = makeNotification(['status' => 'FAILED', 'last_error' => 'Aucun appareil']);

    $this->patchJson("/api/v1/admin/notifications/{$notification->id}", ['status' => 'DRAFT'])
        ->assertOk();

    $this->postJson("/api/v1/admin/notifications/{$notification->id}/send")
        ->assertOk()
        ->assertJsonPath('data.status', 'PENDING');

    Queue::assertPushed(SendPushNotification::class);
});
