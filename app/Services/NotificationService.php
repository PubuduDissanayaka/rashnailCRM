<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationProvider;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\Notification\Channels\NotificationChannel;
use App\Services\Notification\Channels\EmailNotificationChannel;
use App\Services\Notification\Channels\InAppNotificationChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessNotificationJob;

class NotificationService
{
    /**
     * Available channels.
     *
     * @var array<string, string>
     */
    protected $channelMap = [
        'email' => EmailNotificationChannel::class,
        'in_app' => InAppNotificationChannel::class,
    ];

    /**
     * Send a notification immediately.
     *
     * @param Notification $notification
     * @param array|null $channels
     * @return void
     */
    public function send(Notification $notification, array $channels = null): void
    {
        $channels = $channels ?? $this->getDefaultChannelsForNotification($notification);
        
        foreach ($channels as $channelName) {
            $channel = $this->resolveChannel($channelName);
            if ($channel && $this->shouldSendViaChannel($notification, $channelName)) {
                $this->sendViaChannel($notification, $channel);
            }
        }
    }

    /**
     * Queue a notification for background processing.
     *
     * @param Notification $notification
     * @param array|null $channels
     * @return void
     */
    public function queue(Notification $notification, array $channels = null): void
    {
        $channels = $channels ?? $this->getDefaultChannelsForNotification($notification);
        
        Queue::push(new ProcessNotificationJob($notification, $channels));
    }

    /**
     * Schedule a notification for future delivery.
     *
     * @param Notification $notification
     * @param \DateTimeInterface|string $scheduleAt
     * @param array|null $channels
     * @return Notification
     */
    public function schedule(Notification $notification, $scheduleAt, array $channels = null): Notification
    {
        if (is_string($scheduleAt)) {
            $scheduleAt = \Carbon\Carbon::parse($scheduleAt);
        }
        
        // Update notification with scheduled status
        $notification->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduleAt,
        ]);
        
        // Store the channels for later use
        $channels = $channels ?? $this->getDefaultChannelsForNotification($notification);
        $notification->metadata = array_merge($notification->metadata ?? [], [
            'scheduled_channels' => $channels,
        ]);
        $notification->save();
        
        // Schedule the job for processing
        Queue::later($scheduleAt, new ProcessNotificationJob($notification, $channels));
        
        return $notification;
    }

    /**
     * Schedule a notification for a specific user.
     *
     * @param User $user
     * @param string $type
     * @param array $data
     * @param \DateTimeInterface|string $scheduleAt
     * @param array|null $channels
     * @return Notification
     */
    public function scheduleForUser(User $user, string $type, array $data, $scheduleAt, array $channels = null): Notification
    {
        $notification = Notification::create([
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => $data,
            'status' => 'scheduled',
            'scheduled_at' => is_string($scheduleAt) ? \Carbon\Carbon::parse($scheduleAt) : $scheduleAt,
        ]);
        
        return $this->schedule($notification, $scheduleAt, $channels);
    }

    /**
     * Cancel a scheduled notification.
     *
     * @param string $notificationId
     * @return bool
     */
    public function cancelScheduled(string $notificationId): bool
    {
        $notification = Notification::where('uuid', $notificationId)->firstOrFail();
        
        if ($notification->status !== 'scheduled') {
            return false;
        }
        
        $notification->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        
        // Note: In a real implementation, you would also need to cancel the queued job
        // This would require storing the job ID with the notification
        
        return true;
    }

    /**
     * Get scheduled notifications.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getScheduledNotifications(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Notification::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at');
        
        if (!empty($filters['date_from'])) {
            $query->where('scheduled_at', '>=', \Carbon\Carbon::parse($filters['date_from']));
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('scheduled_at', '<=', \Carbon\Carbon::parse($filters['date_to']));
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['notifiable_type'])) {
            $query->where('notifiable_type', $filters['notifiable_type']);
        }
        
        if (!empty($filters['notifiable_id'])) {
            $query->where('notifiable_id', $filters['notifiable_id']);
        }
        
        return $query->get();
    }

    /**
     * Process scheduled notifications that are due.
     *
     * @return array
     */
    public function processDueScheduledNotifications(): array
    {
        $now = now();
        $dueNotifications = Notification::where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->get();
        
        $results = [
            'total' => $dueNotifications->count(),
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        
        foreach ($dueNotifications as $notification) {
            try {
                // Get the channels that were stored when scheduling
                $channels = $notification->metadata['scheduled_channels'] ??
                           $this->getDefaultChannelsForNotification($notification);
                
                // Update status to processing
                $notification->update(['status' => 'processing']);
                
                // Send the notification
                $this->send($notification, $channels);
                
                // Update status based on delivery results
                $logs = $notification->logs()->get();
                $overallStatus = $this->determineOverallStatus($logs);
                
                $notification->update(['status' => $overallStatus]);
                
                $results['processed']++;
                $results['successful']++;
            } catch (\Exception $e) {
                $notification->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                
                $results['processed']++;
                $results['failed']++;
                $results['errors'][] = [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Send a notification to a specific user.
     *
     * @param User $user
     * @param string $type
     * @param array $data
     * @param array|null $channels
     * @return Notification
     */
    public function sendToUser(User $user, string $type, array $data, array $channels = null): Notification
    {
        $notification = Notification::create([
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => $data,
        ]);

        $this->send($notification, $channels);

        return $notification;
    }

    /**
     * Send a notification to multiple users.
     *
     * @param Collection $users
     * @param string $type
     * @param array $data
     * @param array|null $channels
     * @return Collection
     */
    public function sendToUsers(Collection $users, string $type, array $data, array $channels = null): Collection
    {
        $notifications = collect();

        foreach ($users as $user) {
            $notification = $this->sendToUser($user, $type, $data, $channels);
            $notifications->push($notification);
        }

        return $notifications;
    }

    /**
     * Send a broadcast notification (to all users or system-wide).
     *
     * @param string $type
     * @param array $data
     * @param array|null $channels
     * @return void
     */
    public function sendBroadcast(string $type, array $data, array $channels = null): void
    {
        $users = User::all();
        $this->sendToUsers($users, $type, $data, $channels);
    }

    /**
     * Get the status of a notification.
     *
     * @param string $notificationId
     * @return array
     */
    public function getStatus(string $notificationId): array
    {
        $notification = Notification::where('uuid', $notificationId)->firstOrFail();
        
        $logs = $notification->logs()->get();
        
        $status = [
            'notification' => $notification,
            'logs' => $logs,
            'overall_status' => $this->determineOverallStatus($logs),
            'sent_count' => $logs->where('status', 'sent')->count(),
            'failed_count' => $logs->where('status', 'failed')->count(),
            'delivered_count' => $logs->where('status', 'delivered')->count(),
        ];

        return $status;
    }

    /**
     * Retry failed notifications.
     *
     * @param string $notificationId
     * @return bool
     */
    public function retryFailed(string $notificationId): bool
    {
        $notification = Notification::where('uuid', $notificationId)->firstOrFail();
        
        $failedLogs = $notification->logs()->where('status', 'failed')->get();
        
        foreach ($failedLogs as $log) {
            if ($log->canRetry()) {
                $channel = $this->resolveChannel($log->channel);
                if ($channel) {
                    $log->incrementRetry();
                    $this->sendViaChannel($notification, $channel, $log);
                }
            }
        }

        return true;
    }

    /**
     * Process a notification (used by queue job).
     *
     * @param Notification $notification
     * @param array $channels
     * @return void
     */
    public function process(Notification $notification, array $channels): void
    {
        $this->send($notification, $channels);
    }

    /**
     * Resolve a channel instance.
     *
     * @param string $channelName
     * @return NotificationChannel|null
     */
    protected function resolveChannel(string $channelName): ?NotificationChannel
    {
        $class = $this->channelMap[$channelName] ?? null;
        
        if (!$class || !class_exists($class)) {
            return null;
        }

        return app($class);
    }

    /**
     * Send a notification via a specific channel.
     *
     * @param Notification $notification
     * @param NotificationChannel $channel
     * @param NotificationLog|null $log
     * @return void
     */
    protected function sendViaChannel(Notification $notification, NotificationChannel $channel, NotificationLog $log = null): void
    {
        $notifiable = $notification->notifiable;
        
        if (!$channel->canSend($notifiable, $notification)) {
            return;
        }

        try {
            $result = $channel->send($notifiable, $notification);
            
            if ($result) {
                $this->logSuccess($notification, $channel, $log);
            } else {
                $this->logFailure($notification, $channel, 'Channel returned false', $log);
            }
        } catch (\Exception $e) {
            $this->logFailure($notification, $channel, $e->getMessage(), $log);
        }
    }

    /**
     * Determine default channels for a notification.
     *
     * @param Notification $notification
     * @return array
     */
    protected function getDefaultChannelsForNotification(Notification $notification): array
    {
        $type = $notification->type;
        $notifiable = $notification->notifiable;
        
        $channels = [];
        
        // Check user preferences
        if ($notifiable instanceof User) {
            $settings = NotificationSetting::where('user_id', $notifiable->id)
                ->where('notification_type', $type)
                ->enabled()
                ->get();
            
            $channels = $settings->pluck('channel')->toArray();
        }
        
        // Fallback to system defaults
        if (empty($channels)) {
            $settings = NotificationSetting::systemDefaults()
                ->where('notification_type', $type)
                ->enabled()
                ->get();
            
            $channels = $settings->pluck('channel')->toArray();
        }
        
        // Final fallback
        if (empty($channels)) {
            $channels = ['in_app']; // Default channel
        }
        
        return $channels;
    }

    /**
     * Check if notification should be sent via a specific channel.
     *
     * @param Notification $notification
     * @param string $channelName
     * @return bool
     */
    protected function shouldSendViaChannel(Notification $notification, string $channelName): bool
    {
        $notifiable = $notification->notifiable;
        
        if ($notifiable instanceof User) {
            $setting = NotificationSetting::where('user_id', $notifiable->id)
                ->where('notification_type', $notification->type)
                ->where('channel', $channelName)
                ->first();
            
            if ($setting) {
                return $setting->is_enabled;
            }
        }
        
        // Check system default
        $systemSetting = NotificationSetting::systemDefaults()
            ->where('notification_type', $notification->type)
            ->where('channel', $channelName)
            ->first();
        
        return $systemSetting ? $systemSetting->is_enabled : true;
    }

    /**
     * Log a successful delivery.
     *
     * @param Notification $notification
     * @param NotificationChannel $channel
     * @param NotificationLog|null $existingLog
     * @return NotificationLog
     */
    protected function logSuccess(Notification $notification, NotificationChannel $channel, NotificationLog $existingLog = null): NotificationLog
    {
        $log = $existingLog ?? new NotificationLog();
        
        $log->fill([
            'notification_id' => $notification->id,
            'channel' => $channel->getName(),
            'provider' => $channel->getProviderName(),
            'recipient' => $this->getRecipientIdentifier($notification->notifiable, $channel),
            'subject' => $channel->getSubject($notification),
            'content' => $channel->getContentPreview($notification),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        
        $log->save();
        
        return $log;
    }

    /**
     * Log a failed delivery.
     *
     * @param Notification $notification
     * @param NotificationChannel $channel
     * @param string $errorMessage
     * @param NotificationLog|null $existingLog
     * @return NotificationLog
     */
    protected function logFailure(Notification $notification, NotificationChannel $channel, string $errorMessage, NotificationLog $existingLog = null): NotificationLog
    {
        $log = $existingLog ?? new NotificationLog();
        
        $log->fill([
            'notification_id' => $notification->id,
            'channel' => $channel->getName(),
            'provider' => $channel->getProviderName(),
            'recipient' => $this->getRecipientIdentifier($notification->notifiable, $channel),
            'subject' => $channel->getSubject($notification),
            'content' => $channel->getContentPreview($notification),
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
        
        $log->save();
        
        return $log;
    }

    /**
     * Get recipient identifier for logging.
     *
     * @param mixed $notifiable
     * @param NotificationChannel $channel
     * @return string
     */
    protected function getRecipientIdentifier($notifiable, NotificationChannel $channel): string
    {
        if ($notifiable instanceof User) {
            if ($channel->getName() === 'email') {
                return $notifiable->email;
            }
            return (string) $notifiable->id;
        }
        
        return 'unknown';
    }

    /**
     * Determine overall status from logs.
     *
     * @param Collection $logs
     * @return string
     */
    protected function determineOverallStatus(Collection $logs): string
    {
        if ($logs->isEmpty()) {
            return 'pending';
        }
        
        if ($logs->where('status', 'failed')->count() === $logs->count()) {
            return 'failed';
        }
        
        if ($logs->where('status', 'delivered')->count() === $logs->count()) {
            return 'delivered';
        }
        
        if ($logs->where('status', 'sent')->count() > 0) {
            return 'partially_sent';
        }
        
        return 'processing';
    }
}