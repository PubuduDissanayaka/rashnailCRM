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
        // Pagination markup. Laravel defaults to the Tailwind view, whose Previous/Next
        // arrows are inline SVGs sized by Tailwind classes (w-5 h-5). This theme has no
        // Tailwind, so those classes do nothing and each arrow renders at its default
        // size — the giant chevrons under any paginated table.
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // Brute-force protection for the login form (used by routes/web.php).
        // Keyed by email + IP so one noisy account cannot lock out everyone, and by
        // IP alone so spraying many accounts is still bounded.
        \Illuminate\Support\Facades\RateLimiter::for('login', function ($request) {
            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by(
                    \Illuminate\Support\Str::lower((string) $request->input('email')) . '|' . $request->ip()
                ),
                \Illuminate\Cache\RateLimiting\Limit::perHour(30)->by($request->ip()),
            ];
        });

        // Apply business timezone from settings (if available)
        $this->applyBusinessTimezone();

        // Apply security session timeout from settings (if available)
        $this->applySessionTimeout();

        try {
            // Check if user uploaded a logo via Settings → Business Information
            $uploadedLogo = \App\Models\Setting::get('business.logo');
            $uploadedFavicon = \App\Models\Setting::get('business.favicon');

            $appLogoUrl = $uploadedLogo
                ? \Illuminate\Support\Facades\Storage::url($uploadedLogo)
                : (file_exists(public_path('images/logo.png'))
                    ? asset('images/logo.png')
                    : asset('images/logo-sm.png'));

            $appLogoSmUrl = $uploadedLogo
                ? \Illuminate\Support\Facades\Storage::url($uploadedLogo)
                : (file_exists(public_path('images/logo-sm.png'))
                    ? asset('images/logo-sm.png')
                    : 'https://placehold.co/150x50?text=RashNail');

            $appLogoDarkUrl = $uploadedLogo
                ? \Illuminate\Support\Facades\Storage::url($uploadedLogo)
                : (file_exists(public_path('images/logo-dark.png'))
                    ? asset('images/logo-dark.png')
                    : 'https://placehold.co/150x50?text=RashNail');

            $appFaviconUrl = $uploadedFavicon
                ? \Illuminate\Support\Facades\Storage::url($uploadedFavicon)
                : (file_exists(public_path('images/favicon.ico'))
                    ? asset('images/favicon.ico')
                    : asset('images/logo-sm.png'));

            view()->share(compact('appLogoUrl', 'appLogoSmUrl', 'appLogoDarkUrl', 'appFaviconUrl'));
        } catch (\Exception $e) {
            view()->share([
                'appLogoUrl' => 'https://placehold.co/150x50?text=RashNail',
                'appLogoSmUrl' => 'https://placehold.co/150x50?text=RashNail',
                'appLogoDarkUrl' => 'https://placehold.co/150x50?text=RashNail',
                'appFaviconUrl' => 'https://placehold.co/32x32?text=RN',
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
