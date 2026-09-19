<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\StructureController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function (): void {

    // ---------------------------------------------------------------- public
    Route::middleware('throttle:api-public')->group(function (): void {
        Route::get('home', HomeController::class)->name('home');

        Route::get('news', [NewsController::class, 'index'])->name('news.index');
        Route::get('news/{slug}', [NewsController::class, 'show'])->name('news.show');

        Route::get('events', [EventController::class, 'index'])->name('events.index');
        Route::get('events/{slug}', [EventController::class, 'show'])->name('events.show');

        Route::get('districts', [StructureController::class, 'districts'])->name('districts.index');
        Route::get('zones', [StructureController::class, 'zones'])->name('zones.index');
        Route::get('churches', [StructureController::class, 'churches'])->name('churches.index');
        Route::get('churches/{slug}', [StructureController::class, 'church'])->name('churches.show');

        Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
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
    Route::prefix('admin')->name('admin.')
        ->middleware(['auth:sanctum', 'admin', 'throttle:admin'])
        ->group(function (): void {
            Route::apiResource('districts', Admin\DistrictController::class);
            Route::apiResource('zones', Admin\ZoneController::class);
            Route::apiResource('churches', Admin\ChurchController::class);
            Route::apiResource('news', Admin\NewsController::class);
            Route::apiResource('events', Admin\EventController::class);
            Route::apiResource('banners', Admin\BannerController::class);
            Route::apiResource('notifications', Admin\NotificationController::class);
            Route::post('notifications/{notification}/send', [Admin\NotificationController::class, 'send'])
                ->name('notifications.send');
            Route::apiResource('devices', Admin\DeviceController::class)->only(['index', 'show', 'update', 'destroy']);

            Route::get('media', [Admin\MediaController::class, 'index'])->name('media.index');
            Route::get('media/{media}', [Admin\MediaController::class, 'show'])->name('media.show');
            Route::delete('media/{media}', [Admin\MediaController::class, 'destroy'])->name('media.destroy');
            Route::post('media', [Admin\MediaController::class, 'store'])
                ->middleware('throttle:uploads')->name('media.store');
        });
});
