@extends('layouts.vertical', ['title' => 'Edit Customer'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Edit Customer'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('customers.update', $customer) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input class="form-control" type="text" id="first_name" name="first_name" value="{{ old('first_name', $customer->first_name) }}" required>
                                    @error('first_name')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input class="form-control" type="text" id="last_name" name="last_name" value="{{ old('last_name', $customer->last_name) }}" required>
                                    @error('last_name')
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
                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>

                                    <!-- Country Code + Phone Input Group -->
                                    <div class="input-group">
                                        <!-- Country Code Selector -->
                                        <select class="form-select" id="country-code-select" name="country_code"
                                                required style="max-width: 180px;">
                                            @foreach($countries as $country)
                                                <option value="{{ $country['code'] }}"
                                                        data-format="{{ $country['format'] }}"
                                                        {{ $country['code'] == old('country_code', $phoneData['country_code']) ? 'selected' : '' }}>
                                                    {{ $country['flag'] }} +{{ $country['code'] }} {{ $country['name'] }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <!-- Local Phone Number Input -->
                                        <input type="tel" class="form-control" id="local-phone-input"
                                               name="local_phone"
                                               placeholder="77 123 4567"
                                               required
                                               value="{{ old('local_phone', $phoneData['local_number']) }}">
                                    </div>

                                    <!-- Helper Text -->
                                    <div class="form-text" id="phone-format-hint">
                                        Format: <span id="format-example">77 123 4567</span>
                                    </div>

                                    <!-- Hidden field for full phone (WhatsApp format) -->
                                    <input type="hidden" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}">

                                    <!-- Error Messages -->
                                    @error('phone')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                    @error('country_code')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                    @error('local_phone')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input class="form-control" type="email" id="email" name="email" value="{{ old('email', $customer->email) }}">
                                    @error('email')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input class="form-control" type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $customer->date_of_birth?->format('Y-m-d')) }}">
                                    @error('date_of_birth')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-control" id="gender" name="gender" data-choices data-choices-search-false>
                                        <option value="">Select Gender</option>
                                        <option value="male" @selected(old('gender', $customer->gender) === 'male')>Male</option>
                                        <option value="female" @selected(old('gender', $customer->gender) === 'female')>Female</option>
                                        <option value="other" @selected(old('gender', $customer->gender) === 'other')>Other</option>
                                        <option value="prefer_not_to_say" @selected(old('gender', $customer->gender) === 'prefer_not_to_say')>Prefer not to say</option>
                                    </select>
                                    @error('gender')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status" data-choices data-choices-search-false>
                                        <option value="active" @selected(old('status', $customer->status ?? 'active') === 'active')>Active</option>
                                        <option value="inactive" @selected(old('status', $customer->status ?? 'active') === 'inactive')>Inactive</option>
                                        <option value="vip" @selected(old('status', $customer->status ?? 'active') === 'vip')>VIP</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $customer->address) }}</textarea>
                                    @error('address')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $customer->notes) }}</textarea>
                                    @error('notes')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-primary" type="submit">Update Customer</button>
                            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/form-choice.js', 'resources/js/pages/customers-form.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
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