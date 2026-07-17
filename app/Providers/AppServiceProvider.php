<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fix php artisan route:list crash — Queue Worker needs isDownForMaintenance
        $this->app->bind(\Illuminate\Queue\Worker::class, function ($app) {
            return new \Illuminate\Queue\Worker(
                $app['queue'],
                $app['events'],
                $app['cache.store'] ?? $app['cache']->driver(),
                function () {
                    return $app->isDownForMaintenance();
                }
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Apply business timezone from settings (if available)
        $this->applyBusinessTimezone();

        // Apply security session timeout from settings (if available)
        $this->applySessionTimeout();

        try {
            $appLogoUrl = asset('images/logo.png');
            $appLogoSmUrl = asset('images/logo-sm.png');
            $appLogoDarkUrl = asset('images/logo-dark.png');

            if (!file_exists(public_path('images/logo.png'))) {
                $appLogoUrl = asset('images/logo-sm.png');
            }
            if (!file_exists(public_path('images/logo-sm.png'))) {
                $appLogoSmUrl = 'https://placehold.co/150x50?text=RashNail';
                $appLogoUrl = 'https://placehold.co/150x50?text=RashNail';
                $appLogoDarkUrl = 'https://placehold.co/150x50?text=RashNail';
            }

            view()->share(compact('appLogoUrl', 'appLogoSmUrl', 'appLogoDarkUrl'));
        } catch (\Exception $e) {
            view()->share([
                'appLogoUrl' => 'https://placehold.co/150x50?text=RashNail',
                'appLogoSmUrl' => 'https://placehold.co/150x50?text=RashNail',
                'appLogoDarkUrl' => 'https://placehold.co/150x50?text=RashNail',
            ]);
        }
    }

    /**
     * Read business.timezone from the settings table and apply it.
     * Wrapped in try/catch to handle missing table during migrations.
     */
    private function applyBusinessTimezone(): void
    {
        try {
            $timezone = \App\Models\Setting::get('business.timezone');

            if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (\Exception $e) {
            // Settings table may not exist yet (e.g. during migrations)
        }
    }

    /**
     * Read security.session_timeout from the settings table and apply it
     * to config('session.lifetime'). Wrapped in try/catch to handle
     * missing table during migrations.
     */
    private function applySessionTimeout(): void
    {
        try {
            $timeout = \App\Models\Setting::get('security.session_timeout', 120);

            if (is_numeric($timeout) && (int) $timeout > 0) {
                config(['session.lifetime' => (int) $timeout]);
            }
        } catch (\Exception $e) {
            // Settings table may not exist yet (e.g. during migrations)
        }
    }
}
