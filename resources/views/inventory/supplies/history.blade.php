@extends('layouts.vertical', ['title' => 'Stock History - ' . $supply->name])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Stock History: ' . $supply->name])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Stock Movement History</h4>
                        <p class="text-muted mb-0">All stock movements for {{ $supply->name }}</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ route('inventory.supplies.show', $supply->id) }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back to Supply
                        </a>
                        @can('inventory.supplies.adjust')
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                            <i class="ti ti-adjustments me-1"></i> Adjust Stock
                        </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h5 class="text-muted fw-normal mt-0">Current Stock</h5>
                                    <h3 class="mb-0">{{ number_format($supply->current_stock, 2) }} {{ $supply->unit_type }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h5 class="text-muted fw-normal mt-0">Min Level</h5>
                                    <h3 class="mb-0">{{ number_format($supply->min_stock_level, 2) }} {{ $supply->unit_type }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h5 class="text-muted fw-normal mt-0">Total Added</h5>
                                    <h3 class="mb-0 text-success">{{ number_format($supply->stockMovements()->where('quantity', '>', 0)->sum('quantity'), 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h5 class="text-muted fw-normal mt-0">Total Used</h5>
                                    <h3 class="mb-0 text-danger">{{ number_format(abs($supply->stockMovements()->where('quantity', '<', 0)->sum('quantity')), 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-hover w-100 mb-0" id="stockHistoryTable">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th class="ps-3">Date</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Before</th>
                                    <th>After</th>
                                    <th>Unit Cost</th>
                                    <th>Total Cost</th>
                                    <th>Reference</th>
                                    <th>User</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockMovements as $movement)
                                <tr>
                                    <td class="ps-3">{{ $movement->movement_date->format('d M, Y H:i') }}</td>
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
                                    <td>${{ number_format($movement->unit_cost, 2) }}</td>
                                    <td>${{ number_format($movement->total_cost, 2) }}</td>
                                    <td>{{ $movement->reference_number ?: 'N/A' }}</td>
                                    <td>{{ $movement->creator->name ?? 'System' }}</td>
                                    <td>{{ Str::limit($movement->notes, 50) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ti ti-history-off fs-24 mb-2 d-block"></i>
                                            No stock movements recorded yet.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $stockMovements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    @include('inventory.supplies.partials.adjust-stock-modal', ['supply' => $supply])
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable if needed
            if ($.fn.DataTable) {
                $('#stockHistoryTable').DataTable({
                    pageLength: 25,
                    order: [[0, 'desc']],
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>tip',
                    language: {
                        search: "",
                        searchPlaceholder: "Search history..."
                    }
                });
            }
        });
    </script>
@endsection