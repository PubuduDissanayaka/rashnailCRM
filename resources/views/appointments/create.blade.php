@extends('layouts.vertical', ['title' => 'Create Appointment'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Book New Appointment'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('appointments.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">Customer</label>
                                    <select class="form-select" name="customer_id" id="customer_id" required data-choices data-choices-search-true>
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" 
                                            {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->email }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_id" class="form-label">Service</label>
                                    <select class="form-select" name="service_id" id="service_id" required data-choices>
                                        <option value="">Select Service</option>
                                        @foreach($services as $service)
                                        <option value="{{ $service->id }}" 
                                            {{ old('service_id') == $service->id ? 'selected' : '' }}>
                                            {{ $service->name }} ({{ $service->duration }} min, ${{ number_format($service->price, 2) }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('service_id')
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
                                    <label for="user_id" class="form-label">Staff Member</label>
                                    <select class="form-select" name="user_id" id="user_id" required data-choices>
                                        <option value="">Select Staff</option>
                                        @foreach($staff as $member)
                                        <option value="{{ $member->id }}" 
                                            {{ old('user_id') == $member->id ? 'selected' : '' }}>
                                            {{ $member->name }} ({{ ucfirst($member->role) }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="appointment_date" class="form-label">Date & Time</label>
                                    <input type="datetime-local" class="form-control" name="appointment_date" id="appointment_date" 
                                           value="{{ old('appointment_date') }}" required>
                                    @error('appointment_date')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="notes" rows="3" 
                                      placeholder="Any special requests or notes...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="text-danger" role="alert">
                                    <small>{{ $message }}</small>
                                </span>
                            @enderror
                        </div>
                        <div class="text-end">
                            <a href="{{ route('appointments.index') }}" class="btn btn-secondary me-1">Cancel</a>
                            <button class="btn btn-primary" type="submit">Book Appointment</button>
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
    </script>
@endsection