<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Models\NotificationLog;
use App\Services\NotificationService;
use App\Services\TemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BroadcastService
{
    /**
     * The notification service.
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * The template service.
     *
     * @var TemplateService
     */
    protected $templateService;

    /**
     * Create a new broadcast service instance.
     *
     * @param NotificationService $notificationService
     * @param TemplateService $templateService
     */
    public function __construct(NotificationService $notificationService, TemplateService $templateService)
    {
        $this->notificationService = $notificationService;
        $this->templateService = $templateService;
    }

    /**
     * Create a new broadcast notification.
     *
     * @param array $data
     * @return Notification
     */
    public function createBroadcast(array $data): Notification
    {
        DB::beginTransaction();

        try {
            // Prepare notification data
            $notificationData = [
                'type' => 'broadcast_' . ($data['schedule_type'] === 'scheduled' ? 'scheduled' : 'immediate'),
                'data' => [
                    'subject' => $data['subject'],
                    'message' => $data['message'],
                    'recipient_type' => $data['recipient_type'],
                    'recipients' => $data['recipients'] ?? [],
                    'roles' => $data['roles'] ?? [],
                    'departments' => $data['departments'] ?? [],
                    'channels' => $data['channels'],
                    'template_id' => $data['template_id'] ?? null,
                    'template_variables' => $data['template_variables'] ?? [],
                    'priority' => $data['priority'] ?? 'normal',
                    'created_by' => $data['created_by'],
                ],
                'status' => $data['schedule_type'] === 'scheduled' ? 'scheduled' : 'pending',
                'scheduled_at' => $data['schedule_type'] === 'scheduled' ? $data['scheduled_at'] : null,
            ];

            // Create the notification
            $notification = Notification::create($notificationData);

            // If immediate broadcast, schedule for sending
            if ($data['schedule_type'] === 'immediate') {
                // We'll send it in a separate process
                $notification->update(['status' => 'processing']);
            }

            DB::commit();

            return $notification;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create broadcast: ' . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * Send a broadcast notification.
     *
     * @param int $broadcastId
     * @return array
     */
    public function sendBroadcast(int $broadcastId): array
    {
        $broadcast = Notification::findOrFail($broadcastId);

        if ($broadcast->status !== 'processing' && $broadcast->status !== 'scheduled') {
            return [
                'success' => false,
                'message' => 'Broadcast is not in a sendable state',
                'recipient_count' => 0,
            ];
        }

        DB::beginTransaction();

        try {
            // Update broadcast status
            $broadcast->update(['status' => 'sending']);

            // Get recipients
            $recipients = $this->getRecipientsForBroadcast($broadcast);
            $recipientCount = count($recipients);

            if ($recipientCount === 0) {
                $broadcast->update(['status' => 'failed', 'data->error' => 'No recipients found']);
                DB::commit();
                return [
                    'success' => false,
                    'message' => 'No recipients found for broadcast',
                    'recipient_count' => 0,
                ];
            }

            // Prepare notification data for each recipient
            $successCount = 0;
            $failedCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    // Prepare notification data for this recipient
                    $notificationData = $this->prepareRecipientNotificationData($broadcast, $recipient);

                    // Create individual notification for the recipient
                    $userNotification = Notification::create([
                        'type' => 'broadcast_message',
                        'data' => $notificationData,
                        'notifiable_type' => User::class,
                        'notifiable_id' => $recipient->id,
                        'status' => 'pending',
                        'parent_id' => $broadcast->id,
                    ]);

                    // Send the notification
                    $this->notificationService->send($userNotification, $broadcast->data['channels']);

                    $successCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to send broadcast to recipient: ' . $e->getMessage(), [
                        'broadcast_id' => $broadcastId,
                        'recipient_id' => $recipient->id,
                    ]);
                    $failedCount++;
                }
            }

            // Update broadcast status
            $finalStatus = $failedCount === $recipientCount ? 'failed' : ($successCount > 0 ? 'sent' : 'failed');
            $broadcast->update([
                'status' => $finalStatus,
                'data->stats' => [
                    'total' => $recipientCount,
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'sent_at' => now()->toISOString(),
                ],
            ]);

            DB::commit();

            return [
                'success' => $successCount > 0,
                'message' => $successCount > 0 ? 'Broadcast sent successfully' : 'Broadcast failed to send',
                'recipient_count' => $recipientCount,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send broadcast: ' . $e->getMessage(), ['broadcast_id' => $broadcastId]);

            $broadcast->update([
                'status' => 'failed',
                'data->error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send broadcast: ' . $e->getMessage(),
                'recipient_count' => 0,
            ];
        }
    }

    /**
     * Get recipients for a broadcast.
     *
     * @param Notification $broadcast
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecipientsForBroadcast(Notification $broadcast)
    {
        $data = $broadcast->data;
        $query = User::active();

        switch ($data['recipient_type']) {
            case 'all_users':
                // All active users
                break;

            case 'selected_users':
                $query->whereIn('id', $data['recipients'] ?? []);
                break;

            case 'by_role':
                $query->whereHas('roles', function ($q) use ($data) {
                    $q->whereIn('name', $data['roles'] ?? []);
                });
                break;

            case 'by_department':
                $query->whereIn('department', $data['departments'] ?? []);
                break;
        }

        return $query->get();
    }

    /**
     * Get broadcast recipients with delivery status.
     *
     * @param int $broadcastId
     * @return array
     */
    public function getBroadcastRecipients(int $broadcastId): array
    {
        $broadcast = Notification::findOrFail($broadcastId);
        $recipients = $this->getRecipientsForBroadcast($broadcast);

        $result = [];

        foreach ($recipients as $recipient) {
            // Get delivery status for this recipient
            $logs = NotificationLog::whereHas('notification', function ($query) use ($recipient, $broadcastId) {
                $query->where('notifiable_type', User::class)
                      ->where('notifiable_id', $recipient->id)
                      ->where('parent_id', $broadcastId);
            })->get();

            $status = 'pending';
            $lastUpdate = null;
            $error = null;

            if ($logs->isNotEmpty()) {
                $latestLog = $logs->sortByDesc('created_at')->first();
                $status = $latestLog->status;
                $lastUpdate = $latestLog->updated_at;
                $error = $latestLog->error_message;
            }

            $result[] = [
                'user' => [
                    'id' => $recipient->id,
                    'name' => $recipient->name,
                    'email' => $recipient->email,
                    'role' => $recipient->role,
                    'department' => $recipient->department,
                ],
                'status' => $status,
                'last_update' => $lastUpdate,
                'error' => $error,
                'logs_count' => $logs->count(),
            ];
        }

        return $result;
    }

    /**
     * Prepare notification data for a specific recipient.
     *
     * @param Notification $broadcast
     * @param User $recipient
     * @return array
     */
    private function prepareRecipientNotificationData(Notification $broadcast, User $recipient): array
    {
        $data = $broadcast->data;
        $notificationData = [
            'subject' => $data['subject'],
            'message' => $data['message'],
            'broadcast_id' => $broadcast->id,
            'recipient_id' => $recipient->id,
        ];

        // If template is used, render it with recipient-specific variables
        if (!empty($data['template_id'])) {
            $template = EmailTemplate::find($data['template_id']);
            if ($template) {
                $variables = $this->prepareTemplateVariables($data['template_variables'] ?? [], $recipient);
                try {
                    $rendered = $this->renderTemplate($template, $variables);
                    $notificationData['subject'] = $rendered['subject'];
                    $notificationData['message'] = $rendered['body_html'];
                    $notificationData['is_template'] = true;
                    $notificationData['template_id'] = $template->id;
                } catch (\Exception $e) {
                    Log::warning('Failed to render template for broadcast: ' . $e->getMessage(), [
                        'broadcast_id' => $broadcast->id,
                        'template_id' => $data['template_id'],
                    ]);
                }
            }
        }

        return $notificationData;
    }

    /**
     * Prepare template variables for a recipient.
     *
     * @param array $baseVariables
     * @param User $recipient
     * @return array
     */
    private function prepareTemplateVariables(array $baseVariables, User $recipient): array
    {
        $variables = array_merge($baseVariables, [
            'user_name' => $recipient->name,
            'user_email' => $recipient->email,
            'user_phone' => $recipient->phone,
            'user_role' => $recipient->role,
            'user_department' => $recipient->department,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i:s'),
        ]);

        return $variables;
    }

    /**
     * Render a template with variables.
     *
     * @param EmailTemplate $template
     * @param array $variables
     * @return array
     */
    public function renderTemplate(EmailTemplate $template, array $variables): array
    {
        return $this->templateService->render($template->slug, $variables, $template->locale);
    }

    /**
     * Cancel a scheduled broadcast.
     *
     * @param int $broadcastId
     * @return bool
     */
    public function cancelBroadcast(int $broadcastId): bool
    {
        $broadcast = Notification::findOrFail($broadcastId);

        if ($broadcast->status !== 'scheduled') {
            return false;
        }

        $broadcast->update([
            'status' => 'cancelled',
            'data->cancelled_at' => now()->toISOString(),
            'data->cancelled_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Retry a failed broadcast.
     *
     * @param int $broadcastId
     * @return array
     */
    public function retryBroadcast(int $broadcastId): array
    {
        $broadcast = Notification::findOrFail($broadcastId);

        if ($broadcast->status !== 'failed') {
            return [
                'success' => false,
                'message' => 'Only failed broadcasts can be retried',
            ];
        }

        // Reset broadcast status
        $broadcast->update(['status' => 'processing']);

        // Send the broadcast again
        return $this->sendBroadcast($broadcastId);
    }

    /**
     * Process scheduled broadcasts.
     *
     * @return array
     */
    public function processScheduledBroadcasts(): array
    {
        $now = now();
        $scheduledBroadcasts = Notification::where('type', 'like', 'broadcast_%')
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->get();

        $results = [
            'total' => $scheduledBroadcasts->count(),
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($scheduledBroadcasts as $broadcast) {
            try {
                $result = $this->sendBroadcast($broadcast->id);
                
                if ($result['success']) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
                
                $results['processed']++;
            } catch (\Exception $e) {
                Log::error('Failed to process scheduled broadcast: ' . $e->getMessage(), [
                    'broadcast_id' => $broadcast->id,
                ]);
                $results['failed']++;
                $results['processed']++;
            }
        }

        return $results;
    }

    /**
     * Get broadcast statistics.
     *
     * @param array $filters
     * @return array
     */
    public function getStatistics(array $filters = []): array
    {
        $query = Notification::where('type', 'like', 'broadcast_%');

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $total = $query->count();
        $sent = $query->whereIn('status', ['sent', 'delivered'])->count();
        $failed = $query->where('status', 'failed')->count();
        $scheduled = $query->where('status', 'scheduled')->count();

        // Daily statistics for the last 30 days
        $dailyStats = Notification::where('type', 'like', 'broadcast_%')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status in ("sent", "delivered") then 1 else 0 end) as sent'),
                DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'scheduled' => $scheduled,
            'success_rate' => $total > 0 ? round($sent / $total * 100, 2) : 0,
            'daily_stats' => $dailyStats,
        ];
    }
}
