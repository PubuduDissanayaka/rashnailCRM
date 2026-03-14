<?php

namespace App\Listeners;

use App\Events\ReportGenerationFailed;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendReportGenerationFailedNotification implements ShouldQueue
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
     * @param \App\Events\ReportGenerationFailed $event
     * @return void
     */
    public function handle(ReportGenerationFailed $event)
    {
        $reportName = $event->getReportName();
        $errorDetails = $event->getFormattedErrorDetails();

        // Prepare notification data
        $data = [
            'report_type' => $event->reportType,
            'report_name' => $reportName,
            'error_message' => $event->errorMessage,
            'error_code' => $event->errorCode,
            'error_details' => $errorDetails,
            'failed_at' => $event->failedAt->format('Y-m-d H:i:s'),
            'generated_by' => $event->generatedBy ? $event->generatedBy->name : 'System',
            'generated_by_email' => $event->generatedBy ? $event->generatedBy->email : null,
        ];

        // Notify the user who attempted to generate the report
        if ($event->generatedBy) {
            $this->notificationService->sendNotification(
                $event->generatedBy,
                'report_generation_failed',
                [
                    'subject' => "Failed to Generate {$reportName}",
                    'body' => "We encountered an error while generating your {$reportName}. Please try again or contact support.",
                    'data' => $data,
                ],
                ['email', 'in_app']
            );
        }

        // Always notify administrators about report generation failures
        $this->notifyAdmins($event, $data);
    }

    /**
     * Notify administrators about report generation failure.
     *
     * @param \App\Events\ReportGenerationFailed $event
     * @param array $data
     * @return void
     */
    protected function notifyAdmins(ReportGenerationFailed $event, array $data)
    {
        // Get admin users (users with admin or manager roles)
        $adminUsers = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'manager', 'super_admin']);
        })->get();

        foreach ($adminUsers as $admin) {
            // Skip if it's the same user who attempted to generate the report
            if ($event->generatedBy && $admin->id === $event->generatedBy->id) {
                continue;
            }

            $this->notificationService->sendNotification(
                $admin,
                'report_generation_failed_admin',
                [
                    'subject' => "Report Generation Failed: {$event->getReportName()}",
                    'body' => "Failed to generate {$event->getReportName()}. Error: {$event->errorMessage}",
                    'data' => array_merge($data, [
                        'requires_attention' => true,
                        'priority' => 'high',
                    ]),
                ],
                ['email']
            );
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \App\Events\ReportGenerationFailed $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(ReportGenerationFailed $event, $exception)
    {
        // Log the failure
        \Log::error('Failed to send report generation failed notification', [
            'event' => $event,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}