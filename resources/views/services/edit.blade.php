@extends('layouts.vertical', ['title' => 'Edit Service'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Edit Service'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('services.update', $service) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Service Name</label>
                                    <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $service->name) }}" required>
                                    @error('name')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price ($)</label>
                                    <input class="form-control" type="number" id="price" name="price" value="{{ old('price', $service->price) }}" min="0" step="0.01" required>
                                    @error('price')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (minutes)</label>
                                    <input class="form-control" type="number" id="duration" name="duration" value="{{ old('duration', $service->duration) }}" min="1" required>
                                    @error('duration')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Status</label>
                                    <select class="form-select" id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', $service->is_active) ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ !old('is_active', $service->is_active) ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('is_active')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $service->description) }}</textarea>
                            @error('description')
                                <span class="text-danger" role="alert">
                                    <small>{{ $message }}</small>
                                </span>
                            @enderror
                        </div>

                        <!-- Supply Linking Section -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Linked Supplies</h5>
                                <p class="text-muted mb-0">Select supplies required for this service. These will be automatically deducted when appointments are completed.</p>
                            </div>
                            <div class="card-body">
                                <div id="supplies-container">
                                    @php
                                        $existingSupplies = $service->supplies->keyBy('id');
                                        $supplyIndex = 0;
                                    @endphp
                                    
                                    @if($existingSupplies->count() > 0)
                                        @foreach($existingSupplies as $supply)
                                            <div class="supply-row mb-3">
                                                <div class="row g-2">
                                                    <div class="col-md-5">
                                                        <label class="form-label">Supply</label>
                                                        <select class="form-select supply-select" name="supplies[{{ $supplyIndex }}][id]" data-index="{{ $supplyIndex }}">
                                                            <option value="">Select a supply</option>
                                                            @foreach($supplies as $s)
                                                                <option value="{{ $s->id }}" {{ $s->id == $supply->id ? 'selected' : '' }}>
                                                                    {{ $s->name }} ({{ $s->sku }}) - Stock: {{ $s->current_stock }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Quantity Required</label>
                                                        <input type="number" class="form-control" name="supplies[{{ $supplyIndex }}][quantity_required]" min="0.01" step="0.01" value="{{ old('supplies.' . $supplyIndex . '.quantity_required', $supply->pivot->quantity_required ?? 1) }}">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Optional?</label>
                                                        <select class="form-select" name="supplies[{{ $supplyIndex }}][is_optional]">
                                                            <option value="0" {{ (old('supplies.' . $supplyIndex . '.is_optional', $supply->pivot->is_optional ?? 0) == 0) ? 'selected' : '' }}>Required</option>
                                                            <option value="1" {{ (old('supplies.' . $supplyIndex . '.is_optional', $supply->pivot->is_optional ?? 0) == 1) ? 'selected' : '' }}>Optional</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-1 d-flex align-items-end">
                                                        <button type="button" class="btn btn-danger remove-supply">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @php $supplyIndex++; @endphp
                                        @endforeach
                                    @else
                                        <div class="supply-row mb-3">
                                            <div class="row g-2">
                                                <div class="col-md-5">
                                                    <label class="form-label">Supply</label>
                                                    <select class="form-select supply-select" name="supplies[0][id]" data-index="0">
                                                        <option value="">Select a supply</option>
                                                        @foreach($supplies as $supply)
                                                            <option value="{{ $supply->id }}">{{ $supply->name }} ({{ $supply->sku }}) - Stock: {{ $supply->current_stock }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Quantity Required</label>
                                                    <input type="number" class="form-control" name="supplies[0][quantity_required]" min="0.01" step="0.01" value="1">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Optional?</label>
                                                    <select class="form-select" name="supplies[0][is_optional]">
                                                        <option value="0">Required</option>
                                                        <option value="1">Optional</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-1 d-flex align-items-end">
                                                    <button type="button" class="btn btn-danger remove-supply" style="display: none;">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-supply">
                                    <i class="ri-add-line"></i> Add Another Supply
                                </button>
                            </div>
                        </div>

                        <div class="text-end">
                            <button class="btn btn-primary" type="submit">Update Service</button>
                            <a href="{{ route('services.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/form-choice.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        // Check if there are success messages to display
        @if(session('success'))
            Swal.fire({
                title: 'Success!',
                text: '{{ session('success') }}',
                icon: 'success',
                confirmButtonClass: 'btn btn-primary'
            });
        @endif
        
        // Check if there are error messages to display
        @if(session('error'))
            Swal.fire({
                title: 'Error!',
                text: '{{ session('error') }}',
                icon: 'error',
                confirmButtonClass: 'btn btn-primary'
            });
        @endif

        // Supply linking functionality
        document.addEventListener('DOMContentLoaded', function() {
            const suppliesContainer = document.getElementById('supplies-container');
            const addSupplyBtn = document.getElementById('add-supply');
            let supplyIndex = {{ $existingSupplies->count() > 0 ? $existingSupplies->count() : 1 }};

            // Add new supply row
            addSupplyBtn.addEventListener('click', function() {
                const template = `
                    <div class="supply-row mb-3">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label">Supply</label>
                                <select class="form-select supply-select" name="supplies[${supplyIndex}][id]" data-index="${supplyIndex}">
                                    <option value="">Select a supply</option>
                                    @foreach($supplies as $supply)
                                        <option value="{{ $supply->id }}">{{ $supply->name }} ({{ $supply->sku }}) - Stock: {{ $supply->current_stock }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantity Required</label>
                                <input type="number" class="form-control" name="supplies[${supplyIndex}][quantity_required]" min="0.01" step="0.01" value="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Optional?</label>
                                <select class="form-select" name="supplies[${supplyIndex}][is_optional]">
                                    <option value="0">Required</option>
                                    <option value="1">Optional</option>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-danger remove-supply">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                suppliesContainer.insertAdjacentHTML('beforeend', template);
                supplyIndex++;
                
                // Show remove buttons on all rows
                updateRemoveButtons();
            });

            // Remove supply row
            suppliesContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-supply')) {
                    const row = e.target.closest('.supply-row');
                    row.remove();
                    updateRemoveButtons();
                    reindexSupplyRows();
                }
            });

            // Update remove button visibility
            function updateRemoveButtons() {
                const rows = suppliesContainer.querySelectorAll('.supply-row');
                const removeButtons = suppliesContainer.querySelectorAll('.remove-supply');
                
                if (rows.length > 1) {
                    removeButtons.forEach(btn => btn.style.display = 'block');
                } else {
                    removeButtons.forEach(btn => btn.style.display = 'none');
                }
            }

            // Reindex supply rows to maintain sequential array indices
            function reindexSupplyRows() {
                const rows = suppliesContainer.querySelectorAll('.supply-row');
                let newIndex = 0;
                
                rows.forEach(row => {
                    const select = row.querySelector('.supply-select');
                    const quantityInput = row.querySelector('input[type="number"]');
                    const optionalSelect = row.querySelector('select[name$="[is_optional]"]');
                    
                    if (select) {
                        select.name = `supplies[${newIndex}][id]`;
                        select.dataset.index = newIndex;
                    }
                    if (quantityInput) {
                        quantityInput.name = `supplies[${newIndex}][quantity_required]`;
                    }
                    if (optionalSelect) {
                        optionalSelect.name = `supplies[${newIndex}][is_optional]`;
                    }
                    
                    newIndex++;
                });
                
                supplyIndex = newIndex;
            }

            // Initialize remove button visibility
            updateRemoveButtons();
        });
    </script>
@endsection