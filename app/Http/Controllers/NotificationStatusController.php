<?php

namespace App\Http\Controllers;

use App\Services\NotificationMonitoringService;
use App\Services\NotificationErrorHandler;
use App\Services\QueueMonitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationStatusController extends Controller
{
    protected $monitoringService;
    protected $errorHandler;
    protected $queueMonitor;

    public function __construct(
        NotificationMonitoringService $monitoringService,
        NotificationErrorHandler $errorHandler,
        QueueMonitorService $queueMonitor
    ) {
        $this->monitoringService = $monitoringService;
        $this->errorHandler = $errorHandler;
        $this->queueMonitor = $queueMonitor;
        
        $this->middleware('auth');
        $this->middleware('can:manage system')->except(['index', 'health']);
    }

    /**
     * Display notification system status dashboard
     */
    public function index(Request $request)
    {
        $health = $this->monitoringService->getSystemHealth();
        $errorStats = $this->errorHandler->getErrorStatistics('day');
        $queueStats = $this->queueMonitor->getQueueStatistics();
        
        if ($request->expectsJson()) {
            return response()->json([
                'health' => $health,
                'error_statistics' => $errorStats,
                'queue_statistics' => $queueStats,
                'timestamp' => now()->toISOString(),
            ]);
        }
        
        return view('notifications.status.index', compact('health', 'errorStats', 'queueStats'));
    }

    /**
     * Get system health status (public endpoint)
     */
    public function health(Request $request)
    {
        $health = $this->monitoringService->getSystemHealth();
        
        $statusCode = 200;
        if ($health['overall_status'] === 'critical') {
            $statusCode = 503;
        } elseif ($health['overall_status'] === 'warning') {
            $statusCode = 206;
        }
        
        return response()->json([
            'status' => $health['overall_status'],
            'timestamp' => now()->toISOString(),
            'components' => array_map(function ($component) {
                return [
                    'name' => $component,
                    'status' => $component['status'],
                ];
            }, $health['components']),
            'metrics' => [
                'error_rate' => $health['metrics']['notifications']['error_rate'],
                'success_rate' => $health['metrics']['notifications']['success_rate'],
                'processing_rate' => $health['metrics']['performance']['processing_rate_per_minute'],
            ],
        ], $statusCode);
    }

    /**
     * Get detailed error statistics
     */
    public function errorStatistics(Request $request)
    {
        $period = $request->get('period', 'day');
        $stats = $this->errorHandler->getErrorStatistics($period);
        
        return response()->json([
            'period' => $period,
            'statistics' => $stats,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get queue statistics
     */
    public function queueStatistics(Request $request)
    {
        $queueName = $request->get('queue');
        $stats = $this->queueMonitor->getQueueStatistics($queueName);
        
        return response()->json([
            'queue' => $queueName ?? 'all',
            'statistics' => $stats,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get provider health status
     */
    public function providerHealth(Request $request)
    {
        $providers = \App\Models\NotificationProvider::all();
        $healthData = [];
        
        foreach ($providers as $provider) {
            $healthData[] = [
                'id' => $provider->id,
                'name' => $provider->name,
                'type' => $provider->type,
                'status' => $provider->health_status,
                'is_active' => $provider->is_active,
                'is_default' => $provider->is_default,
                'last_health_check' => $provider->last_health_check,
                'failure_rate' => $this->monitoringService->getProviderFailureRate($provider->id),
            ];
        }
        
        return response()->json([
            'providers' => $healthData,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get delivery statistics
     */
    public function deliveryStatistics(Request $request)
    {
        $period = $request->get('period', 'day');
        $stats = $this->monitoringService->getDeliveryStatistics($period);
        
        return response()->json([
            'period' => $period,
            'statistics' => $stats,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get retryable notifications
     */
    public function retryableNotifications(Request $request)
    {
        $limit = $request->get('limit', 100);
        $notifications = $this->errorHandler->getRetryableNotifications($limit);
        
        return response()->json([
            'count' => count($notifications),
            'notifications' => $notifications,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Retry failed notifications
     */
    public function retryFailedNotifications(Request $request)
    {
        $request->validate([
            'notification_ids' => 'array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);
        
        $notificationIds = $request->get('notification_ids', []);
        
        if (empty($notificationIds)) {
            // Get all retryable notifications
            $retryable = $this->errorHandler->getRetryableNotifications(1000);
            $notificationIds = array_column($retryable, 'id');
        }
        
        $results = $this->errorHandler->batchRetryFailedNotifications($notificationIds);
        
        return response()->json([
            'action' => 'retry',
            'results' => $results,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Clear notification cache
     */
    public function clearCache(Request $request)
    {
        $cacheService = app(\App\Services\NotificationCacheService::class);
        $cacheService->clearAllCaches();
        
        Cache::flush();
        
        return response()->json([
            'action' => 'clear_cache',
            'message' => 'Notification cache cleared successfully',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Run health check
     */
    public function runHealthCheck(Request $request)
    {
        $results = $this->monitoringService->runHealthCheck();
        
        return response()->json([
            'action' => 'health_check',
            'results' => $results,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get system recommendations
     */
    public function recommendations(Request $request)
    {
        $health = $this->monitoringService->getSystemHealth();
        
        return response()->json([
            'recommendations' => $health['recommendations'],
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get historical health data
     */
    public function historicalHealth(Request $request)
    {
        $period = $request->get('period', 'day');
        $data = $this->monitoringService->getHistoricalHealth($period);
        
        return response()->json([
            'period' => $period,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Export status report
     */
    public function exportReport(Request $request)
    {
        $format = $request->get('format', 'json');
        $period = $request->get('period', 'day');
        
        $data = [
            'timestamp' => now()->toISOString(),
            'period' => $period,
            'health' => $this->monitoringService->getSystemHealth(),
            'error_statistics' => $this->errorHandler->getErrorStatistics($period),
            'delivery_statistics' => $this->monitoringService->getDeliveryStatistics($period),
            'queue_statistics' => $this->queueMonitor->getQueueStatistics(),
        ];
        
        if ($format === 'csv') {
            return $this->exportCsv($data);
        } elseif ($format === 'pdf') {
            return $this->exportPdf($data);
        }
        
        return response()->json($data);
    }

    /**
     * Export data as CSV
     */
    private function exportCsv(array $data)
    {
        // Implement CSV export
        return response()->json([
            'message' => 'CSV export not implemented',
            'data' => $data,
        ]);
    }

    /**
     * Export data as PDF
     */
    private function exportPdf(array $data)
    {
        // Implement PDF export
        return response()->json([
            'message' => 'PDF export not implemented',
            'data' => $data,
        ]);
    }

    /**
     * Get system alerts
     */
    public function alerts(Request $request)
    {
        $health = $this->monitoringService->getSystemHealth();
        
        return response()->json([
            'alerts' => $health['alerts'],
            'count' => count($health['alerts']),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert(Request $request, string $alertId)
    {
        // Store acknowledgment in cache
        $cacheKey = "alert_acknowledged:{$alertId}";
        Cache::put($cacheKey, [
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now()->toISOString(),
            'notes' => $request->get('notes'),
        ], 3600); // Acknowledgment lasts 1 hour
        
        return response()->json([
            'action' => 'acknowledge',
            'alert_id' => $alertId,
            'acknowledged' => true,
            'timestamp' => now()->toISOString(),
        ]);
    }
}