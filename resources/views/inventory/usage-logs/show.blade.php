@extends('layouts.vertical')

@section('title', 'Usage Log Details')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('inventory.supplies.index') }}">Inventory</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('inventory.usage-logs.index') }}">Usage Logs</a></li>
                        <li class="breadcrumb-item active">Log #{{ $usageLog->id }}</li>
                    </ol>
                </div>
                <h4 class="page-title">Usage Log Details</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Usage Information</h4>
                        <span class="badge bg-{{ $usageLog->appointment_id ? 'primary' : 'secondary' }}">
                            {{ $usageLog->appointment_id ? 'Appointment Linked' : 'Manual Log' }}
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <th style="width: 30%">Supply</th>
                                    <td>
                                        <a href="{{ route('inventory.supplies.show', $usageLog->supply) }}">
                                            {{ $usageLog->supply->name }} (SKU: {{ $usageLog->supply->sku }})
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Quantity Used</th>
                                    <td>{{ $usageLog->quantity_used }} {{ $usageLog->supply->unit_type }}</td>
                                </tr>
                                <tr>
                                    <th>Unit Cost</th>
                                    <td>{{ $currencySymbol }}{{ number_format($usageLog->unit_cost, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Total Cost</th>
                                    <td><strong>{{ $currencySymbol }}{{ number_format($usageLog->total_cost, 2) }}</strong></td>
                                </tr>
                                @if($usageLog->appointment)
                                <tr>
                                    <th>Appointment</th>
                                    <td>
                                        <a href="{{ route('appointments.show', $usageLog->appointment) }}">
                                            #{{ $usageLog->appointment->slug }} - {{ $usageLog->appointment->customer->name ?? 'N/A' }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $usageLog->appointment->appointment_date->format('M d, Y h:i A') }}</small>
                                    </td>
                                </tr>
                                @endif
                                @if($usageLog->service)
                                <tr>
                                    <th>Service</th>
                                    <td>
                                        <a href="{{ route('services.show', $usageLog->service) }}">
                                            {{ $usageLog->service->name }}
                                        </a>
                                    </td>
                                </tr>
                                @endif
                                @if($usageLog->user)
                                <tr>
                                    <th>Staff</th>
                                    <td>{{ $usageLog->user->name }}</td>
                                </tr>
                                @endif
                                @if($usageLog->customer)
                                <tr>
                                    <th>Customer</th>
                                    <td>{{ $usageLog->customer->name }}</td>
                                </tr>
                                @endif
                                @if($usageLog->batch_number)
                                <tr>
                                    <th>Batch Number</th>
                                    <td>{{ $usageLog->batch_number }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th>Used At</th>
                                    <td>{{ $usageLog->used_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $usageLog->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                @if($usageLog->notes)
                                <tr>
                                    <th>Notes</th>
                                    <td>{{ $usageLog->notes }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('inventory.usage-logs.index') }}" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Supply Information</h4>
                    
                    <div class="text-center mb-3">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title bg-light rounded-circle text-primary">
                                <i class="ri-box-3-line h1 m-0"></i>
                            </div>
                        </div>
                        <h4>{{ $usageLog->supply->name }}</h4>
                        <p class="text-muted">SKU: {{ $usageLog->supply->sku }}</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <th>Category:</th>
                                    <td>{{ $usageLog->supply->category->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Current Stock:</th>
                                    <td>
                                        <span class="badge bg-{{ $usageLog->supply->isLowStock() ? 'warning' : ($usageLog->supply->isOutOfStock() ? 'danger' : 'success') }}">
                                            {{ $usageLog->supply->current_stock }} {{ $usageLog->supply->unit_type }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Min Level:</th>
                                    <td>{{ $usageLog->supply->min_stock_level }} {{ $usageLog->supply->unit_type }}</td>
                                </tr>
                                <tr>
                                    <th>Unit Type:</th>
                                    <td>{{ $usageLog->supply->unit_type }}</td>
                                </tr>
                                <tr>
                                    <th>Unit Cost:</th>
                                    <td>{{ $currencySymbol }}{{ number_format($usageLog->supply->unit_cost, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Location:</th>
                                    <td>{{ $usageLog->supply->location ?? 'N/A' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('inventory.supplies.show', $usageLog->supply) }}" class="btn btn-outline-primary w-100">
                            <i class="ri-eye-line me-1"></i> View Supply Details
                        </a>
                    </div>
                </div>
            </div>

            @if($usageLog->appointment)
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Appointment Details</h4>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <th>Customer:</th>
                                    <td>{{ $usageLog->appointment->customer->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Staff:</th>
                                    <td>{{ $usageLog->appointment->user->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Date & Time:</th>
                                    <td>{{ $usageLog->appointment->appointment_date->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-{{ $usageLog->appointment->status_badge }}">
                                            {{ $usageLog->appointment->status_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Service:</th>
                                    <td>{{ $usageLog->appointment->service->name ?? 'N/A' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('appointments.show', $usageLog->appointment) }}" class="btn btn-outline-primary w-100">
                            <i class="ri-calendar-line me-1"></i> View Appointment
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection