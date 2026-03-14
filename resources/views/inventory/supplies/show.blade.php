@extends('layouts.vertical', ['title' => $supply->name])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => $supply->name])

    <div class="row">
        <div class="col-lg-4">
            <!-- Supply Details Card -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Supply Details</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-xl mx-auto mb-3">
                            <div class="avatar-title bg-soft-primary rounded-circle">
                                <i class="ti ti-package fs-24"></i>
                            </div>
                        </div>
                        <h4>{{ $supply->name }}</h4>
                        <p class="text-muted">{{ $supply->sku }}</p>
                        @include('inventory.supplies.partials.stock-badge', ['supply' => $supply])
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <th scope="row" style="width: 40%;">Category</th>
                                    <td>{{ $supply->category->name ?? 'Uncategorized' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Brand</th>
                                    <td>{{ $supply->brand ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Supplier</th>
                                    <td>{{ $supply->supplier_name ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Unit Type</th>
                                    <td>{{ ucfirst($supply->unit_type) }} @if($supply->unit_size) ({{ $supply->unit_size }}) @endif</td>
                                </tr>
                                <tr>
                                    <th scope="row">Min Stock Level</th>
                                    <td>{{ number_format($supply->min_stock_level, 2) }} {{ $supply->unit_type }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Max Stock Level</th>
                                    <td>{{ $supply->max_stock_level ? number_format($supply->max_stock_level, 2) . ' ' . $supply->unit_type : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Unit Cost</th>
                                    <td>{{ $currencySymbol }}{{ number_format($supply->unit_cost, 2) }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Retail Value</th>
                                    <td>{{ $supply->retail_value ? $currencySymbol . number_format($supply->retail_value, 2) : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Current Value</th>
                                    <td><strong>{{ $currencySymbol }}{{ number_format($supply->getCurrentValue(), 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <th scope="row">Location</th>
                                    <td>{{ $supply->location ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Storage Location</th>
                                    <td>{{ $supply->storage_location ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Status</th>
                                    <td>
                                        <span class="badge bg-{{ $supply->is_active ? 'success' : 'secondary' }}-subtle text-{{ $supply->is_active ? 'success' : 'secondary' }}">
                                            <i class="ti ti-circle-filled fs-xs"></i> {{ $supply->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Created</th>
                                    <td>{{ $supply->created_at->format('d M, Y') }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Last Updated</th>
                                    <td>{{ $supply->updated_at->format('d M, Y') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <h6 class="mb-2">Description</h6>
                        <p class="text-muted">{{ $supply->description ?: 'No description provided.' }}</p>
                    </div>

                    <div class="mt-4">
                        <h6 class="mb-2">Notes</h6>
                        <p class="text-muted">{{ $supply->notes ?: 'No notes.' }}</p>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        @can('inventory.manage')
                        <a href="{{ route('inventory.supplies.edit', $supply->id) }}" class="btn btn-primary">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        @endcan
                        @can('inventory.supplies.adjust')
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                            <i class="ti ti-adjustments me-1"></i> Adjust Stock
                        </button>
                        @endcan
                        <a href="{{ route('inventory.supplies.history', $supply->id) }}" class="btn btn-info">
                            <i class="ti ti-history me-1"></i> View History
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Stock Movements Card -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Recent Stock Movements</h4>
                    <a href="{{ route('inventory.supplies.history', $supply->id) }}" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body">
                    @if($stockMovements->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-centered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Before</th>
                                    <th>After</th>
                                    <th>Reference</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockMovements as $movement)
                                <tr>
                                    <td>{{ $movement->movement_date->format('d M, Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $movement->movement_type == 'purchase' ? 'success' : ($movement->movement_type == 'usage' ? 'danger' : 'warning') }}-subtle text-{{ $movement->movement_type == 'purchase' ? 'success' : ($movement->movement_type == 'usage' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($movement->movement_type) }}
                                        </span>
                                    </td>
                                    <td class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                                    </td>
                                    <td>{{ number_format($movement->quantity_before, 2) }}</td>
                                    <td>{{ number_format($movement->quantity_after, 2) }}</td>
                                    <td>{{ $movement->reference_number ?: 'N/A' }}</td>
                                    <td>{{ $movement->creator->name ?? 'System' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <div class="text-muted">
                            <i class="ti ti-history-off fs-24 mb-2 d-block"></i>
                            No stock movements recorded yet.
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Stats Card -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Supply Statistics</h4>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $supply->stockMovements()->count() }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Movements</h6>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $supply->usageLogs()->count() }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Usage Logs</h6>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $supply->alerts()->unresolved()->count() }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Active Alerts</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    @include('inventory.supplies.partials.adjust-stock-modal', ['supply' => $supply])
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
@endsection