<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     */
    protected $commands = [
        // Add your custom commands here
        \App\Console\Commands\CreateMissingPosTables::class,
        \App\Console\Commands\NotificationHealthCheckCommand::class,
        \App\Console\Commands\GenerateSupplyAlerts::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Notification system health checks
        $schedule->command('notifications:health-check --silent')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->environments(['production', 'staging']);
                 
        $schedule->command('notifications:health-check --silent')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->environments(['local', 'development']);
        
        // Inventory alert generation
        $schedule->command('inventory:generate-alerts --silent')
                 ->dailyAt('08:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->environments(['production', 'staging']);
                 
        $schedule->command('inventory:generate-alerts --silent')
                 ->everySixHours()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->environments(['local', 'development']);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}