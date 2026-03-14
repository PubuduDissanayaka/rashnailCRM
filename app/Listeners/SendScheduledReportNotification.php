<?php

namespace App\Listeners;

use App\Events\ScheduledReportReady;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendScheduledReportNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The notification service.
     *
     * @var \App\Services\NotificationService
     */
    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @param \App\Services\NotificationService $notificationService
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param \App\Events\ScheduledReportReady $event
     * @return void
     */
    public function handle(ScheduledReportReady $event)
    {
        $reportName = $event->getReportName();
        $scheduleFrequency = $event->getScheduleFrequencyName();
        $fileSize = $event->getFormattedFileSize();
        $period = $event->getFormattedPeriod();

        // Prepare notification data
        $data = [
            'report_type' => $event->reportType,
            'report_name' => $reportName,
            'schedule_frequency' => $scheduleFrequency,
            'download_url' => $event->downloadUrl,
            'file_size' => $fileSize,
            'format' => strtoupper($event->format),
            'period' => $period,
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'recipient_count' => $event->recipients->count(),
        ];

        // Send notification to each recipient
        foreach ($event->recipients as $recipient) {
            $this->notificationService->sendNotification(
                $recipient,
                'scheduled_report_ready',
                [
                    'subject' => "{$scheduleFrequency} {$reportName} is Ready",
                    'body' => "Your {$scheduleFrequency} {$reportName} for {$period} is ready for download.",
                    'data' => array_merge($data, [
                        'recipient_name' => $recipient->name,
                        'recipient_email' => $recipient->email,
                    ]),
                ],
                ['email']
            );
        }

        // Notify administrators about scheduled report delivery
        $this->notifyAdmins($event, $data);
    }

    /**
     * Notify administrators about scheduled report delivery.
     *
     * @param \App\Events\ScheduledReportReady $event
     * @param array $data
     * @return void
     */
    protected function notifyAdmins(ScheduledReportReady $event, array $data)
    {
        // Get admin users (users with admin or manager roles)
        $adminUsers = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'manager', 'super_admin']);
        })->get();

        foreach ($adminUsers as $admin) {
            $this->notificationService->sendNotification(
                $admin,
                'scheduled_report_delivered',
                [
                    'subject' => "Scheduled Report Delivered: {$event->getReportName()}",
                    'body' => "Scheduled {$event->getScheduleFrequencyName()} {$event->getReportName()} has been delivered to {$event->recipients->count()} recipients.",
                    'data' => array_merge($data, [
                        'delivery_summary' => [
                            'recipient_count' => $event->recipients->count(),
                            'recipients' => $event->recipients->pluck('email')->toArray(),
                        ],
                    ]),
                ],
                ['email']
            );
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \App\Events\ScheduledReportReady $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(ScheduledReportReady $event, $exception)
    {
        // Log the failure
        \Log::error('Failed to send scheduled report notification', [
            'event' => $event,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify administrators about the failure
        $adminUsers = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'manager', 'super_admin']);
        })->get();

        foreach ($adminUsers as $admin) {
            // Use a simpler notification method since NotificationService might fail
            \Illuminate\Support\Facades\Mail::raw(
                "Failed to send scheduled report notifications. Error: {$exception->getMessage()}",
                function ($message) use ($admin, $event) {
                    $message->to($admin->email)
                        ->subject("Scheduled Report Notification Failed: {$event->getReportName()}");
                }
            );
        }
    }
}