<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the notification system including
    | alerting, monitoring, and health check settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how alerts are sent when issues are detected in the
    | notification system.
    |
    */
    'alert_channels' => [
        'log', // Always log alerts
        // 'email', // Uncomment to enable email alerts
        // 'slack', // Uncomment to enable Slack alerts
        // 'webhook', // Uncomment to enable webhook alerts
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Define thresholds for when alerts should be triggered.
    | Values are percentages unless otherwise specified.
    |
    */
    'alert_thresholds' => [
        'error_rate' => 10, // Alert if error rate exceeds 10%
        'queue_backlog' => 100, // Alert if queue backlog exceeds 100 jobs
        'processing_time' => 5000, // Alert if average processing time exceeds 5000ms
        'storage_usage' => 80, // Alert if storage usage exceeds 80%
        'memory_usage' => 85, // Alert if memory usage exceeds 85%
        'cpu_usage' => 90, // Alert if CPU usage exceeds 90%
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the notification system health checks.
    |
    */
    'health_check' => [
        'enabled' => true,
        'frequency' => 300, // Seconds between health checks (5 minutes)
        'detailed_logging' => env('NOTIFICATION_HEALTH_DETAILED_LOGGING', false),
        'alert_on_warning' => env('NOTIFICATION_ALERT_ON_WARNING', false),
        'alert_on_critical' => env('NOTIFICATION_ALERT_ON_CRITICAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for system monitoring and metrics collection.
    |
    */
    'monitoring' => [
        'collect_metrics' => true,
        'metrics_retention_days' => 30,
        'enable_real_time_monitoring' => env('NOTIFICATION_REAL_TIME_MONITORING', false),
        'dashboard_refresh_interval' => 60, // Seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for error handling and retry logic.
    |
    */
    'error_handling' => [
        'max_retry_attempts' => 3,
        'retry_delay_seconds' => 60, // Initial delay
        'retry_backoff_multiplier' => 2, // Exponential backoff multiplier
        'circuit_breaker_threshold' => 5, // Number of failures before circuit opens
        'circuit_breaker_timeout' => 300, // Seconds before circuit half-opens
        'enable_auto_retry' => true,
        'dead_letter_queue_enabled' => true,
        'dead_letter_queue_retention_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimization and caching.
    |
    */
    'performance' => [
        'cache_enabled' => true,
        'cache_ttl' => 300, // Seconds (5 minutes)
        'batch_processing_enabled' => true,
        'batch_size' => 100,
        'queue_optimization_enabled' => true,
        'enable_query_caching' => true,
        'enable_response_caching' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for email alerts (if enabled).
    |
    */
    'email_alerts' => [
        'enabled' => false,
        'recipients' => [
            // Add email addresses to receive alerts
            // 'admin@example.com',
        ],
        'from_address' => env('MAIL_FROM_ADDRESS', 'notifications@example.com'),
        'from_name' => env('MAIL_FROM_NAME', 'Notification System'),
        'subject_prefix' => '[Notification Alert] ',
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Slack alerts (if enabled).
    |
    */
    'slack_alerts' => [
        'enabled' => false,
        'webhook_url' => env('NOTIFICATION_SLACK_WEBHOOK_URL'),
        'channel' => env('NOTIFICATION_SLACK_CHANNEL', '#notifications'),
        'username' => env('NOTIFICATION_SLACK_USERNAME', 'Notification Bot'),
        'icon_emoji' => env('NOTIFICATION_SLACK_ICON_EMOJI', ':bell:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for webhook alerts (if enabled).
    |
    */
    'webhook_alerts' => [
        'enabled' => false,
        'url' => env('NOTIFICATION_WEBHOOK_URL'),
        'secret' => env('NOTIFICATION_WEBHOOK_SECRET'),
        'timeout' => 5, // Seconds
        'retry_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the notification system status dashboard.
    |
    */
    'dashboard' => [
        'enabled' => true,
        'require_authentication' => true,
        'require_admin_role' => true,
        'refresh_interval' => 30000, // Milliseconds (30 seconds)
        'show_sensitive_data' => false,
        'max_history_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Health Check Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for checking the health of notification providers.
    |
    */
    'provider_health_checks' => [
        'enabled' => true,
        'frequency' => 600, // Seconds (10 minutes)
        'timeout' => 10, // Seconds
        'failure_threshold' => 3, // Number of consecutive failures before marking as unhealthy
        'recovery_threshold' => 2, // Number of consecutive successes before marking as healthy
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for notification system logging.
    |
    */
    'logging' => [
        'enabled' => true,
        'level' => env('NOTIFICATION_LOG_LEVEL', 'info'),
        'channel' => env('NOTIFICATION_LOG_CHANNEL', 'daily'),
        'max_files' => 14, // Days to keep log files
        'log_errors' => true,
        'log_warnings' => true,
        'log_info' => true,
        'log_debug' => env('APP_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for maintenance mode behavior.
    |
    */
    'maintenance' => [
        'queue_notifications' => true, // Queue notifications during maintenance
        'retry_after_maintenance' => true,
        'maintenance_message' => 'Notification system is undergoing maintenance. Notifications will be queued and sent once maintenance is complete.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limiting notifications.
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'max_notifications_per_minute' => 1000,
        'max_notifications_per_hour' => 10000,
        'max_notifications_per_day' => 100000,
        'max_notifications_per_user_per_day' => 100,
        'throttle_by_ip' => true,
        'throttle_by_user' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Blacklist/Whitelist Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for blacklisting/whitelisting notifications.
    |
    */
    'filtering' => [
        'enable_blacklist' => true,
        'enable_whitelist' => false,
        'blacklist_emails' => [
            // Add email addresses to blacklist
            // 'spam@example.com',
        ],
        'blacklist_domains' => [
            // Add domains to blacklist
            // 'spamdomain.com',
        ],
        'whitelist_emails' => [
            // Add email addresses to whitelist (if whitelist is enabled)
        ],
        'whitelist_domains' => [
            // Add domains to whitelist (if whitelist is enabled)
        ],
    ],
];