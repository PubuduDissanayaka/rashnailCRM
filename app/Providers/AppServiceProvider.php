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
}
