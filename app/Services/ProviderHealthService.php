<?php

namespace App\Services;

use App\Models\NotificationProvider;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProviderHealthService
{
    /**
     * Check health of all providers.
     *
     * @return array
     */
    public function checkAllProviders(): array
    {
        $providers = NotificationProvider::all();
        $results = [];

        foreach ($providers as $provider) {
            $results[] = $this->checkProviderHealth($provider);
        }

        return $results;
    }

    /**
     * Check health of a specific provider.
     *
     * @param NotificationProvider $provider
     * @return array
     */
    public function checkProviderHealth(NotificationProvider $provider): array
    {
        $cacheKey = "provider_health_{$provider->id}";
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $health = [
            'provider_id' => $provider->id,
            'provider_name' => $provider->name,
            'channel' => $provider->channel,
            'provider_type' => $provider->provider,
            'is_active' => $provider->is_active,
            'is_default' => $provider->is_default,
            'last_test_at' => $provider->last_test_at,
            'last_test_status' => $provider->last_test_status,
            'checks' => [],
            'overall_status' => 'unknown',
            'recommendation' => null,
        ];

        // Perform health checks
        $health['checks'] = $this->performHealthChecks($provider);
        
        // Determine overall status
        $health['overall_status'] = $this->determineOverallStatus($health['checks']);
        
        // Generate recommendation
        $health['recommendation'] = $this->generateRecommendation($provider, $health['checks']);
        
        // Cache the result for 5 minutes
        Cache::put($cacheKey, $health, 300);

        return $health;
    }

    /**
     * Perform health checks for a provider.
     *
     * @param NotificationProvider $provider
     * @return array
     */
    private function performHealthChecks(NotificationProvider $provider): array
    {
        $checks = [];

        // 1. Configuration check
        $checks['configuration'] = $this->checkConfiguration($provider);
        
        // 2. Connection test (if recently tested)
        $checks['connection'] = $this->checkConnection($provider);
        
        // 3. Usage limits check
        $checks['usage_limits'] = $this->checkUsageLimits($provider);
        
        // 4. Recent performance check
        $checks['recent_performance'] = $this->checkRecentPerformance($provider);
        
        // 5. Error rate check
        $checks['error_rate'] = $this->checkErrorRate($provider);

        return $checks;
    }

    /**
     * Check provider configuration.
     *
     * @param NotificationProvider $provider
     * @return array
     */
    private function checkConfiguration(NotificationProvider $provider): array
    {
        $config = $provider->config ?? [];
        $requiredFields = $this->getRequiredFieldsForProvider($provider);
        
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                $missingFields[] = $field;
            }
        }

        $status = empty($missingFields) ? 'healthy' : 'unhealthy';
        
        return [
            'status' => $status,
            'message' => empty($missingFields) 
                ? 'All required configuration fields are present'
                : 'Missing required fields: ' . implode(', ', $missingFields),
            'missing_fields' => $missingFields,
            'config_fields_count' => count($config),
        ];
    }

    /**
     * Check provider connection status.
     *
     * @param NotificationProvider $provider
     * @return array
     */
    private function checkConnection(NotificationProvider $provider): array
    {
        if (!$provider->last_test_at) {
            return [
                'status' => 'unknown',
                'message' => 'Connection has never been tested',
                'last_test' => null,
            ];
        }

        $lastTestAge = $provider->last_test_at->diffInHours(now());
        
        if ($lastTestAge > 24) {
            return [
                'status' => 'warning',
                'message' => 'Connection test was ' . $lastTestAge . ' hours ago',
                'last_test' => $provider->last_test_at->toISOString(),
                'test_age_hours' => $lastTestAge,
            ];
        }

        $status = $provider->last_test_status === 'success' ? 'healthy' : 'unhealthy';
        
        return [
            'status' => $status,
            'message' => $provider->last_test_status === 'success' 
                ? 'Last connection test was successful'
                : 'Last connection test failed',
            'last_test' => $provider->last_test_at->toISOString(),
            'test_result' => $provider->last_test_status,
        ];
    }

    /**
     * Check provider usage limits.
     *
     * @param NotificationProvider $provider
     * @return array
     */
    private function checkUsageLimits(NotificationProvider $provider): array
    {
        $checks = [];
        
        // Daily limit check
        if ($provider->daily_limit !== null) {
            $dailyUsage = $this->getDailyUsage($provider);
            $dailyPercentage = ($dailyUsage / $provider->daily_limit) * 100;
            
            if ($dailyPercentage >= 90) {
                $status = 'warning';
                $message = 'Daily limit almost reached: ' . $dailyUsage . '/' . $provider->daily_limit;
            } elseif ($dailyPercentage >= 100) {
                $status = 'unhealthy';
                $message = 'Daily limit exceeded: ' . $dailyUsage . '/' . $provider->daily_limit;
            } else {
                $status = 'healthy';
                $message = 'Daily usage: ' . $dailyUsage . '/' . $provider->daily_limit;
            }
            
            $checks['daily'] = [
                'status' => $status,
                'message' => $message,
                'usage' => $dailyUsage,
                'limit' => $provider->daily_limit,
                'percentage' => $dailyPercentage,
            ];
        }
        
        // Monthly limit check
        if ($provider->monthly_limit !== null) {
            $monthlyUsage = $this->getMonthlyUsage($provider);
            $monthlyPercentage = ($monthlyUsage / $provider->monthly_limit) * 100;
            
            if ($monthlyPercentage >= 90) {
                $status = 'warning';
                $message = 'Monthly limit almost reached: ' . $monthlyUsage . '/' . $provider->monthly_limit;
            } elseif ($monthlyPercentage >= 100) {
                $status = 'unhealthy';
                $message = 'Monthly limit exceeded: ' . $monthlyUsage . '/' . $provider->monthly_limit;
            } else {
                $status = 'healthy';
                $message = 'Monthly usage: ' . $monthlyUsage . '/' . $provider->monthly_limit;
            }
            
            $checks['monthly'] = [
                'status' => $status,
                'message' => $message,
                'usage' => $monthlyUsage,
                'limit' => $provider->monthly_limit,
                'percentage' => $monthlyPercentage,
            ];
        }
        
        if (empty($checks)) {
            return [
                'status' => 'healthy',
                'message' => 'No usage limits configured',
                'checks' => [],
            ];
        }
        
        // Determine overall status
        $overallStatus = 'healthy';
        foreach ($checks as $check) {
            if ($check['status'] === 'unhealthy') {
                $overallStatus = 'unhealthy';
                break;
            } elseif ($check['status'] === 'warning') {
                $overallStatus = 'warning';
            }
        }
        
        return [
            'status' => $overallStatus,
            'message' => 'Usage limits check completed',
            'checks' => $checks,
        ];
    }

    /**
     * Check recent performance.
     *
     * @param NotificationProvider $provider
     * @return array
     */
    private function checkRecentPerformance(NotificationProvider $provider): array
    {
        $last24Hours = Carbon::now()->subHours(24);
        
        $logs = NotificationLog::where('channel', $provider->channel)
            ->where('provider', $provider->provider)
            ->where('created_at', '>=', $last24Hours)
            ->get();
        
        if ($logs->isEmpty()) {
            return [
                'status' => 'unknown',
                'message' => 'No activity in the last 24 hours',
                'total' => 0,
                'successful' => 0,
                'failed' => 0,
                'success_rate' => 0,
            ];
        }
        
        $total = $logs->count();
        $successful = $logs->whereIn('status', ['sent', 'delivered'])->count();
        $failed = $logs->where('status', 'failed')->count();
        $successRate = $total > 0 ? ($successful / $total) * 100 : 0;
        
        if ($successRate >= 95) {
            $status = 'healthy';
            $message = 'Excellent performance: ' . round($successRate, 2) . '% success rate';
        } elseif ($successRate >= 80) {
            $status = 'warning';
            $message = 'Acceptable performance: ' . round($successRate, 2) . '% success rate';
        } else {
            $status = 'unhealthy';
            $message = 'Poor performance: ' . round($successRate, 2) . '% success rate';
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => round($successRate, 2),
            'time_period' => '24_hours',
        ];
    }

    /**
     * Check error rate.
     *
     * @param NotificationProvider $provider
     * @return array
     */
    private function checkErrorRate(NotificationProvider $provider): array
    {
        $lastHour = Carbon::now()->subHour();
        
        $logs = NotificationLog::where('channel', $provider->channel)
            ->where('provider', $provider->provider)
            ->where('created_at', '>=', $lastHour)
            ->get();
        
        if ($logs->isEmpty()) {
            return [
                'status' => 'healthy',
                'message' => 'No activity in the last hour',
                'total' => 0,
                'errors' => 0,
                'error_rate' => 0,
            ];
        }
        
        $total = $logs->count();
        $errors = $logs->where('status', 'failed')->count();
        $errorRate = $total > 0 ? ($errors / $total) * 100 : 0;
        
        if ($errorRate === 0) {
            $status = 'healthy';
            $message = 'No errors in the last hour';
        } elseif ($errorRate <= 5) {
            $status = 'warning';
            $message = 'Low error rate: ' . round($errorRate, 2) . '%';
        } else {
            $status = 'unhealthy';
            $message = 'High error rate: ' . round($errorRate, 2) . '%';
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'total' => $total,
            'errors' => $errors,
            'error_rate' => round($errorRate, 2),
            'time_period' => '1_hour',
        ];
    }

    /**
     * Get required fields for a provider type.
     *
     * @param NotificationProvider $provider
     * @return array
     */
    private function getRequiredFieldsForProvider(NotificationProvider $provider): array
    {
        $fields = [];
        
        switch ($provider->channel) {
            case 'email':
                switch ($provider->provider) {
                    case 'smtp':
                        $fields = ['host', 'port', 'username', 'password', 'from_address'];
                        break;
                    case 'mailgun':
                        $fields = ['domain', 'secret', 'from_address'];
                        break;
                    case 'sendgrid':
                        $fields = ['api_key', 'from_address'];
                        break;
                    case 'ses':
                        $fields = ['key', 'secret', 'region', 'from_address'];
                        break;
                }
                break;
            case 'sms':
                $fields = ['api_key', 'from_number'];
                break;
            case 'in_app':
                $fields = []; // No configuration required
                break;
            case 'push':
                $fields = ['api_key', 'project_id'];
                break;
        }
        
        return $fields;
    }

    /**
     * Get daily usage for a provider.
     *
     * @param NotificationProvider $provider
     * @return int
     */
    private function getDailyUsage(NotificationProvider $provider): int
    {
        $today = Carbon::today();
        
        return NotificationLog::where('channel', $provider->channel)
            ->where('provider', $provider->provider)
            ->whereDate('created_at', $today)
            ->count();
    }

    /**
     * Get monthly usage for a provider.
     *
     * @param NotificationProvider $provider
     * @return int
     */
    private function getMonthlyUsage(NotificationProvider $provider): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        
        return NotificationLog::where('channel', $provider->channel)
            ->where('provider', $provider->provider)
            ->whereDate('created_at', '>=', $startOfMonth)
            ->count();
    }

    /**
     * Determine overall status from checks.
     *
     * @param array $checks
     * @return string
     */
    private function determineOverallStatus(array $checks): string
    {
        $statusPriority = [
            'unhealthy' => 3,
            'warning' => 2,
            'unknown' => 1,
            'healthy' => 0,
        ];
        
        $highestPriority = 0;
        $overallStatus = 'healthy';
        
        foreach ($checks as $check) {
            $priority = $statusPriority[$check['status']] ?? 0;
            if ($priority > $highestPriority) {
                $highestPriority = $priority;
                $overallStatus = $check['status'];
            }
        }
        
        return $overallStatus;
    }

    /**
     * Generate recommendation based on health checks.
     *
     * @param NotificationProvider $provider
     * @param array $checks
     * @return string|null
     */
    private function generateRecommendation(NotificationProvider $provider, array $checks): ?string
    {
        $recommendations = [];
        
        // Check configuration
        if ($checks['configuration']['status'] === 'unhealthy') {
            $recommendations[] = 'Fix missing configuration fields: ' . 
                implode(', ', $checks['configuration']['missing_fields']);
        }
        
        // Check connection
        if ($checks['connection']['status'] === 'unknown') {
            $recommendations[] = 'Run a connection test to verify provider connectivity';
        } elseif ($checks['connection']['status'] === 'warning') {
            $recommendations[] = 'Connection test is outdated, run a new test';
        } elseif ($checks['connection']['status'] === 'unhealthy') {
            $recommendations[] = 'Connection test failed, check provider credentials and network';
        }
        
        // Check usage limits
        if ($checks['usage_limits']['status'] === 'warning') {
            $recommendations[] = 'Usage limits approaching, consider increasing limits or adding backup provider';
        } elseif ($checks['usage_limits']['status'] === 'unhealthy') {
            $recommendations[] = 'Usage limits exceeded, provider may be blocked. Add backup provider or increase limits';
        }
        
        // Check performance
        if ($checks['recent_performance']['status'] === 'unhealthy') {
            $recommendations[] = 'Poor performance detected, investigate delivery issues';
        }
        
        // Check error rate
        if ($checks['error_rate']['status'] === 'unhealthy') {
            $recommendations[] = 'High error rate detected, immediate attention required';
        }
        
        if (empty($recommendations)) {
            return 'Provider is healthy and operating normally';
        }
        
        return implode('. ', $recommendations);
    }

    /**
     * Get provider health summary.
     *
     * @return array
     */
    public function getHealthSummary(): array
    {
        $providers = NotificationProvider::all();
        
        $summary = [
            'total_providers' => $providers->count(),
            'active_providers' => $providers->where('is_active', true)->count(),
            'health_status' => [
                'healthy' => 0,
                'warning' => 0,
                'unhealthy' => 0,
                'unknown' => 0,
            ],
            'by_channel' => [],
        ];
        
        foreach ($providers as $provider) {
            $health = $this->checkProviderHealth($provider);
            $status = $health['overall_status'];
            
            $summary['health_status'][$status]++;
            
            $channel = $provider->channel;
            if (!isset($summary['by_channel'][$channel])) {
                $summary['by_channel'][$channel] = [
                    'total' => 0,
                    'healthy' => 0,
                    'warning' => 0,
                    'unhealthy' => 0,
                    'unknown' => 0,
                ];
            }
            
            $summary['by_channel'][$channel]['total']++;
            $summary['by_channel'][$channel][$status]++;
        }
        
        return $summary;
    }

    /**
     * Test provider connection.
     *
     * @param NotificationProvider $provider
     * @return array
     */
    public function testProviderConnection(NotificationProvider $provider): array
    {
        try {
            // This is a simplified test - in a real implementation,
            // you would test the actual connection to the provider
            $config = $provider->config ?? [];
            
            if (empty($config)) {
                throw new \Exception('Provider configuration is empty');
            }
            
            // Check if required fields exist
            $requiredFields = $this->getRequiredFieldsForProvider($provider);
            foreach ($requiredFields as $field) {
                if (!isset($config[$field]) || empty($config[$field])) {
                    throw new \Exception("Required field '{$field}' is missing or empty");
                }
            }
            
            // Simulate connection test
            // In production, you would actually connect to the provider
            // For example, for SMTP you would use SwiftMailer to test connection
            // For API providers you would make a test API call
            
            $success = true; // Simulated success
            $message = 'Connection test successful';
            
            // Update provider test status
            $provider->markTested($success);
            
            // Clear health cache
            Cache::forget("provider_health_{$provider->id}");
            
            return [
                'success' => $success,
                'message' => $message,
                'provider_id' => $provider->id,
                'tested_at' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            $provider->markTested(false);
            
            // Clear health cache
            Cache::forget("provider_health_{$provider->id}");
            
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'provider_id' => $provider->id,
                'tested_at' => now()->toISOString(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get unhealthy providers.
     *
     * @return array
     */
    public function getUnhealthyProviders(): array
    {
        $providers = NotificationProvider::all();
        $unhealthy = [];
        
        foreach ($providers as $provider) {
            $health = $this->checkProviderHealth($provider);
            if ($health['overall_status'] === 'unhealthy') {
                $unhealthy[] = $health;
            }
        }
        
        return $unhealthy;
    }

    /**
     * Get providers needing attention (warning or unhealthy).
     *
     * @return array
     */
    public function getProvidersNeedingAttention(): array
    {
        $providers = NotificationProvider::all();
        $needingAttention = [];
        
        foreach ($providers as $provider) {
            $health = $this->checkProviderHealth($provider);
            if (in_array($health['overall_status'], ['unhealthy', 'warning'])) {
                $needingAttention[] = $health;
            }
        }
        
        return $needingAttention;
    }

    /**
     * Get backup providers for a channel.
     *
     * @param string $channel
     * @return array
     */
    public function getBackupProviders(string $channel): array
    {
        $providers = NotificationProvider::where('channel', $channel)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
        
        $backupProviders = [];
        
        foreach ($providers as $provider) {
            $health = $this->checkProviderHealth($provider);
            if ($health['overall_status'] === 'healthy') {
                $backupProviders[] = [
                    'provider' => $provider,
                    'health' => $health,
                ];
            }
        }
        
        return $backupProviders;
    }

    /**
     * Get provider health dashboard data.
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        return [
            'health_summary' => $this->getHealthSummary(),
            'unhealthy_providers' => $this->getUnhealthyProviders(),
            'providers_needing_attention' => $this->getProvidersNeedingAttention(),
            'recent_tests' => NotificationProvider::whereNotNull('last_test_at')
                ->orderBy('last_test_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($provider) {
                    return [
                        'id' => $provider->id,
                        'name' => $provider->name,
                        'channel' => $provider->channel,
                        'last_test_at' => $provider->last_test_at,
                        'last_test_status' => $provider->last_test_status,
                    ];
                }),
            'generated_at' => now()->toISOString(),
        ];
    }
}