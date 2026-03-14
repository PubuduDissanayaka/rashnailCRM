<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationProvider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationStatisticsService
{
    /**
     * Get overall notification statistics.
     *
     * @param array $filters
     * @return array
     */
    public function getOverallStatistics(array $filters = []): array
    {
        $query = Notification::query();
        $logQuery = NotificationLog::query();

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from']);
            $query->whereDate('created_at', '>=', $dateFrom);
            $logQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to']);
            $query->whereDate('created_at', '<=', $dateTo);
            $logQuery->whereDate('created_at', '<=', $dateTo);
        }

        // Apply type filters
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
            $logQuery->whereHas('notification', function ($q) use ($filters) {
                $q->where('type', $filters['type']);
            });
        }

        // Apply channel filters
        if (!empty($filters['channel'])) {
            $logQuery->where('channel', $filters['channel']);
        }

        // Apply status filters
        if (!empty($filters['status'])) {
            $logQuery->where('status', $filters['status']);
        }

        $totalNotifications = $query->count();
        $totalLogs = $logQuery->count();

        // Get status distribution
        $statusDistribution = NotificationLog::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Get channel distribution
        $channelDistribution = NotificationLog::select('channel', DB::raw('count(*) as count'))
            ->groupBy('channel')
            ->get()
            ->pluck('count', 'channel')
            ->toArray();

        // Get type distribution
        $typeDistribution = Notification::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        // Calculate success rate
        $successfulLogs = $logQuery->clone()->whereIn('status', ['sent', 'delivered'])->count();
        $successRate = $totalLogs > 0 ? round(($successfulLogs / $totalLogs) * 100, 2) : 0;

        // Get daily statistics for the last 30 days
        $dailyStats = $this->getDailyStatistics(30, $filters);

        // Get top users by notification count
        $topUsers = $this->getTopUsersByNotifications(10, $filters);

        // Get provider performance
        $providerPerformance = $this->getProviderPerformance($filters);

        return [
            'total_notifications' => $totalNotifications,
            'total_logs' => $totalLogs,
            'status_distribution' => $statusDistribution,
            'channel_distribution' => $channelDistribution,
            'type_distribution' => $typeDistribution,
            'success_rate' => $successRate,
            'daily_statistics' => $dailyStats,
            'top_users' => $topUsers,
            'provider_performance' => $providerPerformance,
        ];
    }

    /**
     * Get daily statistics for a given period.
     *
     * @param int $days
     * @param array $filters
     * @return array
     */
    public function getDailyStatistics(int $days = 30, array $filters = []): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $query = NotificationLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status = "sent" then 1 else 0 end) as sent'),
                DB::raw('sum(case when status = "delivered" then 1 else 0 end) as delivered'),
                DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed'),
                DB::raw('count(distinct notification_id) as unique_notifications')
            )
            ->whereDate('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'desc');

        // Apply filters
        if (!empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }
        if (!empty($filters['type'])) {
            $query->whereHas('notification', function ($q) use ($filters) {
                $q->where('type', $filters['type']);
            });
        }

        $dailyStats = $query->get();

        // Format for chart
        $chartData = [
            'labels' => [],
            'datasets' => [
                'total' => [],
                'sent' => [],
                'delivered' => [],
                'failed' => [],
            ],
        ];

        foreach ($dailyStats as $stat) {
            $chartData['labels'][] = $stat->date;
            $chartData['datasets']['total'][] = $stat->total;
            $chartData['datasets']['sent'][] = $stat->sent;
            $chartData['datasets']['delivered'][] = $stat->delivered;
            $chartData['datasets']['failed'][] = $stat->failed;
        }

        return [
            'raw' => $dailyStats,
            'chart_data' => $chartData,
        ];
    }

    /**
     * Get top users by notification count.
     *
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getTopUsersByNotifications(int $limit = 10, array $filters = []): array
    {
        $query = User::select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('count(notifications.id) as notification_count'),
                DB::raw('count(notification_logs.id) as log_count'),
                DB::raw('sum(case when notification_logs.status in ("sent", "delivered") then 1 else 0 end) as successful_count'),
                DB::raw('sum(case when notification_logs.status = "failed" then 1 else 0 end) as failed_count')
            )
            ->leftJoin('notifications', function ($join) {
                $join->on('users.id', '=', 'notifications.notifiable_id')
                    ->where('notifications.notifiable_type', User::class);
            })
            ->leftJoin('notification_logs', 'notifications.id', '=', 'notification_logs.notification_id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('notification_count', 'desc')
            ->limit($limit);

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from']);
            $query->whereDate('notifications.created_at', '>=', $dateFrom);
        }
        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to']);
            $query->whereDate('notifications.created_at', '<=', $dateTo);
        }

        $users = $query->get();

        return $users->map(function ($user) {
            $successRate = $user->log_count > 0 
                ? round(($user->successful_count / $user->log_count) * 100, 2)
                : 0;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'notification_count' => $user->notification_count,
                'log_count' => $user->log_count,
                'successful_count' => $user->successful_count,
                'failed_count' => $user->failed_count,
                'success_rate' => $successRate,
            ];
        })->toArray();
    }

    /**
     * Get provider performance statistics.
     *
     * @param array $filters
     * @return array
     */
    public function getProviderPerformance(array $filters = []): array
    {
        $query = NotificationProvider::select(
                'notification_providers.id',
                'notification_providers.name',
                'notification_providers.channel',
                'notification_providers.provider',
                'notification_providers.is_active',
                DB::raw('count(notification_logs.id) as total_sent'),
                DB::raw('sum(case when notification_logs.status in ("sent", "delivered") then 1 else 0 end) as successful'),
                DB::raw('sum(case when notification_logs.status = "failed" then 1 else 0 end) as failed'),
                DB::raw('avg(case when notification_logs.sent_at is not null then timestampdiff(second, notification_logs.created_at, notification_logs.sent_at) else null end) as avg_delivery_time_seconds')
            )
            ->leftJoin('notification_logs', function ($join) {
                $join->on('notification_providers.channel', '=', 'notification_logs.channel')
                    ->on('notification_providers.provider', '=', 'notification_logs.provider');
            })
            ->groupBy(
                'notification_providers.id',
                'notification_providers.name',
                'notification_providers.channel',
                'notification_providers.provider',
                'notification_providers.is_active'
            )
            ->orderBy('total_sent', 'desc');

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from']);
            $query->whereDate('notification_logs.created_at', '>=', $dateFrom);
        }
        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to']);
            $query->whereDate('notification_logs.created_at', '<=', $dateTo);
        }

        $providers = $query->get();

        return $providers->map(function ($provider) {
            $successRate = $provider->total_sent > 0 
                ? round(($provider->successful / $provider->total_sent) * 100, 2)
                : 0;

            return [
                'id' => $provider->id,
                'name' => $provider->name,
                'channel' => $provider->channel,
                'provider' => $provider->provider,
                'is_active' => $provider->is_active,
                'total_sent' => $provider->total_sent,
                'successful' => $provider->successful,
                'failed' => $provider->failed,
                'success_rate' => $successRate,
                'avg_delivery_time_seconds' => $provider->avg_delivery_time_seconds,
            ];
        })->toArray();
    }

    /**
     * Get notification type performance.
     *
     * @param array $filters
     * @return array
     */
    public function getTypePerformance(array $filters = []): array
    {
        $query = Notification::select(
                'type',
                DB::raw('count(*) as total'),
                DB::raw('count(distinct notifiable_id) as unique_recipients'),
                DB::raw('avg(case when read_at is not null then 1 else 0 end) * 100 as read_rate')
            )
            ->whereNotNull('notifiable_id')
            ->groupBy('type')
            ->orderBy('total', 'desc');

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from']);
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to']);
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $types = $query->get();

        // Get delivery statistics for each type
        foreach ($types as $type) {
            $logs = NotificationLog::whereHas('notification', function ($q) use ($type) {
                $q->where('type', $type->type);
            })->get();

            $type->delivery_stats = [
                'total' => $logs->count(),
                'sent' => $logs->where('status', 'sent')->count(),
                'delivered' => $logs->where('status', 'delivered')->count(),
                'failed' => $logs->where('status', 'failed')->count(),
                'success_rate' => $logs->count() > 0 
                    ? round(($logs->whereIn('status', ['sent', 'delivered'])->count() / $logs->count()) * 100, 2)
                    : 0,
            ];
        }

        return $types->toArray();
    }

    /**
     * Get channel performance comparison.
     *
     * @param array $filters
     * @return array
     */
    public function getChannelPerformance(array $filters = []): array
    {
        $query = NotificationLog::select(
                'channel',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status = "sent" then 1 else 0 end) as sent'),
                DB::raw('sum(case when status = "delivered" then 1 else 0 end) as delivered'),
                DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed'),
                DB::raw('avg(case when sent_at is not null then timestampdiff(second, created_at, sent_at) else null end) as avg_delivery_time_seconds')
            )
            ->groupBy('channel')
            ->orderBy('total', 'desc');

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from']);
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to']);
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $channels = $query->get();

        return $channels->map(function ($channel) {
            $successRate = $channel->total > 0 
                ? round((($channel->sent + $channel->delivered) / $channel->total) * 100, 2)
                : 0;

            return [
                'channel' => $channel->channel,
                'total' => $channel->total,
                'sent' => $channel->sent,
                'delivered' => $channel->delivered,
                'failed' => $channel->failed,
                'success_rate' => $successRate,
                'avg_delivery_time_seconds' => $channel->avg_delivery_time_seconds,
            ];
        })->toArray();
    }

    /**
     * Get real-time notification statistics.
     *
     * @return array
     */
    public function getRealtimeStatistics(): array
    {
        $now = Carbon::now();
        $hourAgo = $now->copy()->subHour();
        $dayAgo = $now->copy()->subDay();
        $weekAgo = $now->copy()->subWeek();

        return [
            'last_hour' => [
                'total' => NotificationLog::where('created_at', '>=', $hourAgo)->count(),
                'sent' => NotificationLog::where('created_at', '>=', $hourAgo)->where('status', 'sent')->count(),
                'delivered' => NotificationLog::where('created_at', '>=', $hourAgo)->where('status', 'delivered')->count(),
                'failed' => NotificationLog::where('created_at', '>=', $hourAgo)->where('status', 'failed')->count(),
            ],
            'last_24_hours' => [
                'total' => NotificationLog::where('created_at', '>=', $dayAgo)->count(),
                'sent' => NotificationLog::where('created_at', '>=', $dayAgo)->where('status', 'sent')->count(),
                'delivered' => NotificationLog::where('created_at', '>=', $dayAgo)->where('status', 'delivered')->count(),
                'failed' => NotificationLog::where('created_at', '>=', $dayAgo)->where('status', 'failed')->count(),
            ],
            'last_7_days' => [
                'total' => NotificationLog::where('created_at', '>=', $weekAgo)->count(),
                'sent' => NotificationLog::where('created_at', '>=', $weekAgo)->where('status', 'sent')->count(),
                'delivered' => NotificationLog::where('created_at', '>=', $weekAgo)->where('status', 'delivered')->count(),
                'failed' => NotificationLog::where('created_at', '>=', $weekAgo)->where('status', 'failed')->count(),
            ],
            'pending_notifications' => Notification::where('status', 'pending')->count(),
            'processing_notifications' => Notification::where('status', 'processing')->count(),
            'failed_notifications' => Notification::where('status', 'failed')->count(),
        ];
    }

    /**
     * Generate notification performance report.
     *
     * @param array $filters
     * @return array
     */
    public function generatePerformanceReport(array $filters = []): array
    {
        return [
            'overall_statistics' => $this->getOverallStatistics($filters),
            'type_performance' => $this->getTypePerformance($filters),
            'channel_performance' => $this->getChannelPerformance($filters),
            'provider_performance' => $this->getProviderPerformance($filters),
            'daily_statistics' => $this->getDailyStatistics(30, $filters),
            'top_users' => $this->getTopUsersByNotifications(10, $filters),
            'realtime_statistics' => $this->getRealtimeStatistics(),
            'generated_at' => now()->toISOString(),
            'filters_applied' => $filters,
        ];
    }

    /**
     * Get notification delivery trends.
     *
     * @param string $period
     * @param array $filters
     * @return array
     */
    public function getDeliveryTrends(string $period = 'weekly', array $filters = []): array
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'daily':
                $startDate = $now->copy()->subDays(7);
                $groupFormat = '%Y-%m-%d';
                $interval = '1 DAY';
                break;
            case 'weekly':
                $startDate = $now->copy()->subWeeks(8);
                $groupFormat = '%Y-%u';
                $interval = '1 WEEK';
                break;
            case 'monthly':
                $startDate = $now->copy()->subMonths(6);
                $groupFormat = '%Y-%m';
                $interval = '1 MONTH';
                break;
            default:
                $startDate = $now->copy()->subWeeks(8);
                $groupFormat = '%Y-%u';
                $interval = '1 WEEK';
        }

        $query = NotificationLog::select(
                DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as period"),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status in ("sent", "delivered") then 1 else 0 end) as successful'),
                DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed'),
                DB::raw('avg(case when sent_at is not null then timestampdiff(second, created_at, sent_at) else null end) as avg_delivery_time')
            )
            ->whereDate('created_at', '>=', $startDate)
            ->groupBy('period')
            ->orderBy('period');

        // Apply filters
        if (!empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }
        if (!empty($filters['type'])) {
            $query->whereHas('notification', function ($q) use ($filters) {
                $q->where('type', $filters['type']);
            });
        }

        $trends = $query->get();

        // Calculate success rate for each period
        $trends = $trends->map(function ($trend) {
            $trend->success_rate = $trend->total > 0
                ? round(($trend->successful / $trend->total) * 100, 2)
                : 0;
            return $trend;
        });

        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $now->toDateString(),
            'trends' => $trends,
        ];
    }
}