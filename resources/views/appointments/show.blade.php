@extends('layouts.vertical', ['title' => $appointment->customer->name . '\'s Appointment'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Appointment Details'])

    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-sm-row flex-column align-items-sm-center justify-content-sm-between my-3">
                <div class="flex-grow-1">
                    <h4 class="fs-xl mb-1">Appointment Details</h4>
                    <p class="text-muted mb-0">{{ $appointment->customer->name }} - {{ $appointment->service->name }}</p>
                </div>
                <div class="text-end mt-3 mt-sm-0">
                    <a href="{{ route('appointments.index') }}" class="btn btn-light me-1">
                        <i class="ti ti-arrow-left me-1"></i> Back to Appointments
                    </a>
                    @can('edit appointments')
                    <a href="{{ route('appointments.edit', $appointment->slug) }}" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i> Edit Appointment
                    </a>
                    @endcan
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-centered table-nowrap mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold" style="width: 30%;">Customer</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm rounded-circle bg-soft-primary me-2">
                                                        <span class="avatar-title rounded-circle">
                                                            {{ strtoupper(substr($appointment->customer->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h5 class="fs-base mb-0">{{ $appointment->customer->name }}</h5>
                                                        <p class="text-muted mb-0 fs-xs">{{ $appointment->customer->email }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Service</td>
                                            <td>
                                                <h5 class="fs-base mb-0">{{ $appointment->service->name }}</h5>
                                                <p class="text-muted mb-0 fs-xs">${{ number_format($appointment->service->price, 2) }} • {{ $appointment->service->duration }} min</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Staff</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm rounded-circle bg-soft-secondary me-2">
                                                        <span class="avatar-title rounded-circle">
                                                            {{ strtoupper(substr($appointment->user->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h5 class="fs-base mb-0">{{ $appointment->user->name }}</h5>
                                                        <p class="text-muted mb-0 fs-xs">{{ ucfirst($appointment->user->role) }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Date & Time</td>
                                            <td>{{ $appointment->formatted_date_time }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Duration</td>
                                            <td>{{ $appointment->duration }} minutes</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Status</td>
                                            <td>
                                                <span class="badge bg-{{ $appointment->status_badge }}-subtle text-{{ $appointment->status_badge }}">
                                                    <i class="ti ti-circle-filled fs-xs me-1"></i> {{ $appointment->status_label }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Created At</td>
                                            <td>{{ $appointment->created_at->format('M d, Y h:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Updated At</td>
                                            <td>{{ $appointment->updated_at->format('M d, Y h:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Notes</td>
                                            <td>{!! nl2br(e($appointment->notes ?? 'No notes provided.')) !!}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Appointment Actions</h5>
                            
                            <!-- Status Change Buttons -->
                            <div class="mb-3">
                                <label class="form-label">Update Status</label>
                                <div class="d-grid gap-2">
                                    @if($appointment->status !== 'in_progress')
                                    <form method="POST" action="{{ route('appointments.update-status', $appointment->slug) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="in_progress">
                                        <button type="submit" class="btn btn-warning w-100" 
                                            onclick="return confirm('Mark this appointment as in progress?')">
                                            <i class="ti ti-play me-1"></i> Start Appointment
                                        </button>
                                    </form>
                                    @endif
                                    
                                    @if($appointment->status !== 'completed')
                                    <form method="POST" action="{{ route('appointments.update-status', $appointment->slug) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn btn-success w-100" 
                                            onclick="return confirm('Mark this appointment as completed?')">
                                            <i class="ti ti-check me-1"></i> Complete Appointment
                                        </button>
                                    </form>
                                    @endif
                                    
                                    @if($appointment->canBeCancelled())
                                    <form method="POST" action="{{ route('appointments.destroy', $appointment->slug) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger w-100" 
                                            onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                            <i class="ti ti-x me-1"></i> Cancel Appointment
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Customer Contact -->
                            <div class="mb-3">
                                <label class="form-label">Customer Contact</label>
                                <div class="d-grid gap-2">
                                    <a href="tel:{{ $appointment->customer->phone }}" class="btn btn-outline-primary w-100">
                                        <i class="ti ti-phone me-1"></i> {{ $appointment->customer->phone ?? 'No phone provided' }}
                                    </a>
                                    <a href="mailto:{{ $appointment->customer->email }}" class="btn btn-outline-info w-100">
                                        <i class="ti ti-mail me-1"></i> {{ $appointment->customer->email }}
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Customer History -->
                            <div>
                                <label class="form-label">Customer History</label>
                                <div class="d-grid gap-2">
                                    <a href="{{ route('customers.show', $appointment->customer->slug) }}" class="btn btn-light w-100">
                                        <i class="ti ti-user me-1"></i> View Customer Profile
                                    </a>
                                    <a href="{{ route('appointments.index') }}?customer={{ $appointment->customer->id }}" class="btn btn-light w-100">
                                        <i class="ti ti-calendar me-1"></i> View Customer Appointments
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
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