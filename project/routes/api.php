<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\EventRegistrationController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PrivacyConsentController;
use App\Http\Controllers\Api\V1\PrivacyDataRequestController;
use App\Http\Controllers\Api\V1\StructureController;
use App\Http\Middleware\CachePublicResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function (): void {

    // ---------------------------------------------------------------- public
    // Lectures servies depuis un cache court, vérifié avant la limitation de
    // débit : les pics d'ouverture de l'application ne touchent pas la base.
    Route::middleware([CachePublicResponse::class, 'throttle:api-public'])->group(function (): void {
        Route::get('home', HomeController::class)->name('home');

        Route::get('news', [NewsController::class, 'index'])->name('news.index');
        Route::get('news/{slug}', [NewsController::class, 'show'])->name('news.show');

        Route::get('events', [EventController::class, 'index'])->name('events.index');
        Route::get('events/{slug}', [EventController::class, 'show'])->name('events.show');

        Route::get('districts', [StructureController::class, 'districts'])->name('districts.index');
        Route::get('churches', [StructureController::class, 'churches'])->name('churches.index');
        Route::get('churches/{slug}', [StructureController::class, 'church'])->name('churches.show');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    });

    // Images immuables, gardées en cache par les téléphones. Pas de limitation
    // de débit : beaucoup de téléphones partagent une même IP (réseaux mobiles,
    // Wi-Fi d'une église) et chaque contrôle coûte des écritures en base.
    Route::get('media/{media}/file', [MediaController::class, 'file'])->name('media.file');

    Route::post('devices', [DeviceController::class, 'store'])
        ->middleware('throttle:api-public')->name('devices.store');

    Route::post('events/{event}/register', [EventRegistrationController::class, 'store'])
        ->middleware('throttle:registrations')->name('events.register');

    Route::prefix('privacy')->name('privacy.')->group(function (): void {
        Route::post('consents', [PrivacyConsentController::class, 'grant'])
            ->middleware('throttle:privacy-consents')->name('consents.store');
        Route::post('consents/withdrawals', [PrivacyConsentController::class, 'withdraw'])
            ->middleware('throttle:privacy-consents')->name('consents.withdraw');
        Route::post('data-requests', [PrivacyDataRequestController::class, 'store'])
            ->middleware('throttle:privacy-requests')->name('data-requests.store');
    });

    // ------------------------------------------------------------------ auth
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
        });
    });

    // ----------------------------------------------------------------- admin
    // Chaque action est en plus vérifiée par une Policy ou une Gate.
    Route::prefix('admin')->name('admin.')
        ->middleware(['auth:sanctum', 'admin', 'throttle:admin'])
        ->group(function (): void {
            Route::get('dashboard', Admin\DashboardController::class)->name('dashboard');

            Route::apiResource('districts', Admin\DistrictController::class);
            Route::apiResource('churches', Admin\ChurchController::class);
            Route::apiResource('news', Admin\NewsController::class);
            Route::apiResource('events', Admin\EventController::class);
            Route::apiResource('news-categories', Admin\NewsCategoryController::class)
                ->parameters(['news-categories' => 'category']);
            Route::apiResource('event-categories', Admin\EventCategoryController::class)
                ->parameters(['event-categories' => 'category']);
            Route::apiResource('banners', Admin\BannerController::class);
            Route::apiResource('notifications', Admin\NotificationController::class);
            Route::post('notifications/{notification}/send', [Admin\NotificationController::class, 'send'])
                ->name('notifications.send');
            Route::apiResource('devices', Admin\DeviceController::class)->only(['index', 'show', 'update', 'destroy']);
            Route::apiResource('users', Admin\UserController::class);
            Route::apiResource('privacy-requests', Admin\PrivacyRequestController::class)
                ->only(['index', 'show', 'update'])->parameters(['privacy-requests' => 'privacyRequest']);
            Route::apiResource('privacy-consents', Admin\PrivacyConsentController::class)
                ->only(['index', 'show'])->parameters(['privacy-consents' => 'privacyConsent']);

            Route::get('registrations', [Admin\RegistrationController::class, 'index'])->name('registrations.index');
            Route::get('registrations/export', [Admin\RegistrationController::class, 'export'])->name('registrations.export');
            Route::delete('registrations/{registration}', [Admin\RegistrationController::class, 'destroy'])->name('registrations.destroy');

            Route::get('settings', [Admin\SettingController::class, 'show'])->name('settings.show');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');

            Route::get('media', [Admin\MediaController::class, 'index'])->name('media.index');
            Route::get('media/{media}', [Admin\MediaController::class, 'show'])->name('media.show');
            Route::delete('media/{media}', [Admin\MediaController::class, 'destroy'])->name('media.destroy');
            Route::post('media', [Admin\MediaController::class, 'store'])
                ->middleware('throttle:uploads')->name('media.store');
        });
});
