@extends('layouts.vertical', ['title' => 'Purchase Order Management'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Purchase Order Management'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Purchase Orders</h4>
                        <p class="text-muted mb-0">Manage purchase orders and track deliveries</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search POs..." type="search" value="{{ $search ?? '' }}" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        @can('inventory.purchase.create')
                        <a href="{{ route('inventory.purchase-orders.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> New Purchase Order
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body border-top border-light">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total</h6>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['draft'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Draft</h6>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['pending'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Pending</h6>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['ordered'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Ordered</h6>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['partial'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Partial</h6>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['received'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Received</h6>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Filter -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-outline-secondary {{ !$status ? 'active' : '' }}">
                                    All
                                </a>
                                <a href="{{ route('inventory.purchase-orders.index', ['status' => 'draft']) }}" class="btn btn-outline-secondary {{ $status == 'draft' ? 'active' : '' }}">
                                    Draft
                                </a>
                                <a href="{{ route('inventory.purchase-orders.index', ['status' => 'pending']) }}" class="btn btn-outline-secondary {{ $status == 'pending' ? 'active' : '' }}">
                                    Pending
                                </a>
                                <a href="{{ route('inventory.purchase-orders.index', ['status' => 'ordered']) }}" class="btn btn-outline-secondary {{ $status == 'ordered' ? 'active' : '' }}">
                                    Ordered
                                </a>
                                <a href="{{ route('inventory.purchase-orders.index', ['status' => 'partial']) }}" class="btn btn-outline-secondary {{ $status == 'partial' ? 'active' : '' }}">
                                    Partial
                                </a>
                                <a href="{{ route('inventory.purchase-orders.index', ['status' => 'received']) }}" class="btn btn-outline-secondary {{ $status == 'received' ? 'active' : '' }}">
                                    Received
                                </a>
                                <a href="{{ route('inventory.purchase-orders.index', ['status' => 'cancelled']) }}" class="btn btn-outline-secondary {{ $status == 'cancelled' ? 'active' : '' }}">
                                    Cancelled
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">PO #</th>
                                <th data-table-sort="sort-supplier">Supplier</th>
                                <th data-table-sort="sort-date">Order Date</th>
                                <th data-table-sort="sort-delivery">Expected Delivery</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th data-table-sort="sort-items">Items</th>
                                <th data-table-sort="sort-total">Total Amount</th>
                                <th data-table-sort="sort-created">Created By</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchaseOrders as $po)
                            <tr>
                                <td class="ps-3">
                                    <strong>{{ $po->po_number }}</strong>
                                </td>
                                <td data-sort="sort-supplier">
                                    <div>
                                        <h5 class="fs-base mb-0">{{ $po->supplier_name }}</h5>
                                        <small class="text-muted">{{ $po->supplier_contact ?: 'No contact' }}</small>
                                    </div>
                                </td>
                                <td data-sort="sort-date">{{ $po->order_date->format('d M, Y') }}</td>
                                <td data-sort="sort-delivery">
                                    @if($po->expected_delivery_date)
                                        {{ $po->expected_delivery_date->format('d M, Y') }}
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td data-sort="sort-status">
                                    @include('inventory.purchase-orders.partials.status-badge', ['status' => $po->status])
                                </td>
                                <td data-sort="sort-items">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-light text-dark">{{ $po->items_count ?? $po->items->count() }} items</span>
                                    </div>
                                </td>
                                <td data-sort="sort-total">
                                    <strong>{{ $currencySymbol }}{{ number_format($po->total, 2) }}</strong>
                                </td>
                                <td data-sort="sort-created">
                                    {{ $po->creator->name ?? 'Unknown' }}
                                    <br>
                                    <small class="text-muted">{{ $po->created_at->format('d M, Y') }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('inventory.purchase-orders.show', $po->id) }}" title="View Purchase Order">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @if($po->canBeEdited())
                                            @can('inventory.purchase.create')
                                            <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('inventory.purchase-orders.edit', $po->id) }}" title="Edit Purchase Order">
                                                <i class="ti ti-edit fs-lg"></i>
                                            </a>
                                            @endcan
                                        @endif
                                        @if(in_array($po->status, ['ordered', 'partial']))
                                            @can('inventory.purchase.receive')
                                            <button type="button" class="btn btn-success btn-icon btn-sm rounded-circle" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#receiveModal{{ $po->id }}"
                                                    title="Receive Items">
                                                <i class="ti ti-truck-delivery fs-lg"></i>
                                            </button>
                                            @endcan
                                        @endif
                                        @if(in_array($po->status, ['draft', 'pending']))
                                            @can('inventory.purchase.approve')
                                            <form id="approve-form-{{ $po->id }}" action="{{ route('inventory.purchase-orders.approve', $po->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="button" class="btn btn-primary btn-icon btn-sm rounded-circle" title="Approve Purchase Order" onclick="confirmApprove('{{ $po->id }}')">
                                                    <i class="ti ti-check fs-lg"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        @endif
                                        @if($po->canBeEdited())
                                            @can('inventory.purchase.create')
                                            <form id="delete-form-{{ $po->id }}" action="{{ route('inventory.purchase-orders.destroy', $po->id) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                                                    onclick="confirmDelete('{{ $po->id }}', '{{ addslashes($po->po_number) }}')"
                                                    title="Delete Purchase Order">
                                                <i class="ti ti-trash fs-lg"></i>
                                            </button>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-file-invoice-off fs-24 mb-2 d-block"></i>
                                        No purchase orders found. <a href="{{ route('inventory.purchase-orders.create') }}">Create the first purchase order</a>.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="purchase-orders"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->

    <!-- Receive Modals -->
    @foreach($purchaseOrders as $po)
        @if(in_array($po->status, ['ordered', 'partial']))
            @include('inventory.purchase-orders.partials.receive-modal', ['purchaseOrder' => $po])
        @endif
    @endforeach
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function confirmDelete(poId, poNumber) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete purchase order "${poNumber}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`delete-form-${poId}`).submit();
                }
            });
        }
        
        // Search functionality
        document.querySelector('[data-table-search]').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        function confirmApprove(poId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to approve this purchase order. This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, approve it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('approve-form-' + poId).submit();
                }
            })
        }
    </script>
@endsection