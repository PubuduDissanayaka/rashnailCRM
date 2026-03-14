@csrf

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="name" class="form-label">Supply Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $supply->name ?? '') }}" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $supply->sku ?? '') }}" required>
            @error('sku')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="category_id" class="form-label">Category</label>
            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                <option value="">-- Select Category --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id', $supply->category_id ?? '') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="barcode" class="form-label">Barcode (Optional)</label>
            <input type="text" class="form-control @error('barcode') is-invalid @enderror" id="barcode" name="barcode" value="{{ old('barcode', $supply->barcode ?? '') }}">
            @error('barcode')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="brand" class="form-label">Brand</label>
            <input type="text" class="form-control @error('brand') is-invalid @enderror" id="brand" name="brand" value="{{ old('brand', $supply->brand ?? '') }}">
            @error('brand')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="supplier_name" class="form-label">Supplier Name</label>
            <input type="text" class="form-control @error('supplier_name') is-invalid @enderror" id="supplier_name" name="supplier_name" value="{{ old('supplier_name', $supply->supplier_name ?? '') }}">
            @error('supplier_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="unit_type" class="form-label">Unit Type <span class="text-danger">*</span></label>
            <select class="form-select @error('unit_type') is-invalid @enderror" id="unit_type" name="unit_type" required>
                <option value="">-- Select Unit --</option>
                @foreach($unitTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('unit_type', $supply->unit_type ?? '') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('unit_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="unit_size" class="form-label">Unit Size (e.g., 15 for 15ml)</label>
            <input type="number" step="0.01" min="0" class="form-control @error('unit_size') is-invalid @enderror" id="unit_size" name="unit_size" value="{{ old('unit_size', $supply->unit_size ?? '') }}">
            @error('unit_size')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="usage_per_service" class="form-label">Usage per Service</label>
            <input type="number" step="0.01" min="0" class="form-control @error('usage_per_service') is-invalid @enderror" id="usage_per_service" name="usage_per_service" value="{{ old('usage_per_service', $supply->usage_per_service ?? '') }}">
            @error('usage_per_service')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="min_stock_level" class="form-label">Min Stock Level <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" class="form-control @error('min_stock_level') is-invalid @enderror" id="min_stock_level" name="min_stock_level" value="{{ old('min_stock_level', $supply->min_stock_level ?? 0) }}" required>
            @error('min_stock_level')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="max_stock_level" class="form-label">Max Stock Level</label>
            <input type="number" step="0.01" min="0" class="form-control @error('max_stock_level') is-invalid @enderror" id="max_stock_level" name="max_stock_level" value="{{ old('max_stock_level', $supply->max_stock_level ?? '') }}">
            @error('max_stock_level')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="current_stock" class="form-label">Current Stock <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" class="form-control @error('current_stock') is-invalid @enderror" id="current_stock" name="current_stock" value="{{ old('current_stock', $supply->current_stock ?? 0) }}" required>
            @error('current_stock')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="unit_cost" class="form-label">Unit Cost ({{ $currencySymbol }}) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" class="form-control @error('unit_cost') is-invalid @enderror" id="unit_cost" name="unit_cost" value="{{ old('unit_cost', $supply->unit_cost ?? 0) }}" required>
            @error('unit_cost')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="retail_value" class="form-label">Retail Value ({{ $currencySymbol }})</label>
            <input type="number" step="0.01" min="0" class="form-control @error('retail_value') is-invalid @enderror" id="retail_value" name="retail_value" value="{{ old('retail_value', $supply->retail_value ?? '') }}">
            @error('retail_value')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control @error('location') is-invalid @enderror" id="location" name="location" value="{{ old('location', $supply->location ?? '') }}">
            @error('location')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="storage_location" class="form-label">Storage Location (Shelf/Bin)</label>
            <input type="text" class="form-control @error('storage_location') is-invalid @enderror" id="storage_location" name="storage_location" value="{{ old('storage_location', $supply->storage_location ?? '') }}">
            @error('storage_location')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description', $supply->description ?? '') }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $supply->notes ?? '') }}</textarea>
            @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-check form-switch mb-3">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $supply->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch mb-3">
            <input type="checkbox" class="form-check-input" id="track_expiry" name="track_expiry" value="1" {{ old('track_expiry', $supply->track_expiry ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="track_expiry">Track Expiry</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch mb-3">
            <input type="checkbox" class="form-check-input" id="track_batch" name="track_batch" value="1" {{ old('track_batch', $supply->track_batch ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="track_batch">Track Batch</label>
        </div>
    </div>
</div>