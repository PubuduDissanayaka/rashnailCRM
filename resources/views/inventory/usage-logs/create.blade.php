@extends('layouts.vertical')

@section('title', 'Log Supply Usage')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('inventory.supplies.index') }}">Inventory</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('inventory.usage-logs.index') }}">Usage Logs</a></li>
                        <li class="breadcrumb-item active">Log Usage</li>
                    </ol>
                </div>
                <h4 class="page-title">Log Supply Usage</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('inventory.usage-logs.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="supply_id" class="form-label">Supply <span class="text-danger">*</span></label>
                                    <select class="form-select @error('supply_id') is-invalid @enderror" id="supply_id" name="supply_id" required>
                                        <option value="">Select Supply</option>
                                        @foreach($supplies as $supply)
                                            <option value="{{ $supply->id }}" {{ old('supply_id') == $supply->id ? 'selected' : '' }}>
                                                {{ $supply->name }} (SKU: {{ $supply->sku }}) - Stock: {{ $supply->current_stock }} {{ $supply->unit_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supply_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantity_used" class="form-label">Quantity Used <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" class="form-control @error('quantity_used') is-invalid @enderror" 
                                           id="quantity_used" name="quantity_used" value="{{ old('quantity_used', 1) }}" required>
                                    @error('quantity_used')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Enter the quantity used from stock</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="appointment_id" class="form-label">Appointment (Optional)</label>
                                    <select class="form-select @error('appointment_id') is-invalid @enderror" id="appointment_id" name="appointment_id">
                                        <option value="">Select Appointment</option>
                                        @foreach($appointments as $appointment)
                                            <option value="{{ $appointment->id }}" {{ old('appointment_id') == $appointment->id ? 'selected' : '' }}>
                                                #{{ $appointment->slug }} - {{ $appointment->customer->name ?? 'N/A' }} - {{ $appointment->appointment_date->format('M d, Y h:i A') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('appointment_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Link this usage to a completed appointment</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_id" class="form-label">Service (Optional)</label>
                                    <select class="form-select @error('service_id') is-invalid @enderror" id="service_id" name="service_id">
                                        <option value="">Select Service</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                                                {{ $service->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('service_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="used_by" class="form-label">Staff (Optional)</label>
                                    <select class="form-select @error('used_by') is-invalid @enderror" id="used_by" name="used_by">
                                        <option value="">Select Staff</option>
                                        @foreach($staff as $user)
                                            <option value="{{ $user->id }}" {{ old('used_by') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('used_by')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">Customer (Optional)</label>
                                    <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id">
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unit_cost" class="form-label">Unit Cost (Optional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" min="0" class="form-control @error('unit_cost') is-invalid @enderror" 
                                               id="unit_cost" name="unit_cost" value="{{ old('unit_cost') }}">
                                    </div>
                                    @error('unit_cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Leave empty to use supply's current unit cost</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="batch_number" class="form-label">Batch Number (Optional)</label>
                                    <input type="text" class="form-control @error('batch_number') is-invalid @enderror" 
                                           id="batch_number" name="batch_number" value="{{ old('batch_number') }}">
                                    @error('batch_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="used_at" class="form-label">Used At (Optional)</label>
                                    <input type="datetime-local" class="form-control @error('used_at') is-invalid @enderror" 
                                           id="used_at" name="used_at" value="{{ old('used_at') }}">
                                    @error('used_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Leave empty to use current date and time</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes (Optional)</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('inventory.usage-logs.index') }}" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Log Usage</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Set default used_at to current datetime
            if (!$('#used_at').val()) {
                var now = new Date();
                var localDateTime = now.toISOString().slice(0, 16);
                $('#used_at').val(localDateTime);
            }

            // When supply is selected, show current stock and unit cost
            $('#supply_id').change(function() {
                var supplyId = $(this).val();
                if (supplyId) {
                    // In a real implementation, you might want to fetch supply details via AJAX
                    // For now, we'll just update the placeholder text
                    console.log('Supply selected:', supplyId);
                }
            });
        });
    </script>
@endsection