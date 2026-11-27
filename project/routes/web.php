<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Web\LegalPageController;
use App\Http\Controllers\Web\SiteController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

// Site public en lecture seule : ni session ni cookie, cache navigateur 5 min.
Route::controller(SiteController::class)
    ->withoutMiddleware(SiteController::STATELESS)
    ->middleware('cache.headers:public;max_age=300;etag')
    ->group(function () {
        Route::get('/', 'home')->name('home');
        Route::get('actualites', 'newsIndex')->name('site.news.index');
        Route::get('actualites/{slug}', 'newsShow')->name('site.news.show');
        Route::get('evenements', 'eventsIndex')->name('site.events.index');
        Route::get('evenements/{slug}', 'eventShow')->name('site.events.show');
        Route::get('notre-eglise', 'myChurch')->name('site.church');
        Route::get('eglises', 'churches')->name('site.churches.index');
        Route::get('eglises/{slug}', 'churchShow')->name('site.churches.show');
        Route::get('meditations', 'meditations')->name('site.meditations.index');
        Route::get('meditations/{id}', 'meditationShow')->whereUuid('id')->name('site.meditations.show');
        Route::get('decouvrir', 'discover')->name('site.discover.index');
        Route::get('decouvrir/{key}', 'discoverShow')->name('site.discover.show');
        Route::get('sitemap.xml', 'sitemap')->name('site.sitemap');
    });

// Pages juridiques publiques exigées par les boutiques d'applications.
Route::get('{page}', LegalPageController::class)
    ->whereIn('page', ['privacy', 'legal', 'terms', 'cookies', 'consent'])->name('legal.show');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
