@extends('layouts.vertical', ['title' => 'Create Customer'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Create Customer'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('customers.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input class="form-control" type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
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
                                    <input class="form-control" type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
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
                                                        {{ $country['code'] == old('country_code', $defaultCountryCode) ? 'selected' : '' }}>
                                                    {{ $country['flag'] }} +{{ $country['code'] }} {{ $country['name'] }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <!-- Local Phone Number Input -->
                                        <input type="tel" class="form-control" id="local-phone-input"
                                               name="local_phone"
                                               placeholder="77 123 4567"
                                               required
                                               value="{{ old('local_phone') }}">
                                    </div>

                                    <!-- Helper Text -->
                                    <div class="form-text" id="phone-format-hint">
                                        Format: <span id="format-example">77 123 4567</span>
                                    </div>

                                    <!-- Hidden field for full phone (WhatsApp format) -->
                                    <input type="hidden" id="phone" name="phone" value="{{ old('phone') }}">

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
                                    <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}">
                                    @error('email')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input class="form-control" type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}">
                                    @error('date_of_birth')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="joined_date" class="form-label">Joined Date</label>
                                    <input class="form-control" type="date" id="joined_date" name="joined_date"
                                           value="{{ old('joined_date', now()->toDateString()) }}"
                                           max="{{ now()->toDateString() }}">
                                    @error('joined_date')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-control" id="gender" name="gender" data-choices data-choices-search-false>
                                        <option value="">Select Gender</option>
                                        <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                                        <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                                        <option value="prefer_not_to_say" {{ old('gender') === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                                    </select>
                                    @error('gender')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status" data-choices data-choices-search-false>
                                        <option value="active" @selected(old('status') !== 'inactive' && old('status') !== 'vip')>Active</option>
                                        <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                                        <option value="vip" @selected(old('status') === 'vip')>VIP</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3">{{ old('address') }}</textarea>
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
                                    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-primary" type="submit">Create Customer</button>
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