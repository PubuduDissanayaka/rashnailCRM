@extends('layouts.vertical', ['title' => 'Purchase Order Details'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Purchase Order Details'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">{{ $purchaseOrder->po_number }}</h4>
                        <p class="text-muted mb-0">
                            @include('inventory.purchase-orders.partials.status-badge', ['status' => $purchaseOrder->status])
                            • Created {{ $purchaseOrder->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @if($purchaseOrder->canBeEdited())
                            @can('inventory.purchase.create')
                            <a href="{{ route('inventory.purchase-orders.edit', $purchaseOrder->id) }}" class="btn btn-primary">
                                <i class="ti ti-edit me-1"></i> Edit
                            </a>
                            @endcan
                        @endif
                        @if(in_array($purchaseOrder->status, ['ordered', 'partial']))
                            @can('inventory.purchase.receive')
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#receiveModal{{ $purchaseOrder->id }}">
                                <i class="ti ti-truck-delivery me-1"></i> Receive Items
                            </button>
                            @endcan
                        @endif
                        @if(in_array($purchaseOrder->status, ['draft', 'pending']))
                            @can('inventory.purchase.approve')
                            <form id="approve-form-{{ $purchaseOrder->id }}" action="{{ route('inventory.purchase-orders.approve', $purchaseOrder->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="button" class="btn btn-info" onclick="confirmApprove('{{ $purchaseOrder->id }}')">
                                    <i class="ti ti-check me-1"></i> Approve
                                </button>
                            </form>
                            @endcan
                        @endif
                        @if(in_array($purchaseOrder->status, ['draft', 'pending', 'ordered']))
                            @can('inventory.purchase.create')
                            <form action="{{ route('inventory.purchase-orders.cancel', $purchaseOrder->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to cancel this purchase order?')">
                                    <i class="ti ti-x me-1"></i> Cancel
                                </button>
                            </form>
                            @endcan
                        @endif
                        <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Supplier Information</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Supplier Name:</th>
                                            <td>{{ $purchaseOrder->supplier_name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Contact Person:</th>
                                            <td>{{ $purchaseOrder->supplier_contact ?: 'Not specified' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td>{{ $purchaseOrder->supplier_email ?: 'Not specified' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Phone:</th>
                                            <td>{{ $purchaseOrder->supplier_phone ?: 'Not specified' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Order Details</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Order Date:</th>
                                            <td>{{ $purchaseOrder->order_date->format('d M, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Expected Delivery:</th>
                                            <td>
                                                @if($purchaseOrder->expected_delivery_date)
                                                    {{ $purchaseOrder->expected_delivery_date->format('d M, Y') }}
                                                @else
                                                    <span class="text-muted">Not set</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Delivery Location:</th>
                                            <td>{{ $purchaseOrder->delivery_location ?: 'Not specified' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Tracking Number:</th>
                                            <td>{{ $purchaseOrder->tracking_number ?: 'Not provided' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Invoice Number:</th>
                                            <td>{{ $purchaseOrder->invoice_number ?: 'Not provided' }}</td>
                                        </tr>
                                        @if($purchaseOrder->received_date)
                                        <tr>
                                            <th>Received Date:</th>
                                            <td>{{ $purchaseOrder->received_date->format('d M, Y') }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Line Items</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-centered table-hover mb-0">
                                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                        <tr class="text-uppercase fs-xxs">
                                            <th>Supply</th>
                                            <th>SKU</th>
                                            <th>Quantity Ordered</th>
                                            <th>Quantity Received</th>
                                            <th>Remaining</th>
                                            <th>Unit Cost</th>
                                            <th>Total Cost</th>
                                            <th>Batch/Expiry</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($purchaseOrder->items as $item)
                                            <tr>
                                                <td>
                                                    <strong>{{ $item->supply->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $item->supply->brand ?: 'No brand' }}</small>
                                                </td>
                                                <td>{{ $item->supply->sku }}</td>
                                                <td>{{ $item->quantity_ordered }} {{ $item->supply->unit_type }}</td>
                                                <td>{{ $item->quantity_received }} {{ $item->supply->unit_type }}</td>
                                                <td>{{ $item->remainingQuantity() }} {{ $item->supply->unit_type }}</td>
                                                <td>{{ $currencySymbol }}{{ number_format($item->unit_cost, 2) }}</td>
                                                <td>{{ $currencySymbol }}{{ number_format($item->total_cost, 2) }}</td>
                                                <td>
                                                    @if($item->batch_number)
                                                        <span class="badge bg-light text-dark">{{ $item->batch_number }}</span>
                                                    @endif
                                                    @if($item->expiry_date)
                                                        <br>
                                                        <small class="text-muted">Exp: {{ $item->expiry_date->format('d M, Y') }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item->isFullyReceived())
                                                        <span class="badge bg-success-subtle text-success">
                                                            <i class="ti ti-check fs-xs"></i> Fully Received
                                                        </span>
                                                    @elseif($item->quantity_received > 0)
                                                        <span class="badge bg-primary-subtle text-primary">
                                                            <i class="ti ti-package fs-xs"></i> Partial
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning-subtle text-warning">
                                                            <i class="ti ti-clock fs-xs"></i> Pending
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-active">
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                            <td colspan="4">{{ $currencySymbol }}{{ number_format($purchaseOrder->subtotal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Tax:</strong></td>
                                            <td colspan="4">{{ $currencySymbol }}{{ number_format($purchaseOrder->tax, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Shipping:</strong></td>
                                            <td colspan="4">{{ $currencySymbol }}{{ number_format($purchaseOrder->shipping, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                            <td colspan="4"><strong>{{ $currencySymbol }}{{ number_format($purchaseOrder->total, 2) }}</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Audit Trail</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Created By:</th>
                                            <td>{{ $purchaseOrder->creator->name ?? 'Unknown' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Created Date:</th>
                                            <td>{{ $purchaseOrder->created_at->format('d M, Y H:i') }}</td>
                                        </tr>
                                        @if($purchaseOrder->approved_by)
                                        <tr>
                                            <th>Approved By:</th>
                                            <td>{{ $purchaseOrder->approver->name ?? 'Unknown' }}</td>
                                        </tr>
                                        @endif
                                        @if($purchaseOrder->received_by)
                                        <tr>
                                            <th>Received By:</th>
                                            <td>{{ $purchaseOrder->receiver->name ?? 'Unknown' }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Notes</h5>
                                </div>
                                <div class="card-body">
                                    @if($purchaseOrder->notes)
                                        <p class="mb-0">{{ $purchaseOrder->notes }}</p>
                                    @else
                                        <p class="text-muted mb-0">No notes provided.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->

    <!-- Receive Modal -->
    @if(in_array($purchaseOrder->status, ['ordered', 'partial']))
        @include('inventory.purchase-orders.partials.receive-modal', ['purchaseOrder' => $purchaseOrder])
    @endif
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
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