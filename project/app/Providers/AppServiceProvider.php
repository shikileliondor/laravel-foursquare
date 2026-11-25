<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configureGates();
    }

    /**
     * Ressources sans périmètre territorial : bannières, catégories et
     * appareils (national), paramètres de la plateforme (super admin).
     */
    protected function configureGates(): void
    {
        Gate::define('manage-national', fn (User $user) => $user->isNationalAdmin());
        Gate::define('manage-platform', fn (User $user) => $user->isSuperAdmin());
    }

    /**
     * Distinct budgets: public reads, login, admin mutations, uploads.
     */
    protected function configureRateLimiting(): void
    {
        // Ne compte que les lectures absentes du cache public et l'enregistrement
        // des appareils. Large, car beaucoup de téléphones partagent une même IP.
        RateLimiter::for('api-public', fn (Request $request) => Limit::perMinute(300)->by($request->ip()));

        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
        ]);

        RateLimiter::for('admin', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

        // Inscriptions publiques aux événements : limitées pour freiner les robots.
        RateLimiter::for('registrations', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(30)->by($request->ip()),
        ]);

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
