@extends('layouts.vertical', ['title' => 'Alert Details'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Alert Details'])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Alert #{{ $alert->id }}</h4>
                        <p class="text-muted mb-0">Detailed view of inventory alert</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @if(!$alert->is_resolved)
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resolveModal">
                                <i class="ti ti-check me-1"></i> Resolve Alert
                            </button>
                        @endif
                        <a href="{{ route('inventory.alerts.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back to Alerts
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="text-uppercase text-muted mb-2">Alert Information</h6>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Alert Type:</span>
                                        <span class="fw-semibold">
                                            <span class="badge bg-{{ $alert->alert_type === 'low_stock' ? 'warning' : ($alert->alert_type === 'out_of_stock' ? 'danger' : ($alert->alert_type === 'expiring_soon' ? 'info' : 'secondary')) }}-subtle text-{{ $alert->alert_type === 'low_stock' ? 'warning' : ($alert->alert_type === 'out_of_stock' ? 'danger' : ($alert->alert_type === 'expiring_soon' ? 'info' : 'secondary')) }}">
                                                {{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Severity:</span>
                                        <span class="fw-semibold">
                                            @if($alert->severity === 'critical')
                                                <span class="badge bg-danger-subtle text-danger">
                                                    <i class="ti ti-alert-circle fs-xs"></i> Critical
                                                </span>
                                            @elseif($alert->severity === 'warning')
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ti ti-alert-triangle fs-xs"></i> Warning
                                                </span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">
                                                    <i class="ti ti-info-circle fs-xs"></i> Info
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Status:</span>
                                        <span class="fw-semibold">
                                            @if($alert->is_resolved)
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ti ti-check fs-xs"></i> Resolved
                                                </span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ti ti-clock fs-xs"></i> Unresolved
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Created:</span>
                                        <span class="fw-semibold">{{ $alert->created_at->format('d M, Y H:i:s') }}</span>
                                    </div>
                                    @if($alert->is_resolved)
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Resolved:</span>
                                            <span class="fw-semibold">{{ $alert->resolved_at->format('d M, Y H:i:s') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Resolved By:</span>
                                            <span class="fw-semibold">{{ $alert->resolver->name ?? 'Unknown' }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="text-uppercase text-muted mb-2">Supply Information</h6>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Supply:</span>
                                        <span class="fw-semibold">
                                            @if($alert->supply)
                                                <a href="{{ route('inventory.supplies.show', $alert->supply_id) }}" class="text-primary">
                                                    {{ $alert->supply->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Supply deleted</span>
                                            @endif
                                        </span>
                                    </div>
                                    @if($alert->supply)
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">SKU:</span>
                                            <span class="fw-semibold">{{ $alert->supply->sku }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Category:</span>
                                            <span class="fw-semibold">{{ $alert->supply->category->name ?? 'Uncategorized' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Current Stock:</span>
                                            <span class="fw-semibold">
                                                @include('inventory.supplies.partials.stock-badge', ['supply' => $alert->supply])
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Min Stock Level:</span>
                                            <span class="fw-semibold">{{ $alert->min_stock_level }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="text-uppercase text-muted mb-2">Alert Message</h6>
                        <div class="alert alert-{{ $alert->severity === 'critical' ? 'danger' : ($alert->severity === 'warning' ? 'warning' : 'info') }}">
                            <div class="d-flex">
                                <div class="me-2">
                                    <i class="ti ti-{{ $alert->severity === 'critical' ? 'alert-circle' : ($alert->severity === 'warning' ? 'alert-triangle' : 'info-circle') }} fs-lg"></i>
                                </div>
                                <div>
                                    <p class="mb-0">{{ $alert->message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($alert->expiry_date)
                        <div class="mb-4">
                            <h6 class="text-uppercase text-muted mb-2">Expiry Information</h6>
                            <div class="alert alert-{{ $alert->expiry_date->isPast() ? 'danger' : ($alert->expiry_date->diffInDays(now()) <= 7 ? 'warning' : 'info') }}">
                                <div class="d-flex">
                                    <div class="me-2">
                                        <i class="ti ti-calendar fs-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="alert-heading mb-1">
                                            @if($alert->expiry_date->isPast())
                                                Expired
                                            @elseif($alert->expiry_date->diffInDays(now()) <= 7)
                                                Expiring Soon
                                            @else
                                                Expiry Date
                                            @endif
                                        </h6>
                                        <p class="mb-0">
                                            <strong>Date:</strong> {{ $alert->expiry_date->format('d M, Y') }}<br>
                                            <strong>Status:</strong> 
                                            @if($alert->expiry_date->isPast())
                                                <span class="text-danger">Expired {{ $alert->expiry_date->diffForHumans() }}</span>
                                            @else
                                                <span class="{{ $alert->expiry_date->diffInDays(now()) <= 7 ? 'text-warning' : 'text-success' }}">
                                                    {{ $alert->expiry_date->diffForHumans() }} remaining
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            @if($relatedAlerts->count() > 0)
                <div class="card mt-3">
                    <div class="card-header border-light">
                        <h5 class="card-title mb-0">Related Alerts</h5>
                        <p class="text-muted mb-0">Other alerts for the same supply</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($relatedAlerts as $relatedAlert)
                                        <tr>
                                            <td>{{ $relatedAlert->id }}</td>
                                            <td>
                                                <span class="badge bg-{{ $relatedAlert->alert_type === 'low_stock' ? 'warning' : ($relatedAlert->alert_type === 'out_of_stock' ? 'danger' : ($relatedAlert->alert_type === 'expiring_soon' ? 'info' : 'secondary')) }}-subtle text-{{ $relatedAlert->alert_type === 'low_stock' ? 'warning' : ($relatedAlert->alert_type === 'out_of_stock' ? 'danger' : ($relatedAlert->alert_type === 'expiring_soon' ? 'info' : 'secondary')) }}">
                                                    {{ ucfirst(str_replace('_', ' ', $relatedAlert->alert_type)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($relatedAlert->severity === 'critical')
                                                    <span class="badge bg-danger-subtle text-danger">Critical</span>
                                                @elseif($relatedAlert->severity === 'warning')
                                                    <span class="badge bg-warning-subtle text-warning">Warning</span>
                                                @else
                                                    <span class="badge bg-info-subtle text-info">Info</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($relatedAlert->is_resolved)
                                                    <span class="badge bg-success-subtle text-success">Resolved</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">Unresolved</span>
                                                @endif
                                            </td>
                                            <td>{{ $relatedAlert->created_at->format('d M, Y') }}</td>
                                            <td>
                                                <a href="{{ route('inventory.alerts.show', $relatedAlert->id) }}" class="btn btn-light btn-sm">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($alert->supply)
                            <a href="{{ route('inventory.supplies.show', $alert->supply_id) }}" class="btn btn-outline-primary">
                                <i class="ti ti-package me-1"></i> View Supply Details
                            </a>
                            @if(!$alert->is_resolved && ($alert->alert_type === 'low_stock' || $alert->alert_type === 'out_of_stock'))
                                <a href="{{ route('inventory.purchase-orders.create') }}?supply_id={{ $alert->supply_id }}" class="btn btn-outline-success">
                                    <i class="ti ti-shopping-cart me-1"></i> Create Purchase Order
                                </a>
                            @endif
                            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                                <i class="ti ti-adjustments me-1"></i> Adjust Stock
                            </button>
                        @endif
                        @if(!$alert->is_resolved)
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resolveModal">
                                <i class="ti ti-check me-1"></i> Resolve Alert
                            </button>
                        @endif
                        @can('inventory.alerts.manage')
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('{{ $alert->id }}', 'Alert #{{ $alert->id }}')">
                                <i class="ti ti-trash me-1"></i> Delete Alert
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Stock Information</h5>
                </div>
                <div class="card-body">
                    @if($alert->supply)
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Current Stock Level</h6>
                            <div class="progress" style="height: 10px;">
                                @php
                                    $percentage = $alert->supply->max_stock_level > 0 
                                        ? ($alert->supply->current_stock / $alert->supply->max_stock_level) * 100 
                                        : 0;
                                    $percentage = min(100, max(0, $percentage));
                                    $color = $percentage < 20 ? 'danger' : ($percentage < 50 ? 'warning' : 'success');
                                @endphp
                                <div class="progress-bar bg-{{ $color }}" role="progressbar" style="width: {{ $percentage }}%;" 
                                     aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted">{{ $alert->supply->current_stock }} units</small>
                                <small class="text-muted">{{ $alert->supply->max_stock_level ?: 'No max' }} max</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Stock Value</h6>
                            <h4 class="text-primary">${{ number_format($alert->supply->getCurrentValue(), 2) }}</h4>
                            <small class="text-muted">
                                {{ $alert->supply->current_stock }} units × ${{ number_format($alert->supply->unit_cost, 2) }} each
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Stock Status</h6>
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex justify-content-between">
                                    <span>Minimum Level:</span>
                                    <span class="fw-semibold">{{ $alert->supply->min_stock_level }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Reorder Point:</span>
                                    <span class="fw-semibold">
                                        @php
                                            $reorderPoint = $alert->supply->min_stock_level * 1.5;
                                        @endphp
                                        {{ number_format($reorderPoint, 2) }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Units to Reorder:</span>
                                    <span class="fw-semibold">
                                        @php
                                            $unitsToReorder = max(0, $alert->supply->max_stock_level - $alert->supply->current_stock);
                                        @endphp
                                        {{ number_format($unitsToReorder, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-1"></i>
                            Supply information is not available. The supply may have been deleted.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Resolve Modal -->
    @if(!$alert->is_resolved)
        @include('inventory.alerts.partials.resolve-modal', ['alert' => $alert])
    @endif

    <!-- Adjust Stock Modal -->
    @if($alert->supply)
        @include('inventory.supplies.partials.adjust-stock-modal', ['supply' => $alert->supply])
    @endif
@endsection

