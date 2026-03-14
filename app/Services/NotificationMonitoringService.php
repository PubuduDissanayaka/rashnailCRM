<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationProvider;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationMonitoringService
{
    protected $alertThresholds = [
        'error_rate' => 5.0, // Percentage
        'queue_backlog' => 1000,
        'processing_time' => 300, // Seconds
        'provider_failure_rate' => 10.0, // Percentage
        'memory_usage' => 80.0, // Percentage
    ];

    /**
     * Get comprehensive system health status
     */
    public function getSystemHealth(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'overall_status' => $this->getOverallStatus(),
            'components' => [
                'queue' => $this->getQueueHealth(),
                'providers' => $this->getProvidersHealth(),
                'database' => $this->getDatabaseHealth(),
                'cache' => $this->getCacheHealth(),
                'storage' => $this->getStorageHealth(),
            ],
            'metrics' => $this->getSystemMetrics(),
            'alerts' => $this->getActiveAlerts(),
            'recommendations' => $this->getRecommendations(),
        ];
    }

    /**
     * Get overall system status
     */
    private function getOverallStatus(): string
    {
        $components = $this->getSystemHealth()['components'];
        
        $criticalCount = 0;
        $warningCount = 0;
        
        foreach ($components as $component) {
            if ($component['status'] === 'critical') {
                $criticalCount++;
            } elseif ($component['status'] === 'warning') {
                $warningCount++;
            }
        }
        
        if ($criticalCount > 0) {
            return 'critical';
        } elseif ($warningCount > 0) {
            return 'warning';
        }
        
        return 'healthy';
    }

    /**
     * Get queue health status
     */
    private function getQueueHealth(): array
    {
        $pendingJobs = DB::table('jobs')
            ->where('queue', 'notifications')
            ->count();
        
        $failedJobs = DB::table('failed_jobs')
            ->where('queue', 'notifications')
            ->count();
        
        $oldestJob = DB::table('jobs')
            ->where('queue', 'notifications')
            ->orderBy('created_at', 'asc')
            ->first();
        
        $oldestAge = $oldestJob ? now()->diffInMinutes($oldestJob->created_at) : 0;
        
        $status = 'healthy';
        if ($pendingJobs > $this->alertThresholds['queue_backlog']) {
            $status = 'critical';
        } elseif ($pendingJobs > 500) {
            $status = 'warning';
        } elseif ($oldestAge > 30) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'metrics' => [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'oldest_job_age_minutes' => $oldestAge,
                'processing_rate' => $this->getProcessingRate(),
            ],
        ];
    }

    /**
     * Get providers health status
     */
    private function getProvidersHealth(): array
    {
        $providers = NotificationProvider::all();
        $healthyCount = 0;
        $degradedCount = 0;
        $unhealthyCount = 0;
        
        $providerDetails = [];
        
        foreach ($providers as $provider) {
            $providerHealth = $this->getProviderHealth($provider);
            $providerDetails[] = $providerHealth;
            
            switch ($providerHealth['status']) {
                case 'healthy':
                    $healthyCount++;
                    break;
                case 'degraded':
                    $degradedCount++;
                    break;
                case 'unhealthy':
                    $unhealthyCount++;
                    break;
            }
        }
        
        $status = 'healthy';
        if ($unhealthyCount > 0) {
            $status = 'critical';
        } elseif ($degradedCount > 0) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'summary' => [
                'total' => count($providers),
                'healthy' => $healthyCount,
                'degraded' => $degradedCount,
                'unhealthy' => $unhealthyCount,
            ],
            'providers' => $providerDetails,
        ];
    }

    /**
     * Get individual provider health
     */
    private function getProviderHealth(NotificationProvider $provider): array
    {
        $failureRate = $this->getProviderFailureRate($provider->id);
        
        $status = 'healthy';
        if ($failureRate > $this->alertThresholds['provider_failure_rate']) {
            $status = 'unhealthy';
        } elseif ($failureRate > 5.0) {
            $status = 'degraded';
        } elseif (!$provider->is_active) {
            $status = 'inactive';
        }
        
        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'type' => $provider->type,
            'status' => $status,
            'is_active' => $provider->is_active,
            'is_default' => $provider->is_default,
            'health_status' => $provider->health_status,
            'failure_rate' => $failureRate,
            'last_health_check' => $provider->last_health_check,
            'rate_limit' => $provider->rate_limit,
            'rate_limit_period' => $provider->rate_limit_period,
        ];
    }

    /**
     * Get provider failure rate
     */
    private function getProviderFailureRate(int $providerId): float
    {
        $startDate = now()->subHour();
        
        $totalAttempts = DB::table('notification_logs')
            ->where('context->provider_id', $providerId)
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $failedAttempts = DB::table('notification_logs')
            ->where('context->provider_id', $providerId)
            ->where('status', 'error')
            ->where('created_at', '>=', $startDate)
            ->count();
        
        if ($totalAttempts === 0) {
            return 0.0;
        }
        
        return round(($failedAttempts / $totalAttempts) * 100, 2);
    }

    /**
     * Get database health status
     */
    private function getDatabaseHealth(): array
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            
            // Check table sizes
            $notificationCount = Notification::count();
            $logCount = NotificationLog::count();
            
            // Check for long-running queries
            $longQueries = $this->checkLongRunningQueries();
            
            $status = 'healthy';
            if (!empty($longQueries)) {
                $status = 'warning';
            }
            
            return [
                'status' => $status,
                'connected' => true,
                'metrics' => [
                    'notification_count' => $notificationCount,
                    'log_count' => $logCount,
                    'long_running_queries' => count($longQueries),
                ],
                'long_queries' => $longQueries,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'connected' => false,
                'error' => $e->getMessage(),
                'metrics' => [],
            ];
        }
    }

    /**
     * Check for long-running queries
     */
    private function checkLongRunningQueries(): array
    {
        try {
            $queries = DB::select("
                SHOW PROCESSLIST
            ");
            
            $longQueries = [];
            foreach ($queries as $query) {
                if ($query->Time > 30 && $query->Command !== 'Sleep') { // More than 30 seconds
                    $longQueries[] = [
                        'id' => $query->Id,
                        'user' => $query->User,
                        'host' => $query->Host,
                        'db' => $query->db,
                        'command' => $query->Command,
                        'time' => $query->Time,
                        'state' => $query->State,
                        'info' => $query->Info,
                    ];
                }
            }
            
            return $longQueries;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get cache health status
     */
    private function getCacheHealth(): array
    {
        try {
            // Test cache connection
            Cache::put('health_check', 'ok', 10);
            $value = Cache::get('health_check');
            
            $status = ($value === 'ok') ? 'healthy' : 'warning';
            
            // Get cache statistics if using Redis
            $stats = [];
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                $stats = $redis->info();
            }
            
            return [
                'status' => $status,
                'connected' => true,
                'driver' => config('cache.default'),
                'stats' => $stats,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'connected' => false,
                'error' => $e->getMessage(),
                'driver' => config('cache.default'),
            ];
        }
    }

    /**
     * Get storage health status
     */
    private function getStorageHealth(): array
    {
        $storagePath = storage_path();
        $totalSpace = disk_total_space($storagePath);
        $freeSpace = disk_free_space($storagePath);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercentage = ($totalSpace > 0) ? ($usedSpace / $totalSpace) * 100 : 0;
        
        $status = 'healthy';
        if ($usagePercentage > 90) {
            $status = 'critical';
        } elseif ($usagePercentage > 80) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'metrics' => [
                'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'used_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
                'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'usage_percentage' => round($usagePercentage, 2),
            ],
            'paths' => [
                'storage' => $storagePath,
                'logs' => storage_path('logs'),
                'cache' => storage_path('framework/cache'),
            ],
        ];
    }

    /**
     * Get system metrics
     */
    private function getSystemMetrics(): array
    {
        $startDate = now()->subHour();
        
        $totalNotifications = Notification::where('created_at', '>=', $startDate)->count();
        $sentNotifications = Notification::where('status', 'sent')
            ->where('created_at', '>=', $startDate)
            ->count();
        $failedNotifications = Notification::where('status', 'failed')
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $errorRate = ($totalNotifications > 0) ? ($failedNotifications / $totalNotifications) * 100 : 0;
        
        // Get average processing time
        $avgProcessingTime = DB::table('notifications')
            ->where('status', 'sent')
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('sent_at')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, sent_at)) as avg_time')
            ->first();
        
        return [
            'notifications' => [
                'total_last_hour' => $totalNotifications,
                'sent_last_hour' => $sentNotifications,
                'failed_last_hour' => $failedNotifications,
                'error_rate' => round($errorRate, 2),
                'success_rate' => round(100 - $errorRate, 2),
            ],
            'performance' => [
                'avg_processing_time_seconds' => round($avgProcessingTime->avg_time ?? 0, 2),
                'processing_rate_per_minute' => $this->getProcessingRate(),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_usage_percentage' => $this->getMemoryUsagePercentage(),
            ],
            'timestamps' => [
                'current' => now()->toISOString(),
                'hour_start' => $startDate->toISOString(),
            ],
        ];
    }

    /**
     * Get processing rate (notifications per minute)
     */
    private function getProcessingRate(): float
    {
        $startDate = now()->subMinutes(5);
        
        $processedCount = Notification::where('status', 'sent')
            ->where('sent_at', '>=', $startDate)
            ->count();
        
        return round($processedCount / 5, 2); // Per minute
    }

    /**
     * Get memory usage percentage
     */
    private function getMemoryUsagePercentage(): float
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        
        if ($memoryLimit === '-1') {
            return 0.0;
        }
        
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        
        if ($memoryLimitBytes === 0) {
            return 0.0;
        }
        
        return round(($memoryUsage / $memoryLimitBytes) * 100, 2);
    }

    /**
     * Convert memory string to bytes
     */
    private function convertToBytes(string $memory): int
    {
        $unit = strtolower(substr($memory, -1));
        $value = (int) substr($memory, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $memory;
        }
    }

    /**
     * Get active alerts
     */
    private function getActiveAlerts(): array
    {
        $alerts = [];
        
        // Check error rate
        $errorRate = $this->getSystemMetrics()['notifications']['error_rate'];
        if ($errorRate > $this->alertThresholds['error_rate']) {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'high_error_rate',
                'message' => "High error rate detected: {$errorRate}%",
                'threshold' => $this->alertThresholds['error_rate'],
                'current' => $errorRate,
                'timestamp' => now()->toISOString(),
            ];
        }
        
        // Check queue backlog
        $queueHealth = $this->getQueueHealth();
        if ($queueHealth['status'] === 'critical') {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'queue_backlog',
                'message' => "High queue backlog: {$queueHealth['metrics']['pending_jobs']} jobs",
                'threshold' => $this->alertThresholds['queue_backlog'],
                'current' => $queueHealth['metrics']['pending_jobs'],
                'timestamp' => now()->toISOString(),
            ];
        }
        
        // Check provider health
        $providersHealth = $this->getProvidersHealth();
        foreach ($providersHealth['providers'] as $provider) {
            if ($provider['status'] === 'unhealthy') {
                $alerts[] = [
                    'level' => 'critical',
                    'type' => 'provider_unhealthy',
                    'message' => "Provider '{$provider['name']}' is unhealthy",
                    'provider' => $provider['name'],
                    'failure_rate' => $provider['failure_rate'],
                    'timestamp' => now()->toISOString(),
                ];
            }
        }
        
        // Check storage
        $storageHealth = $this->getStorageHealth();
        if ($storageHealth['status'] === 'critical') {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'storage_critical',
                'message' => "Storage usage critical: {$storageHealth['metrics']['usage_percentage']}%",
                'threshold' => 90,
                'current' => $storageHealth['metrics']['usage_percentage'],
                'timestamp' => now()->toISOString(),
            ];
        }
        
        return $alerts;
    }

    /**
     * Get recommendations based on current state
     */
    private function getRecommendations(): array
    {
        $recommendations = [];
        
        $queueHealth = $this->getQueueHealth();
        if ($queueHealth['metrics']['pending_jobs'] > 500) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Increase queue workers',
                'reason' => "High queue backlog: {$queueHealth['metrics']['pending_jobs']} pending jobs",
                'suggestion' => 'Add more queue workers or optimize processing',
            ];
        }
        
        $errorRate = $this->getSystemMetrics()['notifications']['error_rate'];
        if ($errorRate > 2.0) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Investigate error rate',
                'reason' => "Elevated error rate: {$errorRate}%",
                'suggestion' => 'Check provider health and error logs',
            ];
        }
        
        $storageHealth = $this->getStorageHealth();
        if ($storageHealth['metrics']['usage_percentage'] > 70) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Clean up storage',
                'reason' => "High storage usage: {$storageHealth['metrics']['usage_percentage']}%",
                'suggestion' => 'Clean up old logs and attachments',
            ];
        }
        
        // Check memory usage
        $memoryUsage = $this->getMemoryUsagePercentage();
        if ($memoryUsage > $this->alertThresholds['memory_usage']) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Optimize memory usage',
                'reason' => "High memory usage: {$memoryUsage}%",
                'suggestion' => 'Optimize code or increase memory limit',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Send alerts to configured channels
     */
    public function sendAlerts(array $alerts): void
    {
        foreach ($alerts as $alert) {
            $this->sendAlertToChannels($alert);
        }
    }

    /**
     * Send alert to configured channels
     */
    private function sendAlertToChannels(array $alert): void
    {
        $channels = config('notifications.alert_channels', ['log']);
        
        foreach ($channels as $channel) {
            switch ($channel) {
                case 'log':
                    $this->logAlert($alert);
                    break;
                case 'email':
                    $this->sendEmailAlert($alert);
                    break;
                case 'slack':
                    $this->sendSlackAlert($alert);
                    break;
                case 'webhook':
                    $this->sendWebhookAlert($alert);
                    break;
            }
        }
    }

    /**
     * Log alert to file
     */
    private function logAlert(array $alert): void
    {
        $level = strtoupper($alert['level']);
        Log::{$alert['level']}("Notification System Alert: {$alert['message']}", $alert);
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(array $alert): void
    {
        // Implement email alerting
        // $this->mailer->sendAlert($alert);
    }

    /**
     * Send Slack alert
     */
    private function sendSlackAlert(array $alert): void
    {
        // Implement Slack alerting
        // $this->slack->sendAlert($alert);
    }

    /**
     * Send webhook alert
     */
    private function sendWebhookAlert(array $alert): void
    {
        // Implement webhook alerting
        // $this->webhook->sendAlert($alert);
    }

    /**
     * Get historical health data
     */
    public function getHistoricalHealth(string $period = 'day'): array
    {
        $cacheKey = "historical_health:{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($period) {
            $startDate = match($period) {
                'hour' => now()->subHour(),
                'day' => now()->subDay(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                default => now()->subDay(),
            };
            
            // This would typically query a time-series database
            // For now, return sample data structure
            return [
                'period' => $period,
                'start_date' => $startDate->toISOString(),
                'end_date' => now()->toISOString(),
                'data_points' => [],
                'summary' => [
                    'avg_error_rate' => 0.0,
                    'max_queue_backlog' => 0,
                    'downtime_minutes' => 0,
                ],
            ];
        });
    }

    /**
     * Get notification delivery statistics
     */
    public function getDeliveryStatistics(string $period = 'day'): array
    {
        $startDate = match($period) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subDay(),
        };
        
        $stats = DB::table('notifications')
            ->selectRaw('
                DATE(created_at) as date,
                HOUR(created_at) as hour,
                notification_type,
                status,
                COUNT(*) as count
            ')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date', 'hour', 'notification_type', 'status')
            ->orderBy('date', 'desc')
            ->orderBy('hour', 'desc')
            ->get()
            ->groupBy(['date', 'hour'])
            ->map(function ($hourGroup) {
                return $hourGroup->groupBy('notification_type')
                    ->map(function ($typeGroup) {
                        $total = $typeGroup->sum('count');
                        $sent = $typeGroup->where('status', 'sent')->sum('count');
                        $failed = $typeGroup->where('status', 'failed')->sum('count');
                        
                        return [
                            'total' => $total,
                            'sent' => $sent,
                            'failed' => $failed,
                            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
                        ];
                    });
            });
        
        return [
            'period' => $period,
            'start_date' => $startDate->toISOString(),
            'end_date' => now()->toISOString(),
            'statistics' => $stats,
            'summary' => $this->calculateDeliverySummary($stats),
        ];
    }

    /**
     * Calculate delivery summary from statistics
     */
    private function calculateDeliverySummary($stats): array
    {
        $total = 0;
        $sent = 0;
        $failed = 0;
        
        foreach ($stats as $hourGroup) {
            foreach ($hourGroup as $typeStats) {
                $total += $typeStats['total'];
                $sent += $typeStats['sent'];
                $failed += $typeStats['failed'];
            }
        }
        
        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Run health check and return results
     */
    public function runHealthCheck(): array
    {
        $startTime = microtime(true);
        
        $health = $this->getSystemHealth();
        
        $processingTime = microtime(true) - $startTime;
        
        // Log health check
        Log::info('Notification system health check completed', [
            'overall_status' => $health['overall_status'],
            'processing_time' => $processingTime,
            'alerts_count' => count($health['alerts']),
        ]);
        
        // Send alerts if any
        if (!empty($health['alerts'])) {
            $this->sendAlerts($health['alerts']);
        }
        
        return array_merge($health, [
            'health_check' => [
                'processing_time' => $processingTime,
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Schedule regular health checks
     */
    public function scheduleHealthChecks(): void
    {
        // This would typically be called from a scheduled command
        // Example: php artisan notifications:health-check
        
        Cache::put('last_health_check', now()->toISOString(), 300);
    }
}