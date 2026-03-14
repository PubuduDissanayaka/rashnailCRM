<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\NotificationSetting;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessNotificationJob;

class NotificationBatchService
{
    protected $cacheService;
    protected $notificationService;

    public function __construct(
        NotificationCacheService $cacheService,
        NotificationService $notificationService
    ) {
        $this->cacheService = $cacheService;
        $this->notificationService = $notificationService;
    }

    /**
     * Send bulk notifications to multiple users efficiently
     */
    public function sendBulkNotifications(
        array $userIds,
        string $notificationType,
        array $variables = [],
        array $channels = ['email', 'in_app'],
        array $options = []
    ): array {
        $batchId = uniqid('batch_');
        $results = [
            'batch_id' => $batchId,
            'total_users' => count($userIds),
            'successful' => 0,
            'failed' => 0,
            'notifications' => [],
        ];

        // Process in batches to avoid memory issues
        $batchSize = $options['batch_size'] ?? 100;
        $userBatches = array_chunk($userIds, $batchSize);

        foreach ($userBatches as $batchIndex => $userBatch) {
            $batchResults = $this->processUserBatch(
                $userBatch,
                $notificationType,
                $variables,
                $channels,
                $options,
                $batchId
            );

            $results['successful'] += $batchResults['successful'];
            $results['failed'] += $batchResults['failed'];
            $results['notifications'] = array_merge(
                $results['notifications'],
                $batchResults['notifications']
            );
        }

        // Cache batch results for later retrieval
        Cache::put("notification_batch:{$batchId}", $results, 3600);

        return $results;
    }

    /**
     * Process a batch of users
     */
    private function processUserBatch(
        array $userIds,
        string $notificationType,
        array $variables,
        array $channels,
        array $options,
        string $batchId
    ): array {
        $results = [
            'successful' => 0,
            'failed' => 0,
            'notifications' => [],
        ];

        // Get all users in batch with their settings
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        
        // Get system defaults once
        $systemDefaults = $this->cacheService->getSystemDefaults($notificationType);
        
        // Get template once
        $template = $this->cacheService->getEmailTemplate($notificationType);

        foreach ($userIds as $userId) {
            try {
                $user = $users[$userId] ?? null;
                if (!$user) {
                    $results['failed']++;
                    continue;
                }

                // Get user settings from cache
                $userSettings = $this->cacheService->getUserSettings($userId, $notificationType);

                // Determine which channels are enabled for this user
                $enabledChannels = $this->getEnabledChannelsForUser(
                    $notificationType,
                    $channels,
                    $userSettings,
                    $systemDefaults
                );

                if (empty($enabledChannels)) {
                    // User has disabled all channels for this notification type
                    $results['failed']++;
                    continue;
                }

                // Create notification
                $notification = Notification::create([
                    'user_id' => $userId,
                    'notification_type' => $notificationType,
                    'subject' => $this->renderTemplate($template['subject'] ?? '', $variables + ['name' => $user->name]),
                    'content' => $this->renderTemplate($template['content'] ?? '', $variables + ['name' => $user->name]),
                    'channels' => $enabledChannels,
                    'status' => 'pending',
                    'metadata' => [
                        'variables' => $variables,
                        'batch_id' => $batchId,
                        'template_id' => $template['id'] ?? null,
                    ],
                    'scheduled_at' => $options['scheduled_at'] ?? null,
                ]);

                // Dispatch job for processing
                if (!($options['delay_processing'] ?? false)) {
                    Queue::push(new ProcessNotificationJob($notification));
                }

                $results['successful']++;
                $results['notifications'][] = [
                    'id' => $notification->id,
                    'user_id' => $userId,
                    'channels' => $enabledChannels,
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                // Log error
                \Log::error("Failed to create notification for user {$userId}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Get enabled channels for a user based on settings
     */
    private function getEnabledChannelsForUser(
        string $notificationType,
        array $requestedChannels,
        array $userSettings,
        array $systemDefaults
    ): array {
        $enabledChannels = [];

        foreach ($requestedChannels as $channel) {
            $userSetting = $userSettings["{$notificationType}:{$channel}"] ?? null;
            $systemSetting = $systemDefaults["{$notificationType}:{$channel}"] ?? null;

            // Check user setting first, fall back to system default
            if ($userSetting) {
                if ($userSetting['is_enabled']) {
                    $enabledChannels[] = $channel;
                }
            } elseif ($systemSetting && $systemSetting['is_enabled']) {
                $enabledChannels[] = $channel;
            }
        }

        return $enabledChannels;
    }

    /**
     * Render template with variables
     */
    private function renderTemplate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{{$key}}}", $value, $template);
        }
        return $template;
    }

    /**
     * Process pending notifications in batches
     */
    public function processPendingNotifications(int $limit = 1000): array
    {
        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        // Get pending notifications in batches
        Notification::where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('created_at', 'asc')
            ->chunk(100, function ($notifications) use (&$results) {
                foreach ($notifications as $notification) {
                    try {
                        // Process notification
                        $processed = $this->notificationService->processNotification($notification);
                        
                        if ($processed) {
                            $results['sent']++;
                        } else {
                            $results['failed']++;
                        }
                        $results['processed']++;
                    } catch (\Exception $e) {
                        $results['failed']++;
                        \Log::error("Failed to process notification {$notification->id}: " . $e->getMessage());
                    }
                }
            });

        return $results;
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $days = 30): array
    {
        $deletedCount = Notification::where('created_at', '<', now()->subDays($days))
            ->whereIn('status', ['sent', 'failed', 'delivered'])
            ->delete();

        // Also clean up read notifications older than 7 days
        $deletedReadCount = Notification::where('read_at', '<', now()->subDays(7))
            ->whereNotNull('read_at')
            ->delete();

        // Clear relevant caches
        $this->cacheService->clearAllCaches();

        return [
            'deleted_old' => $deletedCount,
            'deleted_read' => $deletedReadCount,
            'total' => $deletedCount + $deletedReadCount,
        ];
    }

    /**
     * Retry failed notifications in batches
     */
    public function retryFailedNotifications(int $maxRetries = 3): array
    {
        $results = [
            'retried' => 0,
            'successful' => 0,
            'failed' => 0,
        ];

        Notification::where('status', 'failed')
            ->where('retry_count', '<', $maxRetries)
            ->where('last_attempt_at', '<', now()->subMinutes(5)) // Wait 5 minutes between retries
            ->chunk(100, function ($notifications) use (&$results) {
                foreach ($notifications as $notification) {
                    try {
                        $retried = $this->notificationService->retryNotification($notification->id);
                        
                        if ($retried) {
                            $results['retried']++;
                            $results['successful']++;
                        } else {
                            $results['failed']++;
                        }
                    } catch (\Exception $e) {
                        $results['failed']++;
                        \Log::error("Failed to retry notification {$notification->id}: " . $e->getMessage());
                    }
                }
            });

        return $results;
    }

    /**
     * Get batch processing statistics
     */
    public function getBatchStatistics(string $batchId = null): array
    {
        if ($batchId) {
            // Get specific batch stats
            $batchData = Cache::get("notification_batch:{$batchId}");
            
            if (!$batchData) {
                // Try to reconstruct from database
                $batchData = $this->reconstructBatchData($batchId);
            }
            
            return $batchData ?? [
                'batch_id' => $batchId,
                'error' => 'Batch not found',
            ];
        }

        // Get overall statistics
        return [
            'pending_batches' => Cache::get('pending_batch_count', 0),
            'today_processed' => Notification::whereDate('created_at', today())->count(),
            'today_sent' => Notification::whereDate('created_at', today())
                ->where('status', 'sent')
                ->count(),
            'today_failed' => Notification::whereDate('created_at', today())
                ->where('status', 'failed')
                ->count(),
            'queue_size' => DB::table('jobs')->where('queue', 'notifications')->count(),
        ];
    }

    /**
     * Reconstruct batch data from database
     */
    private function reconstructBatchData(string $batchId): ?array
    {
        $notifications = Notification::where('metadata->batch_id', $batchId)->get();
        
        if ($notifications->isEmpty()) {
            return null;
        }

        return [
            'batch_id' => $batchId,
            'total_users' => $notifications->count(),
            'successful' => $notifications->where('status', '!=', 'failed')->count(),
            'failed' => $notifications->where('status', 'failed')->count(),
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'user_id' => $notification->user_id,
                    'status' => $notification->status,
                    'channels' => $notification->channels,
                ];
            })->toArray(),
        ];
    }

    /**
     * Optimize database indexes for notification queries
     */
    public function optimizeDatabaseIndexes(): array
    {
        $results = [];

        // Check existing indexes
        $indexes = DB::select("
            SHOW INDEXES FROM notifications
            WHERE Key_name NOT LIKE 'PRIMARY'
        ");

        $results['existing_indexes'] = count($indexes);

        // Add missing indexes if needed
        $missingIndexes = $this->identifyMissingIndexes();
        
        foreach ($missingIndexes as $index) {
            try {
                DB::statement("CREATE INDEX {$index['name']} ON notifications ({$index['columns']})");
                $results['added_indexes'][] = $index['name'];
            } catch (\Exception $e) {
                $results['errors'][] = "Failed to create index {$index['name']}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Identify missing indexes for common queries
     */
    private function identifyMissingIndexes(): array
    {
        return [
            [
                'name' => 'idx_notifications_user_status',
                'columns' => 'user_id, status, created_at',
                'purpose' => 'Speed up user notification queries',
            ],
            [
                'name' => 'idx_notifications_type_status',
                'columns' => 'notification_type, status, scheduled_at',
                'purpose' => 'Speed up notification type queries',
            ],
            [
                'name' => 'idx_notifications_created_at',
                'columns' => 'created_at',
                'purpose' => 'Speed up date-based queries',
            ],
        ];
    }
}