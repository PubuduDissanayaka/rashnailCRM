<div class="modal fade" id="receiveModal{{ $purchaseOrder->id }}" tabindex="-1" aria-labelledby="receiveModalLabel{{ $purchaseOrder->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('inventory.purchase-orders.receive', $purchaseOrder->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="receiveModalLabel{{ $purchaseOrder->id }}">Receive Items - {{ $purchaseOrder->po_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="received_date" class="form-label">Received Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="received_date" name="received_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="tracking_number" class="form-label">Tracking Number</label>
                            <input type="text" class="form-control" id="tracking_number" name="tracking_number" value="{{ old('tracking_number') }}">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="invoice_number" class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="{{ old('invoice_number') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="notes" class="form-label">Receiving Notes</label>
                            <input type="text" class="form-control" id="notes" name="notes" value="{{ old('notes') }}">
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-centered table-hover mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>Supply</th>
                                    <th>Ordered</th>
                                    <th>Already Received</th>
                                    <th>Remaining</th>
                                    <th>Receive Now</th>
                                    <th>Batch/Expiry</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->items as $index => $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->supply->name }}</strong>
                                            <br>
                                            <small class="text-muted">SKU: {{ $item->supply->sku }}</small>
                                        </td>
                                        <td>{{ $item->quantity_ordered }} {{ $item->supply->unit_type }}</td>
                                        <td>{{ $item->quantity_received }} {{ $item->supply->unit_type }}</td>
                                        <td>{{ $item->remainingQuantity() }} {{ $item->supply->unit_type }}</td>
                                        <td>
                                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                            <input type="number" class="form-control" name="items[{{ $index }}][quantity_received]" 
                                                   step="0.01" min="0" max="{{ $item->remainingQuantity() }}" 
                                                   value="0" placeholder="Qty to receive">
                                        </td>
                                        <td>
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="items[{{ $index }}][batch_number]" 
                                                           placeholder="Batch #" 
                                                           value="{{ $item->batch_number }}">
                                                </div>
                                                <div class="col-12">
                                                    <input type="date" class="form-control form-control-sm" 
                                                           name="items[{{ $index }}][expiry_date]" 
                                                           value="{{ $item->expiry_date ? $item->expiry_date->format('Y-m-d') : '' }}">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="ti ti-info-circle me-2"></i>
                        <strong>Note:</strong> Receiving items will update stock levels and create stock movement records.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Receive Items</button>
                </div>
            </form>
        </div>
    </div>
</div>