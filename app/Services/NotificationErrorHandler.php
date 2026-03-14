<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class NotificationErrorHandler
{
    protected $maxRetryAttempts = 3;
    protected $retryDelay = 300; // 5 minutes in seconds
    protected $circuitBreakerThreshold = 10;
    protected $circuitBreakerTimeout = 300; // 5 minutes

    /**
     * Handle notification sending error
     */
    public function handleError(Notification $notification, Throwable $exception, array $context = []): void
    {
        $errorCode = $this->getErrorCode($exception);
        $errorContext = $this->buildErrorContext($exception, $context);
        
        // Log the error
        $this->logError($notification, $errorCode, $exception->getMessage(), $errorContext);
        
        // Update notification status
        $this->updateNotificationStatus($notification, $errorCode);
        
        // Check if we should retry
        if ($this->shouldRetry($notification, $errorCode)) {
            $this->scheduleRetry($notification);
        } else {
            $this->markAsFailed($notification, $errorCode);
        }
        
        // Update circuit breaker if needed
        $this->updateCircuitBreaker($notification, $errorCode);
        
        // Send alert if critical error
        if ($this->isCriticalError($errorCode)) {
            $this->sendAlert($notification, $errorCode, $exception->getMessage());
        }
    }

    /**
     * Get standardized error code from exception
     */
    private function getErrorCode(Throwable $exception): string
    {
        $message = $exception->getMessage();
        
        // Map common error messages to error codes
        if (str_contains($message, 'Connection refused') || 
            str_contains($message, 'Connection timed out')) {
            return 'CONNECTION_ERROR';
        }
        
        if (str_contains($message, 'Authentication failed') || 
            str_contains($message, 'Invalid credentials')) {
            return 'AUTHENTICATION_ERROR';
        }
        
        if (str_contains($message, 'Rate limit exceeded') || 
            str_contains($message, 'Too many requests')) {
            return 'RATE_LIMIT_ERROR';
        }
        
        if (str_contains($message, 'Invalid recipient') || 
            str_contains($message, 'Email address')) {
            return 'INVALID_RECIPIENT';
        }
        
        if (str_contains($message, 'Attachment') || 
            str_contains($message, 'File')) {
            return 'ATTACHMENT_ERROR';
        }
        
        if (str_contains($message, 'Template') || 
            str_contains($message, 'Variable')) {
            return 'TEMPLATE_ERROR';
        }
        
        return 'UNKNOWN_ERROR';
    }

    /**
     * Build error context for logging
     */
    private function buildErrorContext(Throwable $exception, array $context): array
    {
        return [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Log error to database
     */
    private function logError(Notification $notification, string $errorCode, string $message, array $context): void
    {
        try {
            NotificationLog::create([
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'action' => 'send',
                'status' => 'error',
                'error_code' => $errorCode,
                'error_message' => $message,
                'context' => $context,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'system',
            ]);
        } catch (Throwable $e) {
            // Fallback to file logging if database logging fails
            Log::error('Failed to log notification error', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'original_error' => $message,
            ]);
        }
    }

    /**
     * Update notification status based on error
     */
    private function updateNotificationStatus(Notification $notification, string $errorCode): void
    {
        $notification->update([
            'status' => 'failed',
            'last_attempt_at' => now(),
            'retry_count' => $notification->retry_count + 1,
            'metadata' => array_merge($notification->metadata ?? [], [
                'last_error' => $errorCode,
                'last_error_at' => now()->toISOString(),
                'retry_attempts' => $notification->retry_count + 1,
            ]),
        ]);
    }

    /**
     * Determine if notification should be retried
     */
    private function shouldRetry(Notification $notification, string $errorCode): bool
    {
        // Don't retry certain error types
        $nonRetryableErrors = [
            'INVALID_RECIPIENT',
            'AUTHENTICATION_ERROR',
            'TEMPLATE_ERROR',
        ];
        
        if (in_array($errorCode, $nonRetryableErrors)) {
            return false;
        }
        
        // Check retry count
        if ($notification->retry_count >= $this->maxRetryAttempts) {
            return false;
        }
        
        // Check if error is transient
        if (!$this->isTransientError($errorCode)) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if error is transient (can be retried)
     */
    private function isTransientError(string $errorCode): bool
    {
        $transientErrors = [
            'CONNECTION_ERROR',
            'RATE_LIMIT_ERROR',
            'TIMEOUT_ERROR',
            'SERVER_ERROR',
            'UNKNOWN_ERROR',
        ];
        
        return in_array($errorCode, $transientErrors);
    }

    /**
     * Schedule notification for retry
     */
    private function scheduleRetry(Notification $notification): void
    {
        $retryDelay = $this->calculateRetryDelay($notification->retry_count);
        $scheduledAt = now()->addSeconds($retryDelay);
        
        $notification->update([
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
            'metadata' => array_merge($notification->metadata ?? [], [
                'next_retry_at' => $scheduledAt->toISOString(),
                'retry_delay' => $retryDelay,
            ]),
        ]);
        
        Log::info('Notification scheduled for retry', [
            'notification_id' => $notification->id,
            'retry_count' => $notification->retry_count,
            'scheduled_at' => $scheduledAt,
            'delay_seconds' => $retryDelay,
        ]);
    }

    /**
     * Calculate retry delay with exponential backoff
     */
    private function calculateRetryDelay(int $retryCount): int
    {
        // Exponential backoff: 5min, 15min, 45min
        return $this->retryDelay * pow(3, $retryCount - 1);
    }

    /**
     * Mark notification as permanently failed
     */
    private function markAsFailed(Notification $notification, string $errorCode): void
    {
        $notification->update([
            'status' => 'failed',
            'metadata' => array_merge($notification->metadata ?? [], [
                'permanently_failed' => true,
                'failure_reason' => $errorCode,
                'failed_at' => now()->toISOString(),
            ]),
        ]);
        
        Log::warning('Notification marked as permanently failed', [
            'notification_id' => $notification->id,
            'error_code' => $errorCode,
            'retry_count' => $notification->retry_count,
        ]);
    }

    /**
     * Update circuit breaker for provider
     */
    private function updateCircuitBreaker(Notification $notification, string $errorCode): void
    {
        $providerId = $notification->metadata['provider_id'] ?? null;
        
        if (!$providerId || !$this->isCircuitBreakerError($errorCode)) {
            return;
        }
        
        $cacheKey = "circuit_breaker:provider:{$providerId}";
        $failureCount = Cache::get($cacheKey, 0) + 1;
        
        Cache::put($cacheKey, $failureCount, $this->circuitBreakerTimeout);
        
        if ($failureCount >= $this->circuitBreakerThreshold) {
            $this->tripCircuitBreaker($providerId);
        }
    }

    /**
     * Check if error should trigger circuit breaker
     */
    private function isCircuitBreakerError(string $errorCode): bool
    {
        $circuitBreakerErrors = [
            'CONNECTION_ERROR',
            'AUTHENTICATION_ERROR',
            'SERVER_ERROR',
        ];
        
        return in_array($errorCode, $circuitBreakerErrors);
    }

    /**
     * Trip circuit breaker for provider
     */
    private function tripCircuitBreaker(string $providerId): void
    {
        $cacheKey = "circuit_breaker:tripped:{$providerId}";
        Cache::put($cacheKey, true, $this->circuitBreakerTimeout);
        
        // Update provider status
        NotificationProvider::where('id', $providerId)->update([
            'health_status' => 'unhealthy',
            'last_health_check' => now(),
        ]);
        
        Log::critical('Circuit breaker tripped for provider', [
            'provider_id' => $providerId,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check if circuit breaker is tripped for provider
     */
    public function isCircuitBreakerTripped(string $providerId): bool
    {
        $cacheKey = "circuit_breaker:tripped:{$providerId}";
        return Cache::get($cacheKey, false);
    }

    /**
     * Reset circuit breaker for provider
     */
    public function resetCircuitBreaker(string $providerId): void
    {
        $cacheKey = "circuit_breaker:tripped:{$providerId}";
        Cache::forget($cacheKey);
        
        $failureCountKey = "circuit_breaker:provider:{$providerId}";
        Cache::forget($failureCountKey);
        
        Log::info('Circuit breaker reset for provider', [
            'provider_id' => $providerId,
        ]);
    }

    /**
     * Check if error is critical (requires immediate attention)
     */
    private function isCriticalError(string $errorCode): bool
    {
        $criticalErrors = [
            'AUTHENTICATION_ERROR',
            'CONNECTION_ERROR',
            'SERVER_ERROR',
        ];
        
        return in_array($errorCode, $criticalErrors);
    }

    /**
     * Send alert for critical error
     */
    private function sendAlert(Notification $notification, string $errorCode, string $message): void
    {
        // Implement alerting logic here
        // This could be:
        // - Email to administrators
        // - Slack/Teams notification
        // - PagerDuty alert
        // - SMS alert
        
        $alertData = [
            'notification_id' => $notification->id,
            'error_code' => $errorCode,
            'error_message' => $message,
            'user_id' => $notification->user_id,
            'notification_type' => $notification->notification_type,
            'timestamp' => now()->toISOString(),
            'retry_count' => $notification->retry_count,
        ];
        
        // For now, log the alert
        Log::critical('Notification critical error alert', $alertData);
        
        // TODO: Implement actual alerting mechanism
        // $this->alertService->sendCriticalAlert('notification_error', $alertData);
    }

    /**
     * Get error statistics
     */
    public function getErrorStatistics(string $period = 'day'): array
    {
        $startDate = match($period) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subDay(),
        };
        
        $errors = DB::table('notification_logs')
            ->select('error_code', DB::raw('COUNT(*) as count'))
            ->where('status', 'error')
            ->where('created_at', '>=', $startDate)
            ->groupBy('error_code')
            ->orderBy('count', 'desc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->error_code => $item->count];
            })
            ->toArray();
        
        $totalErrors = array_sum($errors);
        
        return [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => now(),
            'total_errors' => $totalErrors,
            'errors_by_code' => $errors,
            'error_rate' => $this->calculateErrorRate($startDate),
        ];
    }

    /**
     * Calculate error rate for period
     */
    private function calculateErrorRate(\DateTimeInterface $startDate): float
    {
        $totalNotifications = DB::table('notifications')
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $failedNotifications = DB::table('notifications')
            ->where('status', 'failed')
            ->where('created_at', '>=', $startDate)
            ->count();
        
        if ($totalNotifications === 0) {
            return 0.0;
        }
        
        return round(($failedNotifications / $totalNotifications) * 100, 2);
    }

    /**
     * Get failed notifications that can be retried
     */
    public function getRetryableNotifications(int $limit = 100): array
    {
        return Notification::where('status', 'failed')
            ->where('retry_count', '<', $this->maxRetryAttempts)
            ->where(function ($query) {
                $query->whereNull('last_attempt_at')
                    ->orWhere('last_attempt_at', '<', now()->subMinutes(5));
            })
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Batch retry failed notifications
     */
    public function batchRetryFailedNotifications(array $notificationIds): array
    {
        $results = [
            'total' => count($notificationIds),
            'successful' => 0,
            'failed' => 0,
            'details' => [],
        ];
        
        foreach ($notificationIds as $notificationId) {
            try {
                $notification = Notification::find($notificationId);
                
                if (!$notification) {
                    $results['failed']++;
                    $results['details'][] = [
                        'id' => $notificationId,
                        'status' => 'error',
                        'message' => 'Notification not found',
                    ];
                    continue;
                }
                
                if ($notification->status !== 'failed') {
                    $results['failed']++;
                    $results['details'][] = [
                        'id' => $notificationId,
                        'status' => 'error',
                        'message' => 'Notification is not in failed state',
                    ];
                    continue;
                }
                
                if ($notification->retry_count >= $this->maxRetryAttempts) {
                    $results['failed']++;
                    $results['details'][] = [
                        'id' => $notificationId,
                        'status' => 'error',
                        'message' => 'Max retry attempts exceeded',
                    ];
                    continue;
                }
                
                // Schedule for retry
                $this->scheduleRetry($notification);
                
                $results['successful']++;
                $results['details'][] = [
                    'id' => $notificationId,
                    'status' => 'success',
                    'message' => 'Scheduled for retry',
                    'scheduled_at' => $notification->scheduled_at,
                ];
            } catch (Throwable $e) {
                $results['failed']++;
                $results['details'][] = [
                    'id' => $notificationId,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Clean up old error logs
     */
    public function cleanupOldErrorLogs(int $days = 30): array
    {
        $deletedCount = NotificationLog::where('created_at', '<', now()->subDays($days))
            ->where('status', 'error')
            ->delete();
        
        return [
            'deleted_count' => $deletedCount,
            'days' => $days,
            'timestamp' => now()->toISOString(),
        ];
    }
}