<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Attendance Events
use App\Events\AttendanceCheckedIn;
use App\Events\AttendanceCheckedOut;
use App\Events\LateCheckInDetected;
use App\Events\EarlyDepartureDetected;

// Report Events
use App\Events\ReportGenerated;
use App\Events\ReportGenerationFailed;
use App\Events\ScheduledReportReady;

// Attendance Listeners
use App\Listeners\SendAttendanceCheckInNotification;
use App\Listeners\SendAttendanceCheckOutNotification;
use App\Listeners\SendLateCheckInNotification;
use App\Listeners\SendEarlyDepartureNotification;

// Report Listeners
use App\Listeners\SendReportGeneratedNotification;
use App\Listeners\SendReportGenerationFailedNotification;
use App\Listeners\SendScheduledReportNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Attendance Events
        AttendanceCheckedIn::class => [
            SendAttendanceCheckInNotification::class,
        ],
        
        AttendanceCheckedOut::class => [
            SendAttendanceCheckOutNotification::class,
        ],
        
        LateCheckInDetected::class => [
            SendLateCheckInNotification::class,
        ],
        
        EarlyDepartureDetected::class => [
            SendEarlyDepartureNotification::class,
        ],
        
        // Report Events
        ReportGenerated::class => [
            SendReportGeneratedNotification::class,
        ],
        
        ReportGenerationFailed::class => [
            SendReportGenerationFailedNotification::class,
        ],
        
        ScheduledReportReady::class => [
            SendScheduledReportNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}