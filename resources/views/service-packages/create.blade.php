@extends('layouts.vertical', ['title' => 'Create Service Package'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('scripts')
    @vite(['resources/js/pages/form-choice.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const servicePrices = {};
            const servicesSelect = document.getElementById('servicesSelect');
            const quantityContainer = document.getElementById('quantityInputsContainer');
            const bottomContainer = document.getElementById('bottomCalculations');
            
            // Initialize prices map
            if (servicesSelect) {
                Array.from(servicesSelect.options).forEach(option => {
                    if (option.value) {
                        servicePrices[option.value] = parseFloat(option.getAttribute('data-price')) || 0;
                    }
                });
            }

            // Function to generate quantity input for a service
            function createQuantityInput(serviceId, serviceName) {
                const div = document.createElement('div');
                div.className = 'row g-2 align-items-center mb-2 quantity-row w-100 mx-0';
                div.id = `quantity-row-${serviceId}`;
                div.innerHTML = `
                    <div class="col-8">
                        <label class="form-label mb-0 text-truncate" title="${serviceName}">${serviceName}</label>
                    </div>
                    <div class="col-4">
                        <input type="number" 
                               class="form-control quantity-input" 
                               name="quantities[${serviceId}]" 
                               min="1" 
                               value="1" 
                               data-service-id="${serviceId}"
                               onchange="calculateTotal()"
                               onkeyup="calculateTotal()">
                    </div>
                `;
                return div;
            }

            // Handle Choices.js events if available, otherwise fallback to change
            // We use a MutationObserver to detect when Choices.js hides the original select and creates its structure, 
            // but since we can't easily access the Choices instance created by the other script, 
            // we will listen to the standard 'change' event which Choices.js typically triggers on the original select,
            // OR we can try to hook into the library's specific events if emitted on the element.
            
            if (servicesSelect) {
                servicesSelect.addEventListener('change', function(e) {
                    updateQuantityInputs();
                    calculateTotal();
                });
                
                // Also listen for specific Choices.js events
                servicesSelect.addEventListener('addItem', function(e) {
                    updateQuantityInputs();
                    calculateTotal();
                });
                
                servicesSelect.addEventListener('removeItem', function(e) {
                    updateQuantityInputs();
                    calculateTotal();
                });
            }

            function updateQuantityInputs() {
                if (!servicesSelect) return;

                const selectedOptions = Array.from(servicesSelect.selectedOptions);
                const selectedIds = selectedOptions.map(opt => opt.value);
                
                // Remove inputs for deselected services
                const existingRows = quantityContainer.querySelectorAll('.quantity-row');
                existingRows.forEach(row => {
                    const id = row.id.replace('quantity-row-', '');
                    if (!selectedIds.includes(id)) {
                        row.remove();
                    }
                });

                // Add inputs for new selected services
                if (selectedIds.length > 0 && quantityContainer.querySelectorAll('h6').length === 0) {
                     const header = document.createElement('h6');
                     header.textContent = 'Service Quantities';
                     header.className = 'mb-3';
                     quantityContainer.prepend(header);
                } else if (selectedIds.length === 0) {
                    quantityContainer.innerHTML = '';
                }

                selectedOptions.forEach(option => {
                    const serviceId = option.value;
                    if (!document.getElementById(`quantity-row-${serviceId}`)) {
                        const serviceName = option.text.split(' ({{ $currencySymbol }}')[0]; // Clean name
                        quantityContainer.appendChild(createQuantityInput(serviceId, serviceName));
                    }
                });
            }

            window.calculateTotal = function() {
                let basePrice = 0;
                
                // Calculate from quantity inputs
                document.querySelectorAll('.quantity-input').forEach(input => {
                    const serviceId = input.getAttribute('data-service-id');
                    const quantity = parseInt(input.value) || 0;
                    const price = servicePrices[serviceId] || 0;
                    basePrice += price * quantity;
                });

                // Update displays
                document.getElementById('basePriceDisplay').value = basePrice.toFixed(2);
                document.getElementById('basePriceInput').value = basePrice.toFixed(2);

                const discountedPrice = parseFloat(document.getElementById('discountedPriceInput').value) || 0;
                const savings = basePrice - discountedPrice;
                
                document.getElementById('savingsInput').value = savings.toFixed(2);
            };

            // Initial setup
            const discountedInput = document.getElementById('discountedPriceInput');
            if (discountedInput) {
                discountedInput.addEventListener('input', calculateTotal);
                discountedInput.addEventListener('keyup', calculateTotal);
            }

            // Check for SweetAlert messages
            @if(session('success'))
                Swal.fire({
                    title: 'Success!',
                    text: '{{ session('success') }}',
                    icon: 'success',
                    confirmButtonClass: 'btn btn-primary'
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    icon: 'error',
                    confirmButtonClass: 'btn btn-primary'
                });
            @endif
        });
    </script>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Create Service Package'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('service-packages.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Package Name</label>
                                    <input class="form-control" type="text" id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Status</label>
                                    <select class="form-select" id="is_active" name="is_active" required>
                                        <option value="1" {{ old('is_active', true) ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active') ? 'selected' : '' }}>Inactive</option>
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
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="text-danger" role="alert">
                                    <small>{{ $message }}</small>
                                </span>
                            @enderror
                        </div>
                        
                        <h5 class="mb-3">Services in Package</h5>

                        <!-- Multi-select with Choices.js for services -->
                        <div class="mb-3">
                            <label class="form-label">Select Services</label>
                            <select class="form-select" name="services[]" id="servicesSelect" data-choices multiple>
                                @foreach($services as $service)
                                <option value="{{ $service->id }}" data-price="{{ $service->price }}">{{ $service->name }} ({{ $currencySymbol }}{{ number_format($service->price, 2) }})</option>
                                @endforeach
                            </select>
                            <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple services</div>
                        </div>

                        <!-- Quantity inputs for each selected service -->
                        <div id="quantityInputsContainer">
                            <!-- Dynamically populate quantity inputs based on selected services -->
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Base Price (Calculated)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $currencySymbol }}</span>
                                        <input type="text" class="form-control" id="basePriceDisplay" readonly value="0.00">
                                        <input type="hidden" id="basePriceInput" name="base_price" value="0.00">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="discountedPriceInput" class="form-label">Discounted Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $currencySymbol }}</span>
                                        <input type="number" class="form-control" id="discountedPriceInput" name="discounted_price" step="0.01" min="0" required placeholder="Enter discounted price">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="savingsInput" class="form-label">Savings</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $currencySymbol }}</span>
                                        <input type="text" class="form-control" id="savingsInput" name="savings" readonly placeholder="Automatically calculated">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button class="btn btn-primary" type="submit">Create Package</button>
                            <a href="{{ route('service-packages.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection