@php
use App\Models\SupplyAlert;
use App\Services\AlertService;

// Get alert statistics
$alertService = app(AlertService::class);
$alertStats = $alertService->getAlertStatistics();

// Get recent alerts
$recentAlerts = SupplyAlert::with(['supply'])
    ->where('is_resolved', false)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();
@endphp

<!-- Inventory Alert Widgets -->
<div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 mt-3">
    <!-- Total Alerts Widget -->
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                            <i class="ti ti-bell"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <h3 class="mb-2 fw-normal">{{ $alertStats['total'] }}</h3>
                        <p class="mb-0 text-muted"><span>Total Alerts</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- end col -->
    
    <!-- Unresolved Alerts Widget -->
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-24">
                            <i class="ti ti-alert-triangle"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <h3 class="mb-2 fw-normal">{{ $alertStats['unresolved'] }}</h3>
                        <p class="mb-0 text-muted"><span>Unresolved</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- end col -->
    
    <!-- Critical Alerts Widget -->
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-24">
                            <i class="ti ti-alert-circle"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <h3 class="mb-2 fw-normal">{{ $alertStats['critical'] }}</h3>
                        <p class="mb-0 text-muted"><span>Critical</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- end col -->
    
    <!-- Warning Alerts Widget -->
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-24">
                            <i class="ti ti-info-circle"></i>
                        </span>
                    </div>
                    <div class="text-end">
                        <h3 class="mb-2 fw-normal">{{ $alertStats['warning'] }}</h3>
                        <p class="mb-0 text-muted"><span>Warnings</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- end col -->
</div><!-- end row -->

<!-- Recent Alerts Card -->
@if($recentAlerts->count() > 0)
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header justify-content-between align-items-center border-dashed">
                <h4 class="card-title mb-0">Recent Inventory Alerts</h4>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-primary" href="{{ route('inventory.alerts.index') }}">
                        <i class="ti ti-list me-1"></i> View All
                    </a>
                    @can('inventory.alerts.manage')
                    <button class="btn btn-sm btn-success" id="resolveAllAlertsBtn">
                        <i class="ti ti-check me-1"></i> Resolve All
                    </button>
                    @endcan
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-custom table-sm table-nowrap table-hover mb-0">
                        <tbody>
                            @foreach($recentAlerts as $alert)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs rounded-circle me-2">
                                            <span class="avatar-title rounded-circle bg-{{ $alert->severity === 'critical' ? 'danger' : ($alert->severity === 'warning' ? 'warning' : 'info') }}-subtle text-{{ $alert->severity === 'critical' ? 'danger' : ($alert->severity === 'warning' ? 'warning' : 'info') }}">
                                                <i class="ti ti-{{ $alert->alert_type === 'low_stock' ? 'alert-triangle' : ($alert->alert_type === 'out_of_stock' ? 'x' : ($alert->alert_type === 'expiring_soon' ? 'clock' : 'calendar')) }} fs-xs"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-muted fs-xs">{{ $alert->supply->name ?? 'Unknown Supply' }}</span>
                                            <h5 class="fs-base mb-0">
                                                <a class="text-body" href="{{ route('inventory.alerts.show', $alert->id) }}">
                                                    {{ Str::limit($alert->message, 50) }}
                                                </a>
                                            </h5>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted fs-xs">Type</span>
                                    <h5 class="fs-base fw-normal mb-0">
                                        <span class="badge bg-{{ $alert->alert_type === 'low_stock' ? 'warning' : ($alert->alert_type === 'out_of_stock' ? 'danger' : ($alert->alert_type === 'expiring_soon' ? 'info' : 'secondary')) }}-subtle text-{{ $alert->alert_type === 'low_stock' ? 'warning' : ($alert->alert_type === 'out_of_stock' ? 'danger' : ($alert->alert_type === 'expiring_soon' ? 'info' : 'secondary')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}
                                        </span>
                                    </h5>
                                </td>
                                <td>
                                    <span class="text-muted fs-xs">Stock</span>
                                    <h5 class="fs-base fw-normal mb-0">
                                        @if($alert->supply)
                                            @include('inventory.supplies.partials.stock-badge', ['supply' => $alert->supply])
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </h5>
                                </td>
                                <td>
                                    <span class="text-muted fs-xs">Created</span>
                                    <h5 class="fs-base fw-normal mb-0">{{ $alert->created_at->diffForHumans() }}</h5>
                                </td>
                                <td style="width: 30px;">
                                    <div class="dropdown">
                                        <a class="dropdown-toggle text-muted drop-arrow-none card-drop p-0"
                                            data-bs-toggle="dropdown" href="#">
                                            <i class="ti ti-dots-vertical fs-lg"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('inventory.alerts.show', $alert->id) }}">
                                                <i class="ti ti-eye me-1"></i> View Details
                                            </a>
                                            @if(!$alert->is_resolved)
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#resolveModal{{ $alert->id }}">
                                                <i class="ti ti-check me-1"></i> Resolve
                                            </a>
                                            @endif
                                            @can('inventory.alerts.manage')
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#" onclick="confirmDelete('{{ $alert->id }}', 'Alert #{{ $alert->id }}')">
                                                <i class="ti ti-trash me-1"></i> Delete
                                            </a>
                                            @endcan
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> <!-- end table-responsive-->
            </div> <!-- end card-body-->
            <div class="card-footer border-0">
                <div class="align-items-center justify-content-between row text-center text-sm-start">
                    <div class="col-sm">
                        <div class="text-muted">
                            <i class="ti ti-info-circle me-1"></i>
                            {{ $alertStats['unresolved'] }} unresolved alerts requiring attention
                        </div>
                    </div>
                    <div class="col-sm-auto mt-3 mt-sm-0">
                        <a href="{{ route('inventory.alerts.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-arrow-right me-1"></i> Manage Alerts
                        </a>
                    </div> <!-- end col-->
                </div> <!-- end row-->
            </div> <!-- end card-footer-->
        </div> <!-- end card-->
    </div> <!-- end col-->
</div> <!-- end row-->

<!-- Resolve Modals for Recent Alerts -->
@foreach($recentAlerts as $alert)
    @if(!$alert->is_resolved)
        @include('inventory.alerts.partials.resolve-modal', ['alert' => $alert])
    @endif
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Resolve All Alerts button
    const resolveAllBtn = document.getElementById('resolveAllAlertsBtn');
    if (resolveAllBtn) {
        resolveAllBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Resolve All Alerts',
                text: 'Are you sure you want to resolve all unresolved alerts?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, resolve all',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Resolving Alerts',
                        text: 'Please wait...',
                        icon: 'info',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });

                    // Get all alert IDs
                    const alertIds = @json($recentAlerts->pluck('id')->toArray());
                    
                    fetch('{{ route("inventory.alerts.bulk-resolve") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({
                            alert_ids: alertIds,
                            resolution_notes: 'Resolved from dashboard',
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Alerts Resolved',
                                text: data.message || 'All alerts have been resolved.',
                                icon: 'success',
                                confirmButtonText: 'OK',
                                buttonsStyling: false,
                                customClass: {
                                    confirmButton: 'btn btn-primary',
                                },
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'Failed to resolve alerts.');
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error',
                            text: error.message,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-danger',
                            },
                        });
                    });
                }
            });
        });
    }

    // Auto-refresh alert widgets every 60 seconds
    setInterval(() => {
        fetch('{{ route("alerts.statistics") }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update widget counts
                const widgets = document.querySelectorAll('.card .fw-normal');
                if (widgets.length >= 4) {
                    widgets[0].textContent = data.data.total; // Total Alerts
                    widgets[1].textContent = data.data.unresolved; // Unresolved
                    widgets[2].textContent = data.data.critical; // Critical
                    widgets[3].textContent = data.data.warning; // Warning
                }
            }
        })
        .catch(() => {
            // Silently fail - network issues are okay for polling
        });
    }, 60000); // 60 seconds
});
</script>
@endif