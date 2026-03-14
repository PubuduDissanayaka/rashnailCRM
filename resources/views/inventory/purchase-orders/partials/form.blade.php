<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="po_number" class="form-label">Purchase Order Number <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="po_number" name="po_number" value="{{ old('po_number', $purchaseOrder->po_number ?? '') }}" required {{ isset($purchaseOrder) ? 'readonly' : '' }}>
            @error('po_number')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="order_date" class="form-label">Order Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old('order_date', isset($purchaseOrder) ? $purchaseOrder->order_date->format('Y-m-d') : date('Y-m-d')) }}" required>
            @error('order_date')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="supplier_name" class="form-label">Supplier Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="supplier_name" name="supplier_name" value="{{ old('supplier_name', $purchaseOrder->supplier_name ?? '') }}" required>
            @error('supplier_name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="supplier_contact" class="form-label">Supplier Contact Person</label>
            <input type="text" class="form-control" id="supplier_contact" name="supplier_contact" value="{{ old('supplier_contact', $purchaseOrder->supplier_contact ?? '') }}">
            @error('supplier_contact')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="supplier_email" class="form-label">Supplier Email</label>
            <input type="email" class="form-control" id="supplier_email" name="supplier_email" value="{{ old('supplier_email', $purchaseOrder->supplier_email ?? '') }}">
            @error('supplier_email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="supplier_phone" class="form-label">Supplier Phone</label>
            <input type="text" class="form-control" id="supplier_phone" name="supplier_phone" value="{{ old('supplier_phone', $purchaseOrder->supplier_phone ?? '') }}">
            @error('supplier_phone')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="expected_delivery_date" class="form-label">Expected Delivery Date</label>
            <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" value="{{ old('expected_delivery_date', isset($purchaseOrder) && $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('Y-m-d') : '') }}">
            @error('expected_delivery_date')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="delivery_location" class="form-label">Delivery Location</label>
            <input type="text" class="form-control" id="delivery_location" name="delivery_location" value="{{ old('delivery_location', $purchaseOrder->delivery_location ?? '') }}" placeholder="e.g., Main Warehouse">
            @error('delivery_location')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="tax" class="form-label">Tax ({{ $currencySymbol }})</label>
            <input type="number" class="form-control" id="tax" name="tax" step="0.01" min="0" value="{{ old('tax', $purchaseOrder->tax ?? 0) }}">
            @error('tax')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="shipping" class="form-label">Shipping Cost ({{ $currencySymbol }})</label>
            <input type="number" class="form-control" id="shipping" name="shipping" step="0.01" min="0" value="{{ old('shipping', $purchaseOrder->shipping ?? 0) }}">
            @error('shipping')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes</label>
    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $purchaseOrder->notes ?? '') }}</textarea>
    @error('notes')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>