<div class="card">
    <div class="card-header border-light justify-content-between">
        <div>
            <h4 class="card-title mb-0">Line Items</h4>
            <p class="text-muted mb-0">Add supplies to this purchase order</p>
        </div>
        <button type="button" class="btn btn-primary" id="add-item-btn">
            <i class="ti ti-plus me-1"></i> Add Item
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered table-hover mb-0" id="items-table">
                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th style="width: 30%;">Supply</th>
                        <th style="width: 15%;">Quantity</th>
                        <th style="width: 15%;">Unit Cost ({{ $currencySymbol }})</th>
                        <th style="width: 15%;">Total Cost ({{ $currencySymbol }})</th>
                        <th style="width: 15%;">Batch/Expiry</th>
                        <th style="width: 10%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="items-tbody">
                    <!-- Items will be added here dynamically -->
                    @if(isset($purchaseOrder) && $purchaseOrder->items->count() > 0)
                        @foreach($purchaseOrder->items as $index => $item)
                            <tr class="item-row" data-index="{{ $index }}">
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                    <select class="form-select supply-select" name="items[{{ $index }}][supply_id]" required>
                                        <option value="">Select Supply</option>
                                        @foreach($supplies as $supply)
                                            <option value="{{ $supply->id }}" data-unit-cost="{{ $supply->unit_cost }}" data-unit-type="{{ $supply->unit_type }}" {{ $item->supply_id == $supply->id ? 'selected' : '' }}>
                                                {{ $supply->name }} ({{ $supply->sku }}) - Stock: {{ $supply->current_stock }} {{ $supply->unit_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control quantity" name="items[{{ $index }}][quantity_ordered]" step="0.01" min="0.01" value="{{ $item->quantity_ordered }}" required>
                                    <small class="text-muted unit-type-display">{{ $item->supply->unit_type ?? '' }}</small>
                                </td>
                                <td>
                                    <input type="number" class="form-control unit-cost" name="items[{{ $index }}][unit_cost]" step="0.01" min="0" value="{{ $item->unit_cost }}" required>
                                </td>
                                <td>
                                    <input type="number" class="form-control total-cost" name="items[{{ $index }}][total_cost]" step="0.01" min="0" value="{{ $item->total_cost }}" readonly>
                                </td>
                                <td>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <input type="text" class="form-control form-control-sm" name="items[{{ $index }}][batch_number]" placeholder="Batch #" value="{{ $item->batch_number }}">
                                        </div>
                                        <div class="col-12">
                                            <input type="date" class="form-control form-control-sm" name="items[{{ $index }}][expiry_date]" value="{{ $item->expiry_date ? $item->expiry_date->format('Y-m-d') : '' }}">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-item">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <!-- Empty row template -->
                        <tr class="item-row" data-index="0">
                            <td>
                                <select class="form-select supply-select" name="items[0][supply_id]" required>
                                    <option value="">Select Supply</option>
                                    @foreach($supplies as $supply)
                                        <option value="{{ $supply->id }}" data-unit-cost="{{ $supply->unit_cost }}" data-unit-type="{{ $supply->unit_type }}">
                                            {{ $supply->name }} ({{ $supply->sku }}) - Stock: {{ $supply->current_stock }} {{ $supply->unit_type }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control quantity" name="items[0][quantity_ordered]" step="0.01" min="0.01" value="1" required>
                                <small class="text-muted unit-type-display"></small>
                            </td>
                            <td>
                                <input type="number" class="form-control unit-cost" name="items[0][unit_cost]" step="0.01" min="0" value="0" required>
                            </td>
                            <td>
                                <input type="number" class="form-control total-cost" name="items[0][total_cost]" step="0.01" min="0" value="0" readonly>
                            </td>
                            <td>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <input type="text" class="form-control form-control-sm" name="items[0][batch_number]" placeholder="Batch #">
                                    </div>
                                    <div class="col-12">
                                        <input type="date" class="form-control form-control-sm" name="items[0][expiry_date]">
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-sm remove-item">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                        <td colspan="3">
                            <div class="d-flex justify-content-between">
                                <span>{{ $currencySymbol }}</span>
                                <span id="subtotal">0.00</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                        <td colspan="3">
                            <div class="d-flex justify-content-between">
                                <span>{{ $currencySymbol }}</span>
                                <span id="tax-amount">0.00</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                        <td colspan="3">
                            <div class="d-flex justify-content-between">
                                <span>{{ $currencySymbol }}</span>
                                <span id="shipping-amount">0.00</span>
                            </div>
                        </td>
                    </tr>
                    <tr class="table-active">
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td colspan="3">
                            <div class="d-flex justify-content-between">
                                <span>{{ $currencySymbol }}</span>
                                <span id="grand-total">0.00</span>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let itemIndex = {{ isset($purchaseOrder) ? $purchaseOrder->items->count() : 1 }};
        
        // Add item row
        document.getElementById('add-item-btn').addEventListener('click', function() {
            const tbody = document.getElementById('items-tbody');
            const newRow = document.querySelector('.item-row').cloneNode(true);
            
            // Update indices
            newRow.setAttribute('data-index', itemIndex);
            newRow.querySelectorAll('[name]').forEach(input => {
                const name = input.getAttribute('name');
                input.setAttribute('name', name.replace(/items\[\d+\]/, `items[${itemIndex}]`));
                if (input.type !== 'hidden') {
                    input.value = '';
                }
            });
            
            // Reset values
            newRow.querySelector('.supply-select').selectedIndex = 0;
            newRow.querySelector('.quantity').value = '1';
            newRow.querySelector('.unit-cost').value = '0';
            newRow.querySelector('.total-cost').value = '0';
            newRow.querySelector('.unit-type-display').textContent = '';
            newRow.querySelector('[name*="batch_number"]').value = '';
            newRow.querySelector('[name*="expiry_date"]').value = '';
            
            tbody.appendChild(newRow);
            itemIndex++;
            
            // Reattach event listeners
            attachRowEventListeners(newRow);
            calculateTotals();
        });
        
        // Remove item row
        function attachRowEventListeners(row) {
            row.querySelector('.remove-item').addEventListener('click', function() {
                if (document.querySelectorAll('.item-row').length > 1) {
                    row.remove();
                    calculateTotals();
                } else {
                    alert('At least one item is required.');
                }
            });
            
            row.querySelector('.supply-select').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const unitCost = selectedOption.dataset.unitCost || '0';
                const unitType = selectedOption.dataset.unitType || '';
                
                row.querySelector('.unit-cost').value = unitCost;
                row.querySelector('.unit-type-display').textContent = unitType;
                calculateRowTotal(row);
                calculateTotals();
            });
            
            row.querySelector('.quantity').addEventListener('input', function() {
                calculateRowTotal(row);
                calculateTotals();
            });
            
            row.querySelector('.unit-cost').addEventListener('input', function() {
                calculateRowTotal(row);
                calculateTotals();
            });
        }
        
        // Calculate row total
        function calculateRowTotal(row) {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitCost = parseFloat(row.querySelector('.unit-cost').value) || 0;
            const total = quantity * unitCost;
            
            row.querySelector('.total-cost').value = total.toFixed(2);
        }
        
        // Calculate all totals
        function calculateTotals() {
            let subtotal = 0;
            
            document.querySelectorAll('.item-row').forEach(row => {
                const total = parseFloat(row.querySelector('.total-cost').value) || 0;
                subtotal += total;
            });
            
            const tax = parseFloat(document.getElementById('tax').value) || 0;
            const shipping = parseFloat(document.getElementById('shipping').value) || 0;
            const grandTotal = subtotal + tax + shipping;
            
            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('tax-amount').textContent = tax.toFixed(2);
            document.getElementById('shipping-amount').textContent = shipping.toFixed(2);
            document.getElementById('grand-total').textContent = grandTotal.toFixed(2);
        }
        
        // Attach event listeners to existing rows
        document.querySelectorAll('.item-row').forEach(row => {
            attachRowEventListeners(row);
        });
        
        // Tax and shipping inputs
        document.getElementById('tax').addEventListener('input', calculateTotals);
        document.getElementById('shipping').addEventListener('input', calculateTotals);
        
        // Initial calculation
        calculateTotals();
    });
</script>
@endpush