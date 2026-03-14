<?php

namespace App\Listeners;

use App\Events\ReportGenerated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendReportGeneratedNotification implements ShouldQueue
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
     * @param \App\Events\ReportGenerated $event
     * @return void
     */
    public function handle(ReportGenerated $event)
    {
        $user = $event->generatedBy;
        $reportName = $event->getReportName();
        $fileSize = $event->getFormattedFileSize();
        $format = strtoupper($event->format);

        // Prepare notification data
        $data = [
            'report_type' => $event->reportType,
            'report_name' => $reportName,
            'generated_by' => $user->name,
            'generated_by_email' => $user->email,
            'download_url' => $event->downloadUrl,
            'file_size' => $fileSize,
            'format' => $format,
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'file_path' => $event->filePath,
        ];

        // Send notification to the user who generated the report
        $this->notificationService->sendNotification(
            $user,
            'report_generated',
            [
                'subject' => "Your {$reportName} is Ready",
                'body' => "Your {$reportName} has been generated successfully and is ready for download.",
                'data' => $data,
            ],
            ['email', 'in_app']
        );

        // Also send to admins/managers if configured
        $this->notifyAdmins($event, $data);
    }

    /**
     * Notify administrators about report generation.
     *
     * @param \App\Events\ReportGenerated $event
     * @param array $data
     * @return void
     */
    protected function notifyAdmins(ReportGenerated $event, array $data)
    {
        // Get admin users (users with admin or manager roles)
        $adminUsers = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'manager', 'super_admin']);
        })->get();

        foreach ($adminUsers as $admin) {
            // Skip if it's the same user who generated the report
            if ($admin->id === $event->generatedBy->id) {
                continue;
            }

            $this->notificationService->sendNotification(
                $admin,
                'report_generated_admin',
                [
                    'subject' => "Report Generated: {$event->getReportName()}",
                    'body' => "{$event->generatedBy->name} has generated a {$event->getReportName()}.",
                    'data' => array_merge($data, [
                        'generated_by_user' => $event->generatedBy->name,
                        'generated_by_email' => $event->generatedBy->email,
                    ]),
                ],
                ['email']
            );
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \App\Events\ReportGenerated $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(ReportGenerated $event, $exception)
    {
        // Log the failure
        \Log::error('Failed to send report generated notification', [
            'event' => $event,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}