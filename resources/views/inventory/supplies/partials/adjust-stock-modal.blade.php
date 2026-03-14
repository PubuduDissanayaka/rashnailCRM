<div class="modal fade" id="adjustStockModal" tabindex="-1" aria-labelledby="adjustStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="adjustStockForm" action="{{ route('inventory.supplies.adjust', $supply->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="adjustStockModalLabel">Adjust Stock: {{ $supply->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="adjustment_type" class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="adjustment_type" name="adjustment_type" required>
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Stock to Specific Value</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="quantity" name="quantity" required>
                        <div class="form-text">Current stock: <strong>{{ number_format($supply->current_stock, 2) }} {{ $supply->unit_type }}</strong></div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="reason" name="reason" required placeholder="e.g., Received shipment, Used in service, etc.">
                    </div>

                    <div class="mb-3">
                        <label for="movement_date" class="form-label">Movement Date</label>
                        <input type="datetime-local" class="form-control" id="movement_date" name="movement_date" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Optional notes about this adjustment"></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-1"></i>
                        <strong>Note:</strong> This adjustment will create a stock movement record for audit purposes.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Adjust Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const adjustmentType = document.getElementById('adjustment_type');
        const quantityInput = document.getElementById('quantity');
        const currentStock = {{ $supply->current_stock }};
        
        adjustmentType.addEventListener('change', function() {
            if (this.value === 'set') {
                quantityInput.min = 0;
                quantityInput.placeholder = 'Enter new stock level';
            } else {
                quantityInput.min = 0.01;
                quantityInput.placeholder = 'Enter quantity to ' + this.value;
            }
        });
        
        // Initialize
        adjustmentType.dispatchEvent(new Event('change'));
    });
</script>