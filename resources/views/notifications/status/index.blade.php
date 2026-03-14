<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Status Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --status-healthy: #28a745;
            --status-warning: #ffc107;
            --status-critical: #dc3545;
            --status-unknown: #6c757d;
        }
        
        .status-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
        }
        
        .status-healthy {
            border-left: 5px solid var(--status-healthy);
        }
        
        .status-warning {
            border-left: 5px solid var(--status-warning);
        }
        
        .status-critical {
            border-left: 5px solid var(--status-critical);
        }
        
        .status-unknown {
            border-left: 5px solid var(--status-unknown);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .badge-healthy {
            background-color: var(--status-healthy);
            color: white;
        }
        
        .badge-warning {
            background-color: var(--status-warning);
            color: black;
        }
        
        .badge-critical {
            background-color: var(--status-critical);
            color: white;
        }
        
        .badge-unknown {
            background-color: var(--status-unknown);
            color: white;
        }
        
        .metric-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .alert-card {
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 15px;
        }
        
        .alert-critical {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3">
                        <i class="bi bi-bell-fill me-2"></i>
                        Notification System Status Dashboard
                    </h1>
                    <div>
                        <span class="badge bg-secondary me-2" id="last-updated">Loading...</span>
                        <button class="btn btn-primary" onclick="refreshDashboard()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
                <p class="text-muted">Real-time monitoring of notification system health, performance, and errors</p>
            </div>
        </div>

        <!-- Overall Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card status-card status-{{ strtolower($health['overall_status']) }}">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="card-title mb-1">System Status</h3>
                                <p class="card-text">
                                    @if($health['overall_status'] === 'healthy')
                                        All systems operational. Notifications are being processed normally.
                                    @elseif($health['overall_status'] === 'warning')
                                        Some components require attention. System is operational but with degraded performance.
                                    @else
                                        System requires immediate attention. Some notifications may be delayed or failing.
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="status-badge badge-{{ strtolower($health['overall_status']) }}">
                                    {{ strtoupper($health['overall_status']) }}
                                </span>
                                <div class="mt-2">
                                    <small class="text-muted">Last updated: {{ now()->format('Y-m-d H:i:s') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-value text-primary" id="success-rate">
                        {{ number_format($health['metrics']['notifications']['success_rate'], 1) }}%
                    </div>
                    <div class="metric-label">Success Rate</div>
                    <small class="text-muted">Last 24 hours</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-value text-danger" id="error-rate">
                        {{ number_format($health['metrics']['notifications']['error_rate'], 1) }}%
                    </div>
                    <div class="metric-label">Error Rate</div>
                    <small class="text-muted">Last 24 hours</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-value text-success" id="processing-rate">
                        {{ number_format($health['metrics']['performance']['processing_rate_per_minute'], 0) }}
                    </div>
                    <div class="metric-label">Processing Rate</div>
                    <small class="text-muted">Notifications per minute</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-value text-warning" id="queue-backlog">
                        {{ $queueStats['total_jobs_pending'] ?? 0 }}
                    </div>
                    <div class="metric-label">Queue Backlog</div>
                    <small class="text-muted">Pending jobs</small>
                </div>
            </div>
        </div>

        <!-- Charts and Detailed Information -->
        <div class="row">
            <!-- Left Column: Components and Alerts -->
            <div class="col-md-6">
                <!-- System Components -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-gear-fill me-2"></i>
                            System Components
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Component</th>
                                        <th>Status</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($health['components'] as $name => $component)
                                    <tr>
                                        <td>
                                            <i class="bi bi-{{ $component['icon'] ?? 'gear' }} me-2"></i>
                                            {{ ucfirst(str_replace('_', ' ', $name)) }}
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $component['status'] === 'healthy' ? 'success' : ($component['status'] === 'warning' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($component['status']) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $component['message'] ?? 'No issues detected' }}</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Active Alerts -->
                @if(!empty($health['alerts']))
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Active Alerts ({{ count($health['alerts']) }})
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($health['alerts'] as $alert)
                        <div class="alert-card alert-{{ $alert['level'] }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="bi bi-{{ $alert['level'] === 'critical' ? 'exclamation-octagon' : 'exclamation-triangle' }} me-2"></i>
                                        {{ $alert['title'] }}
                                    </h6>
                                    <p class="mb-1">{{ $alert['message'] }}</p>
                                    <small class="text-muted">
                                        {{ $alert['timestamp'] }} • {{ $alert['component'] }}
                                    </small>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary" onclick="acknowledgeAlert('{{ $alert['id'] }}')">
                                    Acknowledge
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column: Charts and Statistics -->
            <div class="col-md-6">
                <!-- Delivery Statistics Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-bar-chart-fill me-2"></i>
                            Delivery Statistics (Last 24 Hours)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="deliveryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Error Statistics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-bug-fill me-2"></i>
                            Error Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="metric-card">
                                    <div class="metric-value text-danger">
                                        {{ $errorStats['total_errors'] ?? 0 }}
                                    </div>
                                    <div class="metric-label">Total Errors</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="metric-card">
                                    <div class="metric-value text-warning">
                                        {{ $errorStats['retryable_errors'] ?? 0 }}
                                    </div>
                                    <div class="metric-label">Retryable Errors</div>
                                </div>
                            </div>
                        </div>
                        
                        @if(!empty($errorStats['error_types']))
                        <h6 class="mt-3">Error Types</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Error Type</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($errorStats['error_types'] as $type => $count)
                                    <tr>
                                        <td>{{ $type }}</td>
                                        <td>{{ $count }}</td>
                                        <td>
                                            @php
                                                $percentage = $errorStats['total_errors'] > 0 ? ($count / $errorStats['total_errors'] * 100) : 0;
                                            @endphp
                                            {{ number_format($percentage, 1) }}%
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-lightning-fill me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <button class="btn btn-outline-primary w-100" onclick="runHealthCheck()">
                                    <i class="bi bi-heart-pulse"></i> Run Health Check
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-outline-warning w-100" onclick="retryFailedNotifications()">
                                    <i class="bi bi-arrow-repeat"></i> Retry Failed
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-outline-info w-100" onclick="clearCache()">
                                    <i class="bi bi-trash"></i> Clear Cache
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('notifications.settings.system') }}" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-gear"></i> System Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Provider Status -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-plug-fill me-2"></i>
                            Notification Providers
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row" id="providers-container">
                            <!-- Providers will be loaded via AJAX -->
                            <div class="col-12 text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="btn btn-primary refresh-btn rounded-circle" onclick="refreshDashboard()" title="Refresh Dashboard">
        <i class="bi bi-arrow-clockwise"></i>
    </button>

    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            updateLastUpdated();
            loadProviders();
            initializeCharts();
            
            // Auto-refresh every 60 seconds
            setInterval(refreshDashboard, 60000);
        });

        function updateLastUpdated() {
            const now = new Date();
            const formatted = now.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('last-updated').textContent = `Last updated: ${formatted}`;
        }

        function refreshDashboard() {
            location.reload();
        }

        function loadProviders() {
            fetch('/notifications/status/providers')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('providers-container');
                    container.innerHTML = '';
                    
                    data.providers.forEach(provider => {
                        const statusClass = provider.status === 'healthy' ? 'success' : 
                                          provider.status === 'warning' ? 'warning' : 'danger';
                        
                        const card = `
                            <div class="col-md-3 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title mb-1">${provider.name}</h6>
                                                <small class="text-muted">${provider.type}</small>
                                            </div>
                                            <span class="badge bg-${statusClass}">${provider.status}</span>
                                        </div>
                                        <div class="mt-3">
                                            <small class="d-block">
                                                <i class="bi bi-${provider.is_active ? 'check-circle text-success' : 'x-circle text-danger'} me-1"></i>
                                                ${provider.is_active ? 'Active' : 'Inactive'}
                                            </small>
                                            <small class="d-block">
                                                <i class="bi bi-${provider.is_default ? 'star-fill text-warning' : 'star'} me-1"></i>
                                                ${provider.is_default ? 'Default' : 'Not default'}
                                            </small>
                                            <small class="d-block text-muted">
                                                Failure rate: ${provider.failure_rate || 0}%
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        container.innerHTML += card;
                    });
                })
                .catch(error => {
                    console.error('Error loading providers:', error);
                    document.getElementById('providers-container').innerHTML = `
                        <div class="col-12 text-center">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                Failed to load provider data
                            </div>
                        </div>
                    `;
                });
        }

        function initializeCharts() {
            // Delivery statistics chart
            const ctx = document.getElementById('deliveryChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
                    datasets: [{
                        label: 'Successful',
                        data: [120, 190, 300, 500, 200, 300],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Failed',
                        data: [5, 10, 15, 8, 12, 7],
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Notifications'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Time'
                            }
                        }
                    }
                }
            });
        }

        function acknowledgeAlert(alertId) {
            fetch(`/notifications/status/alerts/${alertId}/acknowledge`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ notes: 'Acknowledged via dashboard' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.acknowledged) {
                    showToast('Alert acknowledged successfully', 'success');
                    setTimeout(refreshDashboard, 1000);
                }
            })
            .catch(error => {
                console.error('Error acknowledging alert:', error);
                showToast('Failed to acknowledge alert', 'error');
            });
        }

        function runHealthCheck() {
            fetch('/notifications/status/health-check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                showToast('Health check completed successfully', 'success');
                setTimeout(refreshDashboard, 1500);
            })
            .catch(error => {
                console.error('Error running health check:', error);
                showToast('Failed to run health check', 'error');
            });
        }

        function retryFailedNotifications() {
            if (!confirm('Retry all failed notifications? This may take a few moments.')) {
                return;
            }
            
            fetch('/notifications/status/retry-failed', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                showToast(`Retried ${data.results?.retried_count || 0} notifications`, 'success');
                setTimeout(refreshDashboard, 2000);
            })
            .catch(error => {
                console.error('Error retrying notifications:', error);
                showToast('Failed to retry notifications', 'error');
            });
        }

        function clearCache() {
            if (!confirm('Clear all notification cache? This may temporarily increase load on the system.')) {
                return;
            }
            
            fetch('/notifications/status/clear-cache', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                showToast('Cache cleared successfully', 'success');
                setTimeout(refreshDashboard, 1000);
            })
            .catch(error => {
                console.error('Error clearing cache:', error);
                showToast('Failed to clear cache', 'error');
            });
        }

        function showToast(message, type = 'info') {
            // Create toast element
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0"
                     role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            // Add to container
            const container = document.getElementById('toast-container') || createToastContainer();
            container.innerHTML += toastHtml;
            
            // Show toast
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
            toast.show();
            
            // Remove after hide
            toastElement.addEventListener('hidden.bs.toast', function () {
                toastElement.remove();
            });
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1050';
            document.body.appendChild(container);
            return container;
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</body>
</html>