<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationSetting;
use App\Models\EmailTemplate;
use App\Models\NotificationProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NotificationCacheService
{
    const CACHE_TTL = 3600; // 1 hour
    const USER_SETTINGS_TTL = 1800; // 30 minutes
    const TEMPLATES_TTL = 7200; // 2 hours
    const PROVIDERS_TTL = 3600; // 1 hour

    /**
     * Get user notification settings with caching
     */
    public function getUserSettings(int $userId, string $notificationType = null): array
    {
        $cacheKey = "user_notification_settings:{$userId}";
        
        return Cache::remember($cacheKey, self::USER_SETTINGS_TTL, function () use ($userId, $notificationType) {
            $query = NotificationSetting::where('user_id', $userId);
            
            if ($notificationType) {
                $query->where('notification_type', $notificationType);
            }
            
            return $query->get()->keyBy(function ($setting) {
                return "{$setting->notification_type}:{$setting->channel}";
            })->toArray();
        });
    }

    /**
     * Get system default notification settings with caching
     */
    public function getSystemDefaults(string $notificationType = null): array
    {
        $cacheKey = 'system_notification_defaults';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($notificationType) {
            $query = NotificationSetting::whereNull('user_id');
            
            if ($notificationType) {
                $query->where('notification_type', $notificationType);
            }
            
            return $query->get()->keyBy(function ($setting) {
                return "{$setting->notification_type}:{$setting->channel}";
            })->toArray();
        });
    }

    /**
     * Get email template with caching
     */
    public function getEmailTemplate(string $notificationType, string $language = 'en'): ?array
    {
        $cacheKey = "email_template:{$notificationType}:{$language}";
        
        return Cache::remember($cacheKey, self::TEMPLATES_TTL, function () use ($notificationType, $language) {
            $template = EmailTemplate::where('notification_type', $notificationType)
                ->where('language', $language)
                ->where('is_active', true)
                ->orderBy('version', 'desc')
                ->first();
            
            return $template ? $template->toArray() : null;
        });
    }

    /**
     * Get active notification providers with caching
     */
    public function getActiveProviders(string $type = null): array
    {
        $cacheKey = 'active_notification_providers';
        
        return Cache::remember($cacheKey, self::PROVIDERS_TTL, function () use ($type) {
            $query = NotificationProvider::where('is_active', true)
                ->orderBy('is_default', 'desc')
                ->orderBy('priority', 'asc');
            
            if ($type) {
                $query->where('type', $type);
            }
            
            return $query->get()->toArray();
        });
    }

    /**
     * Get notification statistics with caching
     */
    public function getNotificationStatistics(string $period = 'today'): array
    {
        $cacheKey = "notification_stats:{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($period) { // 5 minutes TTL for stats
            $startDate = match($period) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                'year' => now()->startOfYear(),
                default => now()->subDay(),
            };
            
            return [
                'total' => DB::table('notifications')
                    ->where('created_at', '>=', $startDate)
                    ->count(),
                'sent' => DB::table('notifications')
                    ->where('status', 'sent')
                    ->where('created_at', '>=', $startDate)
                    ->count(),
                'failed' => DB::table('notifications')
                    ->where('status', 'failed')
                    ->where('created_at', '>=', $startDate)
                    ->count(),
                'pending' => DB::table('notifications')
                    ->where('status', 'pending')
                    ->where('created_at', '>=', $startDate)
                    ->count(),
                'success_rate' => DB::table('notifications')
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count
                    ')
                    ->where('created_at', '>=', $startDate)
                    ->first(),
            ];
        });
    }

    /**
     * Clear cache for a specific user
     */
    public function clearUserCache(int $userId): void
    {
        Cache::forget("user_notification_settings:{$userId}");
    }

    /**
     * Clear system defaults cache
     */
    public function clearSystemDefaultsCache(): void
    {
        Cache::forget('system_notification_defaults');
    }

    /**
     * Clear template cache
     */
    public function clearTemplateCache(string $notificationType, string $language = 'en'): void
    {
        Cache::forget("email_template:{$notificationType}:{$language}");
    }

    /**
     * Clear providers cache
     */
    public function clearProvidersCache(): void
    {
        Cache::forget('active_notification_providers');
    }

    /**
     * Clear all notification caches
     */
    public function clearAllCaches(): void
    {
        Cache::forget('system_notification_defaults');
        Cache::forget('active_notification_providers');
        
        // Clear user settings cache pattern
        Cache::forget('user_notification_settings:*');
        
        // Clear template cache pattern
        Cache::forget('email_template:*');
        
        // Clear statistics cache
        Cache::forget('notification_stats:*');
    }

    /**
     * Batch process notifications with optimized queries
     */
    public function batchProcessNotifications(array $notificationIds, int $batchSize = 100): array
    {
        $results = [];
        
        // Process in batches to avoid memory issues
        foreach (array_chunk($notificationIds, $batchSize) as $batch) {
            $notifications = DB::table('notifications')
                ->whereIn('id', $batch)
                ->select(['id', 'user_id', 'notification_type', 'channels', 'status'])
                ->get();
            
            // Group by user for batch processing
            $groupedByUser = $notifications->groupBy('user_id');
            
            foreach ($groupedByUser as $userId => $userNotifications) {
                // Get user settings once per batch
                $userSettings = $this->getUserSettings($userId);
                
                foreach ($userNotifications as $notification) {
                    // Process notification with cached settings
                    $results[] = $this->processSingleNotification($notification, $userSettings);
                }
            }
        }
        
        return $results;
    }

    /**
     * Process single notification with cached data
     */
    private function processSingleNotification($notification, array $userSettings): array
    {
        // Implementation would go here
        return [
            'id' => $notification->id,
            'processed' => true,
            'timestamp' => now(),
        ];
    }

    /**
     * Optimized query for getting user notification preferences
     */
    public function getOptimizedUserPreferences(int $userId): array
    {
        return DB::table('notification_settings')
            ->select([
                'notification_type',
                'channel',
                'is_enabled',
                'preferences',
                DB::raw('CASE WHEN user_id IS NULL THEN "system" ELSE "user" END as source')
            ])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereNull('user_id');
            })
            ->orderBy('source', 'asc') // User settings first, then system defaults
            ->get()
            ->groupBy('notification_type')
            ->map(function ($settings) {
                return $settings->keyBy('channel');
            })
            ->toArray();
    }

    /**
     * Get notification delivery rates with caching
     */
    public function getDeliveryRates(string $channel = null, string $period = 'week'): array
    {
        $cacheKey = "delivery_rates:{$channel}:{$period}";
        
        return Cache::remember($cacheKey, 600, function () use ($channel, $period) { // 10 minutes TTL
            $startDate = match($period) {
                'day' => now()->subDay(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                'quarter' => now()->subMonths(3),
                default => now()->subWeek(),
            };
            
            $query = DB::table('notifications')
                ->where('created_at', '>=', $startDate);
            
            if ($channel) {
                $query->whereJsonContains('channels', $channel);
            }
            
            return $query->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                ROUND(SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as success_rate
            ')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
        });
    }
}