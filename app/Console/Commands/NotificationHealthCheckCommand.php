<?php

namespace App\Console\Commands;

use App\Services\NotificationMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotificationHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:health-check
                            {--silent : Run silently without output}
                            {--detailed : Show detailed health information}
                            {--send-alerts : Send alerts for any issues found}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a comprehensive health check of the notification system';

    /**
     * Execute the console command.
     */
    public function handle(NotificationMonitoringService $monitoringService)
    {
        $startTime = microtime(true);
        
        $this->info('Starting notification system health check...');
        
        // Run health check
        $health = $monitoringService->runHealthCheck();
        
        $processingTime = microtime(true) - $startTime;
        
        // Display results
        $this->displayHealthResults($health, $processingTime);
        
        // Send alerts if requested
        if ($this->option('send-alerts') && !empty($health['alerts'])) {
            $this->sendAlerts($health['alerts']);
        }
        
        // Return appropriate exit code
        if ($health['overall_status'] === 'critical') {
            $this->error('Health check completed with CRITICAL status');
            return 1;
        } elseif ($health['overall_status'] === 'warning') {
            $this->warn('Health check completed with WARNING status');
            return 0;
        }
        
        $this->info('Health check completed successfully');
        return 0;
    }

    /**
     * Display health check results
     */
    private function displayHealthResults(array $health, float $processingTime): void
    {
        $statusColor = match($health['overall_status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white',
        };
        
        $this->line('');
        $this->line(str_repeat('=', 60));
        $this->line('NOTIFICATION SYSTEM HEALTH CHECK');
        $this->line(str_repeat('=', 60));
        $this->line('');
        
        $this->line("Overall Status: <fg={$statusColor}>{$health['overall_status']}</>");
        $this->line("Processing Time: {$processingTime}s");
        $this->line("Timestamp: {$health['health_check']['timestamp']}");
        $this->line('');
        
        // Display metrics
        $this->line('<fg=cyan>Key Metrics:</>');
        $this->table(
            ['Metric', 'Value', 'Status'],
            [
                ['Success Rate', "{$health['metrics']['notifications']['success_rate']}%", $this->getMetricStatus($health['metrics']['notifications']['success_rate'], 95, 85)],
                ['Error Rate', "{$health['metrics']['notifications']['error_rate']}%", $this->getMetricStatus($health['metrics']['notifications']['error_rate'], 5, 15, true)],
                ['Processing Rate', "{$health['metrics']['performance']['processing_rate_per_minute']}/min", $this->getMetricStatus($health['metrics']['performance']['processing_rate_per_minute'], 100, 50)],
                ['Queue Backlog', $health['metrics']['performance']['queue_backlog'], $this->getMetricStatus($health['metrics']['performance']['queue_backlog'], 10, 50, true)],
                ['Avg Processing Time', "{$health['metrics']['performance']['average_processing_time_ms']}ms", $this->getMetricStatus($health['metrics']['performance']['average_processing_time_ms'], 1000, 5000, true)],
            ]
        );
        
        // Display component status
        $this->line('<fg=cyan>Component Status:</>');
        $components = [];
        foreach ($health['components'] as $name => $component) {
            $components[] = [
                ucfirst(str_replace('_', ' ', $name)),
                $component['status'],
                $component['message'] ?? 'No issues detected',
            ];
        }
        $this->table(['Component', 'Status', 'Details'], $components);
        
        // Display alerts if any
        if (!empty($health['alerts'])) {
            $this->line('');
            $this->line('<fg=red>Active Alerts:</>');
            foreach ($health['alerts'] as $alert) {
                $this->line("  • [{$alert['level']}] {$alert['title']}: {$alert['message']}");
            }
        }
        
        // Display recommendations if any
        if (!empty($health['recommendations'])) {
            $this->line('');
            $this->line('<fg=yellow>Recommendations:</>');
            foreach ($health['recommendations'] as $recommendation) {
                $this->line("  • [{$recommendation['priority']}] {$recommendation['action']}: {$recommendation['reason']}");
            }
        }
        
        // Detailed information if requested
        if ($this->option('detailed')) {
            $this->displayDetailedInformation($health);
        }
        
        $this->line('');
        $this->line(str_repeat('=', 60));
    }

    /**
     * Get metric status for display
     */
    private function getMetricStatus($value, $goodThreshold, $warningThreshold, $inverse = false): string
    {
        if ($inverse) {
            // Lower is better
            if ($value <= $goodThreshold) return '✅ Good';
            if ($value <= $warningThreshold) return '⚠️ Warning';
            return '❌ Critical';
        } else {
            // Higher is better
            if ($value >= $goodThreshold) return '✅ Good';
            if ($value >= $warningThreshold) return '⚠️ Warning';
            return '❌ Critical';
        }
    }

    /**
     * Display detailed health information
     */
    private function displayDetailedInformation(array $health): void
    {
        $this->line('');
        $this->line('<fg=magenta>Detailed Information:</>');
        
        // Database metrics
        $this->line('<fg=cyan>Database Metrics:</>');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Notifications', $health['metrics']['database']['total_notifications']],
                ['Pending Notifications', $health['metrics']['database']['pending_notifications']],
                ['Failed Notifications', $health['metrics']['database']['failed_notifications']],
                ['Success Rate', "{$health['metrics']['database']['success_rate']}%"],
            ]
        );
        
        // Queue metrics
        $this->line('<fg=cyan>Queue Metrics:</>');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Jobs', $health['metrics']['queue']['total_jobs']],
                ['Pending Jobs', $health['metrics']['queue']['pending_jobs']],
                ['Failed Jobs', $health['metrics']['queue']['failed_jobs']],
                ['Average Wait Time', "{$health['metrics']['queue']['average_wait_time_seconds']}s"],
            ]
        );
        
        // Storage metrics
        $this->line('<fg=cyan>Storage Metrics:</>');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Space', $this->formatBytes($health['metrics']['storage']['total_space_bytes'])],
                ['Used Space', $this->formatBytes($health['metrics']['storage']['used_space_bytes'])],
                ['Free Space', $this->formatBytes($health['metrics']['storage']['free_space_bytes'])],
                ['Usage Percentage', "{$health['metrics']['storage']['usage_percentage']}%"],
            ]
        );
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Send alerts to configured channels
     */
    private function sendAlerts(array $alerts): void
    {
        $this->info('Sending alerts for ' . count($alerts) . ' issues...');
        
        foreach ($alerts as $alert) {
            $this->line("  • Sending alert: {$alert['title']}");
            // In a real implementation, this would send alerts via email, Slack, etc.
            // For now, just log it
            Log::warning('Notification system alert', $alert);
        }
        
        $this->info('Alerts sent successfully');
    }

    /**
     * Schedule the command
     */
    public function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        // Run every 5 minutes in production
        if (app()->environment('production')) {
            $schedule->command('notifications:health-check --silent --send-alerts')
                     ->everyFiveMinutes()
                     ->withoutOverlapping()
                     ->runInBackground();
        }
        
        // Run every hour in other environments
        $schedule->command('notifications:health-check --silent')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();
    }
}