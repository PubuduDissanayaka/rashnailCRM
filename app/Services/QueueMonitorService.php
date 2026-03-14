<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Contracts\Queue\Factory as QueueFactory;

class QueueMonitorService
{
    protected $queue;
    protected $worker;

    public function __construct(QueueFactory $queue, Worker $worker)
    {
        $this->queue = $queue;
        $this->worker = $worker;
    }

    /**
     * Get queue statistics
     */
    public function getQueueStatistics(string $queueName = null): array
    {
        $queues = $queueName ? [$queueName] : ['notifications', 'default', 'high', 'low'];
        
        $stats = [];
        foreach ($queues as $queue) {
            $stats[$queue] = $this->getQueueStats($queue);
        }

        return [
            'queues' => $stats,
            'total_jobs' => array_sum(array_column($stats, 'pending')),
            'total_failed' => $this->getFailedJobsCount(),
            'worker_status' => $this->getWorkerStatus(),
            'last_processed' => Cache::get('queue_last_processed', 'Never'),
            'processing_rate' => $this->getProcessingRate(),
        ];
    }

    /**
     * Get stats for a specific queue
     */
    private function getQueueStats(string $queueName): array
    {
        $pending = DB::table('jobs')
            ->where('queue', $queueName)
            ->count();

        $failed = DB::table('failed_jobs')
            ->where('queue', $queueName)
            ->count();

        // Get oldest job timestamp
        $oldestJob = DB::table('jobs')
            ->where('queue', $queueName)
            ->orderBy('created_at', 'asc')
            ->first();

        // Get average processing time (from job payload metadata if available)
        $recentJobs = DB::table('jobs')
            ->where('queue', $queueName)
            ->where('created_at', '>=', now()->subHour())
            ->get();

        $totalProcessingTime = 0;
        $processedCount = 0;

        foreach ($recentJobs as $job) {
            $payload = json_decode($job->payload, true);
            if (isset($payload['metadata']['processing_time'])) {
                $totalProcessingTime += $payload['metadata']['processing_time'];
                $processedCount++;
            }
        }

        $avgProcessingTime = $processedCount > 0 ? $totalProcessingTime / $processedCount : 0;

        return [
            'pending' => $pending,
            'failed' => $failed,
            'oldest_job_age' => $oldestJob ? now()->diffInMinutes($oldestJob->created_at) : 0,
            'avg_processing_time' => round($avgProcessingTime, 2),
            'health_status' => $this->getQueueHealthStatus($pending, $oldestJob),
        ];
    }

    /**
     * Determine queue health status
     */
    private function getQueueHealthStatus(int $pending, $oldestJob): string
    {
        if ($pending === 0) {
            return 'healthy';
        }

        $oldestAge = $oldestJob ? now()->diffInMinutes($oldestJob->created_at) : 0;

        if ($oldestAge > 60) { // Jobs older than 1 hour
            return 'critical';
        } elseif ($oldestAge > 30) { // Jobs older than 30 minutes
            return 'degraded';
        } elseif ($pending > 1000) { // Too many pending jobs
            return 'congested';
        } else {
            return 'healthy';
        }
    }

    /**
     * Get failed jobs count
     */
    private function getFailedJobsCount(): int
    {
        return DB::table('failed_jobs')->count();
    }

    /**
     * Get worker status
     */
    private function getWorkerStatus(): array
    {
        $workers = Cache::get('active_workers', []);
        
        return [
            'active_workers' => count($workers),
            'workers' => $workers,
            'last_heartbeat' => Cache::get('worker_last_heartbeat', null),
            'memory_usage' => memory_get_usage(true) / 1024 / 1024, // MB
        ];
    }

    /**
     * Get processing rate (jobs per minute)
     */
    private function getProcessingRate(): float
    {
        $processedLastHour = Cache::get('jobs_processed_last_hour', 0);
        return round($processedLastHour / 60, 2); // Jobs per minute
    }

    /**
     * Process queue with monitoring
     */
    public function processQueue(string $queueName, int $maxJobs = 100, int $timeout = 60): array
    {
        $startTime = microtime(true);
        $processed = 0;
        $failed = 0;

        $options = new WorkerOptions(
            $timeout, // timeout
            0, // memory
            60, // sleep
            3, // maxTries
            false, // force
            false // stopWhenEmpty
        );

        // Record worker start
        $this->recordWorkerStart($queueName);

        try {
            while ($processed < $maxJobs) {
                $jobProcessed = $this->worker->runNextJob(
                    'database',
                    $queueName,
                    $options
                );

                if ($jobProcessed === false) {
                    // No more jobs
                    break;
                }

                $processed++;
                
                // Update heartbeat
                $this->updateHeartbeat($queueName, $processed);
            }
        } catch (\Exception $e) {
            $failed++;
            \Log::error("Queue processing error: " . $e->getMessage());
        } finally {
            // Record worker stop
            $this->recordWorkerStop($queueName);
        }

        $processingTime = microtime(true) - $startTime;

        // Update statistics
        $this->updateStatistics($queueName, $processed, $failed, $processingTime);

        return [
            'queue' => $queueName,
            'processed' => $processed,
            'failed' => $failed,
            'processing_time' => round($processingTime, 2),
            'jobs_per_second' => $processed > 0 ? round($processed / $processingTime, 2) : 0,
            'memory_used' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        ];
    }

    /**
     * Record worker start
     */
    private function recordWorkerStart(string $queueName): void
    {
        $workerId = uniqid('worker_');
        $workers = Cache::get('active_workers', []);
        
        $workers[$workerId] = [
            'queue' => $queueName,
            'started_at' => now(),
            'pid' => getmypid(),
            'processed' => 0,
        ];
        
        Cache::put('active_workers', $workers, 300); // 5 minutes TTL
        Cache::put('worker_last_heartbeat', now(), 65); // Slightly longer than timeout
    }

    /**
     * Update worker heartbeat
     */
    private function updateHeartbeat(string $queueName, int $processed): void
    {
        $workers = Cache::get('active_workers', []);
        
        foreach ($workers as $workerId => &$worker) {
            if ($worker['queue'] === $queueName) {
                $worker['last_heartbeat'] = now();
                $worker['processed'] = $processed;
                break;
            }
        }
        
        Cache::put('active_workers', $workers, 300);
        Cache::put('worker_last_heartbeat', now(), 65);
    }

    /**
     * Record worker stop
     */
    private function recordWorkerStop(string $queueName): void
    {
        $workers = Cache::get('active_workers', []);
        
        foreach ($workers as $workerId => $worker) {
            if ($worker['queue'] === $queueName) {
                unset($workers[$workerId]);
                break;
            }
        }
        
        Cache::put('active_workers', $workers, 300);
    }

    /**
     * Update processing statistics
     */
    private function updateStatistics(string $queueName, int $processed, int $failed, float $processingTime): void
    {
        // Update hourly processed count
        $hourKey = 'jobs_processed_hour_' . date('Y-m-d-H');
        $hourlyCount = Cache::get($hourKey, 0) + $processed;
        Cache::put($hourKey, $hourlyCount, 3600);

        // Update last processed timestamp
        Cache::put('queue_last_processed', now(), 300);

        // Update queue-specific stats
        $queueStats = Cache::get("queue_stats:{$queueName}", [
            'total_processed' => 0,
            'total_failed' => 0,
            'total_time' => 0,
        ]);

        $queueStats['total_processed'] += $processed;
        $queueStats['total_failed'] += $failed;
        $queueStats['total_time'] += $processingTime;

        Cache::put("queue_stats:{$queueName}", $queueStats, 3600);
    }

    /**
     * Clean up stale workers
     */
    public function cleanupStaleWorkers(): array
    {
        $workers = Cache::get('active_workers', []);
        $removed = 0;
        
        foreach ($workers as $workerId => $worker) {
            $lastHeartbeat = $worker['last_heartbeat'] ?? $worker['started_at'];
            
            if (now()->diffInMinutes($lastHeartbeat) > 5) { // Stale after 5 minutes
                unset($workers[$workerId]);
                $removed++;
            }
        }
        
        Cache::put('active_workers', $workers, 300);
        
        return [
            'removed' => $removed,
            'remaining' => count($workers),
        ];
    }

    /**
     * Get queue recommendations
     */
    public function getRecommendations(): array
    {
        $stats = $this->getQueueStatistics();
        $recommendations = [];

        foreach ($stats['queues'] as $queueName => $queueStats) {
            if ($queueStats['pending'] > 1000) {
                $recommendations[] = "Queue '{$queueName}' has high pending jobs ({$queueStats['pending']}). Consider scaling workers.";
            }
            
            if ($queueStats['oldest_job_age'] > 30) {
                $recommendations[] = "Queue '{$queueName}' has old jobs ({$queueStats['oldest_job_age']} minutes). Check for processing issues.";
            }
            
            if ($queueStats['health_status'] === 'critical') {
                $recommendations[] = "Queue '{$queueName}' is in critical state. Immediate attention required.";
            }
        }

        if ($stats['total_failed'] > 100) {
            $recommendations[] = "High number of failed jobs ({$stats['total_failed']}). Review failed jobs queue.";
        }

        if ($stats['processing_rate'] < 1) {
            $recommendations[] = "Low processing rate ({$stats['processing_rate']} jobs/min). Consider optimizing job processing.";
        }

        return $recommendations;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(string $period = 'day'): array
    {
        $cacheKey = "queue_performance:{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($period) {
            $startDate = match($period) {
                'hour' => now()->subHour(),
                'day' => now()->subDay(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                default => now()->subDay(),
            };

            $metrics = DB::table('jobs')
                ->selectRaw('
                    DATE(created_at) as date,
                    HOUR(created_at) as hour,
                    queue,
                    COUNT(*) as total_jobs,
                    AVG(TIMESTAMPDIFF(SECOND, created_at, reserved_at)) as avg_wait_time,
                    AVG(TIMESTAMPDIFF(SECOND, reserved_at, available_at)) as avg_process_time
                ')
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('reserved_at')
                ->whereNotNull('available_at')
                ->groupBy('date', 'hour', 'queue')
                ->orderBy('date', 'desc')
                ->orderBy('hour', 'desc')
                ->get()
                ->toArray();

            return [
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => now(),
                'metrics' => $metrics,
                'summary' => [
                    'total_jobs' => array_sum(array_column($metrics, 'total_jobs')),
                    'avg_wait_time' => round(collect($metrics)->avg('avg_wait_time') ?? 0, 2),
                    'avg_process_time' => round(collect($metrics)->avg('avg_process_time') ?? 0, 2),
                ],
            ];
        });
    }

    /**
     * Optimize queue configuration
     */
    public function optimizeConfiguration(): array
    {
        $currentConfig = config('queue');
        $recommendations = [];

        // Check database queue configuration
        if ($currentConfig['default'] === 'database') {
            $pendingJobs = DB::table('jobs')->count();
            
            if ($pendingJobs > 10000) {
                $recommendations[] = "Consider switching to Redis queue for better performance with {$pendingJobs} pending jobs.";
            }
        }

        // Check retry configuration
        $databaseRetry = $currentConfig['connections']['database']['retry_after'] ?? 90;
        if ($databaseRetry < 60) {
            $recommendations[] = "Consider increasing retry_after to at least 60 seconds to prevent duplicate processing.";
        }

        // Check queue priorities
        if (!isset($currentConfig['connections']['database']['queue'])) {
            $recommendations[] = "Consider implementing multiple queues (high, default, low) for better priority management.";
        }

        return [
            'current_config' => [
                'default' => $currentConfig['default'],
                'retry_after' => $databaseRetry,
                'queues' => array_keys($currentConfig['connections']),
            ],
            'recommendations' => $recommendations,
        ];
    }
}