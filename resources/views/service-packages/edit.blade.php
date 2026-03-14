@extends('layouts.vertical', ['title' => 'Edit Service Package'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('scripts')
    @vite(['resources/js/pages/form-choice.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        // Pass selected services from PHP to JavaScript
        const preSelectedServices = @json($selectedServices ?? []);
        const preQuantities = @json($serviceQuantities ?? []);

        document.addEventListener('DOMContentLoaded', function() {
            const servicePrices = {};
            const servicesSelect = document.getElementById('servicesSelect');
            const quantityContainer = document.getElementById('quantityInputsContainer');
            
            // Initialize prices map
            if (servicesSelect) {
                Array.from(servicesSelect.options).forEach(option => {
                    if (option.value) {
                        servicePrices[option.value] = parseFloat(option.getAttribute('data-price')) || 0;
                    }
                });
            }

            // Function to generate quantity input for a service
            function createQuantityInput(serviceId, serviceName, value = 1) {
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
                               value="${value}" 
                               data-service-id="${serviceId}"
                               onchange="calculateTotal()"
                               onkeyup="calculateTotal()">
                    </div>
                `;
                return div;
            }

            // Handle Choices.js events
            if (servicesSelect) {
                servicesSelect.addEventListener('change', function(e) {
                    updateQuantityInputs();
                    calculateTotal();
                });
                
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
                    // If row doesn't exist, create it
                    if (!document.getElementById(`quantity-row-${serviceId}`)) {
                        const serviceName = option.text.split(' ({{ $currencySymbol }}')[0]; // Clean name
                        // Check if we have a pre-existing quantity (for initial load)
                        let qty = 1;
                        // Start with preQuantities if available
                        if (preQuantities && preQuantities[serviceId]) {
                            qty = preQuantities[serviceId];
                        }
                        
                        quantityContainer.appendChild(createQuantityInput(serviceId, serviceName, qty));
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
                const basePriceDisplay = document.getElementById('basePriceDisplay');
                const basePriceInput = document.getElementById('basePriceInput');
                if (basePriceDisplay) basePriceDisplay.value = basePrice.toFixed(2);
                if (basePriceInput) basePriceInput.value = basePrice.toFixed(2);

                const discountedInput = document.getElementById('discountedPriceInput');
                const discountedPrice = parseFloat(discountedInput ? discountedInput.value : 0) || 0;
                const savings = basePrice - discountedPrice;
                
                const savingsInput = document.getElementById('savingsInput');
                if (savingsInput) savingsInput.value = savings.toFixed(2);
            };

            // Initial setup methods
            const discountedInput = document.getElementById('discountedPriceInput');
            if (discountedInput) {
                discountedInput.addEventListener('input', calculateTotal);
                discountedInput.addEventListener('keyup', calculateTotal);
            }
            
            // Run once to initialize
            updateQuantityInputs();
            calculateTotal();

            // Check for success messages to display
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
    @include('layouts.partials.page-title', ['title' => 'Edit Service Package'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('service-packages.update', $servicePackage->slug) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Package Name</label>
                                    <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $servicePackage->name) }}" required>
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
                                        <option value="1" {{ old('is_active', $servicePackage->is_active) ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active', !$servicePackage->is_active) ? 'selected' : '' }}>Inactive</option>
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
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $servicePackage->description) }}</textarea>
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
                                <option value="{{ $service->id }}" data-price="{{ $service->price }}"
                                    {{ in_array($service->id, old('services', $selectedServices ?? [])) ? 'selected' : '' }}>
                                    {{ $service->name }} ({{ $currencySymbol }}{!! number_format($service->price, 2) !!})
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple services</div>
                        </div>

                        <!-- Quantity inputs for each selected service -->
                        <div id="quantityInputsContainer">
                            <!-- Dynamically populate quantity inputs based on selected services -->
                            @if(count($servicePackage->services) > 0)
                                <h6 class="mb-3">Service Quantities</h6>
                                @foreach($servicePackage->services as $service)
                                    <div class="row mb-2">
                                        <div class="col-md-8">
                                            <label class="form-label">{{ $service->name }}</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number"
                                                   class="form-control quantity-input"
                                                   name="quantities[{{ $service->id }}]"
                                                   min="1"
                                                   value="{{ old('quantities.'.$loop->index, $serviceQuantities[$service->id] ?? 1) }}"
                                                   data-service-id="{{ $service->id }}"
                                                   onchange="calculateTotal()">
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Base Price (Calculated)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $currencySymbol }}</span>
                                        <input type="text" class="form-control" id="basePriceDisplay" readonly value="{{ number_format($servicePackage->base_price, 2) }}">
                                        <input type="hidden" id="basePriceInput" name="base_price" value="{{ old('base_price', $servicePackage->base_price) }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="discountedPriceInput" class="form-label">Discounted Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $currencySymbol }}</span>
                                        <input type="number" class="form-control" id="discountedPriceInput" name="discounted_price" step="0.01" min="0"
                                            value="{{ old('discounted_price', $servicePackage->discounted_price) }}" required placeholder="Enter discounted price">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="savingsInput" class="form-label">Savings</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $currencySymbol }}</span>
                                        <input type="text" class="form-control" id="savingsInput" name="savings" readonly
                                            value="{{ old('savings', number_format($servicePackage->base_price - $servicePackage->discounted_price, 2)) }}" placeholder="Automatically calculated">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button class="btn btn-primary" type="submit">Update Package</button>
                            <a href="{{ route('service-packages.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection