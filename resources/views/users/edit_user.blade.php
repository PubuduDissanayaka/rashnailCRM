@extends('layouts.vertical', ['title' => 'Edit User'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
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
        @if(session('error') || $errors->any())
            Swal.fire({
                title: 'Error!',
                text: '{{ session('error') ?: $errors->first() }}',
                icon: 'error',
                confirmButtonClass: 'btn btn-primary'
            });
        @endif
    </script>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Edit User'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                    @error('name')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input class="form-control" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                    @error('email')
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
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input class="form-control" type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                                    @error('phone')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-control" id="role" name="role" data-choices data-trigger required>
                                        <option value="">Select Role</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role }}" {{ old('role', $user->role) === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                                        @endforeach
                                    </select>
                                    @error('role')
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
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status" data-choices data-trigger required>
                                        <option value="">Select Status</option>
                                        <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    </select>
                                    @error('status')
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
                                    <label for="password" class="form-label">New Password</label>
                                    <input class="form-control" type="password" id="password" name="password">
                                    <div class="form-text">Leave blank to keep current password</div>
                                    @error('password')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation">
                                    @error('password_confirmation')
                                        <span class="text-danger" role="alert">
                                            <small>{{ $message }}</small>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-primary" type="submit">Update User</button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@endsection