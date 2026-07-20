@extends('layouts.vertical', ['title' => 'Create Appointment'])

@section('css')
    
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
                                    <div class="d-flex align-items-stretch gap-1">
                                        <div style="flex:1;min-width:0">
                                            <select class="form-select" name="customer_id" id="customer_id" required data-choices data-choices-search-true>
                                                <option value="">Select Customer</option>
                                                @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}" 
                                                    {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }} ({{ $customer->email }})
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary" 
                                                style="white-space:nowrap;display:flex;align-items:center;"
                                                title="Add new customer" data-bs-toggle="modal" data-bs-target="#quickCustomerModal">
                                            <i class="ti ti-plus fs-5"></i>
                                        </button>
                                    </div>
                                    @error('customer_id')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_ids" class="form-label">Services</label>
                                    <select class="form-select" name="service_ids[]" id="service_ids" required multiple data-choices data-choices-search-true>
                                        <option value="">Select Services</option>
                                        @foreach($services as $service)
                                        <option value="{{ $service->id }}" 
                                            {{ (is_array(old('service_ids')) && in_array($service->id, old('service_ids'))) ? 'selected' : '' }}>
                                            {{ $service->name }} ({{ $service->duration }} min, {{ $currencySymbol }}{{ number_format($service->price, 2) }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('service_ids')
                                        <span class="text-danger" role="alert"><small>{{ $message }}</small></span>
                                    @enderror
                                    @error('service_ids.*')
                                        <span class="text-danger" role="alert"><small>{{ $message }}</small></span>
                                    @enderror
                                    <div class="form-text">Hold Ctrl/Cmd to select multiple services</div>
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

    <!-- Quick Customer Modal -->
    <div class="modal fade" id="quickCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="quickCustomerForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small">First Name *</label>
                            <input type="text" name="first_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Last Name *</label>
                            <input type="text" name="last_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Phone *</label>
                            <div class="input-group input-group-sm">
                                <select name="country_code" class="form-select form-control-sm" style="max-width:95px;flex:none" required>
                                    <option value="94">🇱🇰 +94</option>
                                    <option value="91">🇮🇳 +91</option>
                                    <option value="1">🇺🇸 +1</option>
                                    <option value="44">🇬🇧 +44</option>
                                    <option value="61">🇦🇺 +61</option>
                                    <option value="971">🇦🇪 +971</option>
                                </select>
                                <input type="tel" name="local_phone" class="form-control form-control-sm" placeholder="77 123 4567" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i> Add Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@vite(['resources/js/pages/form-choice.js'])
<script>
@if(session('success'))
    Swal.fire({ title: 'Success!', text: '{{ session('success') }}', icon: 'success', confirmButtonClass: 'btn btn-primary' });
@endif
@if(session('error'))
    Swal.fire({ title: 'Error!', text: '{{ session('error') }}', icon: 'error', confirmButtonClass: 'btn btn-primary' });
@endif
</script>
<script>
document.getElementById('quickCustomerForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = this, btn = form.querySelector('[type="submit"]');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    fetch('{{ route("customers.quick-store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') },
        body: (function() {
            var fd = new FormData(form);
            // Combine country_code + local_phone into phone
            var cc = fd.get('country_code') || '94';
            var lp = fd.get('local_phone') || '';
            fd.set('phone', cc + lp.replace(/[^0-9]/g, ''));
            fd.delete('country_code');
            fd.delete('local_phone');
            return fd;
        })()
    })
    .then(r => r.json())
    .then(data => {
        if (data.success || data.id) {
            const id = data.id || data.customer?.id;
            const name = data.name || data.customer?.name;
            const email = data.email || data.customer?.email || '';
            bootstrap.Modal.getInstance(document.getElementById('quickCustomerModal'))?.hide();
            form.reset();
            const select = document.getElementById('customer_id');
            if (select.choices) {
                select.choices.setChoices([{ value: id, label: name + ' (' + email + ')', selected: true }], 'value', 'label', true);
            } else {
                select.add(new Option(name + ' (' + email + ')', id, true, true));
            }
            Swal.fire({ title: 'Customer added!', icon: 'success', timer: 1500, showConfirmButton: false });
        } else {
            alert(data.message || 'Failed to create customer.');
        }
    })
    .catch(() => alert('Network error.'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="ti ti-plus me-1"></i> Add Customer'; });
});
</script>
@endsection