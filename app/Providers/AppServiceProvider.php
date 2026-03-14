<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Appointment;
use App\Observers\AppointmentObserver;

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
        Schema::defaultStringLength(191);

        // Register observers
        Appointment::observe(AppointmentObserver::class);

        // Grant all permissions to administrators
        Gate::before(function (User $user, string $ability) {
            if ($user->role === 'administrator') {
                return true;
            }
            if ($user->hasRole('administrator')) {
                return true;
            }
        });

        // ─── Apply business timezone ────────────────────────────────────────────
        // Reads the timezone from the settings DB (key: business.timezone).
        // Falls back to 'Asia/Kolkata' (IST +05:30) if not yet configured.
        //
        // This overrides the UTC default in config/app.php so that ALL
        // timestamps — Carbon::now(), today(), check-in/out, reports —
        // are expressed in the configured local time.
        //
        // Note: date_default_timezone_set() affects PHP's date functions,
        // and setting config('app.timezone') is picked up by Carbon v2/v3
        // via the next Carbon::now() / Carbon::today() call automatically
        // because Carbon reads app.timezone from the config.
        try {
            $timezone = \App\Models\Setting::get('business.timezone', 'Asia/Kolkata');
            if ($timezone && @timezone_open($timezone) !== false) {
                date_default_timezone_set($timezone);
                config(['app.timezone' => $timezone]);
            }
        } catch (\Exception $e) {
            // DB not ready (e.g. during migrations) — apply safe fallback
            date_default_timezone_set('Asia/Kolkata');
            config(['app.timezone' => 'Asia/Kolkata']);
        }
        // ───────────────────────────────────────────────────────────────────────

        // Share currency symbol with all views
        try {
            $currencySymbol = \App\Models\Setting::get('payment.currency_symbol', '$');
            \Illuminate\Support\Facades\View::share('currencySymbol', $currencySymbol);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\View::share('currencySymbol', '$');
        }

        // Share logo URLs with all views
        try {
            $logoPath = \App\Models\Setting::get('business.logo');
            $appLogoUrl     = $logoPath ? \Illuminate\Support\Facades\Storage::url($logoPath) : '/images/logo.png';
            $appLogoDarkUrl = $logoPath ? \Illuminate\Support\Facades\Storage::url($logoPath) : '/images/logo-black.png';
            $appLogoSmUrl   = $logoPath ? \Illuminate\Support\Facades\Storage::url($logoPath) : '/images/logo-sm.png';
            \Illuminate\Support\Facades\View::share('appLogoUrl', $appLogoUrl);
            \Illuminate\Support\Facades\View::share('appLogoDarkUrl', $appLogoDarkUrl);
            \Illuminate\Support\Facades\View::share('appLogoSmUrl', $appLogoSmUrl);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\View::share('appLogoUrl', '/images/logo.png');
            \Illuminate\Support\Facades\View::share('appLogoDarkUrl', '/images/logo-black.png');
            \Illuminate\Support\Facades\View::share('appLogoSmUrl', '/images/logo-sm.png');
        }
    }
}
