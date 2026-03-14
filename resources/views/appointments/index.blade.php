@extends('layouts.vertical', ['title' => 'Appointment Management'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css'])
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.css" rel="stylesheet">
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Appointments'])

    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-sm-row flex-column align-items-sm-center gap-3 mb-3">
                <h4 class="mb-0">Appointment List</h4>
                <div class="flex-grow-1"></div>
                <a href="{{ route('appointments.create') }}" class="btn btn-primary">
                    <i class="ti ti-calendar-plus me-1"></i> Book Appointment
                </a>
                <a href="{{ route('appointments.calendar') }}" class="btn btn-light">
                    <i class="ti ti-calendar-event me-1"></i> Calendar View
                </a>
            </div>
            
            <!-- Stats Section -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 mb-1">{{ $stats['total'] }}</h5>
                                <p class="mb-0 text-uppercase fs-12 fw-bold">Total Appointments</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 mb-1">{{ $stats['today'] }}</h5>
                                <p class="mb-0 text-uppercase fs-12 fw-bold">Today's Appointments</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 mb-1">{{ $stats['upcoming'] }}</h5>
                                <p class="mb-0 text-uppercase fs-12 fw-bold">Upcoming</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 mb-1">{{ $stats['completed'] }}</h5>
                                <p class="mb-0 text-uppercase fs-12 fw-bold">Completed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Appointments Table -->
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search appointments..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        <button class="btn btn-danger d-none" data-table-delete-selected="">Delete</button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Filter by Status -->
                        <div class="app-search">
                            <select class="form-select" data-table-filter="status">
                                <option value="All">Status</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <i class="app-search-icon text-muted" data-lucide="circle-check"></i>
                        </div>
                        <!-- Filter by Staff -->
                        <div class="app-search">
                            <select class="form-select" data-table-filter="staff">
                                <option value="All">Staff</option>
                                @foreach($staff as $member)
                                    <option value="{{ $member->name }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                            <i class="app-search-icon text-muted" data-lucide="users"></i>
                        </div>
                        <!-- Filter by Date -->
                        <div class="app-search">
                            <input type="date" class="form-control" data-table-filter="date" />
                            <i class="app-search-icon text-muted" data-lucide="calendar"></i>
                        </div>
                        <!-- Records Per Page -->
                        <div>
                            <select class="form-select" data-table-set-rows-per-page="">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input class="form-check-input" data-table-select-all="" type="checkbox" value="" />
                                </th>
                                <th data-table-sort="sort-date">Date</th>
                                <th data-table-sort="sort-customer">Customer</th>
                                <th data-table-sort="sort-service">Service</th>
                                <th data-table-sort="sort-staff">Staff</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($appointments as $appointment)
                            <tr>
                                <td class="ps-3">
                                    <input class="form-check-input product-item-check" type="checkbox" value="" />
                                </td>
                                <td data-sort="sort-date">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h5 class="fs-base mb-0">{{ $appointment->date_only }}</h5>
                                            <p class="text-muted mb-0 fs-xs">{{ $appointment->time_only }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td data-sort="sort-customer">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded bg-soft-primary me-2">
                                            <span class="avatar-title rounded-circle">
                                                {{ strtoupper(substr($appointment->customer->first_name, 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="fs-base mb-0">{{ $appointment->customer->full_name }}</h5>
                                            <p class="text-muted mb-0 fs-xs">{{ $appointment->customer->phone ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td data-sort="sort-service">{{ $appointment->service->name }}</td>
                                <td data-sort="sort-staff">{{ $appointment->user->name }}</td>
                                <td data-sort="sort-status">
                                    <span class="badge bg-{{ $appointment->status_badge }}-subtle text-{{ $appointment->status_badge }}">
                                        <i class="ti ti-circle-filled fs-xs me-1"></i> {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('appointments.show', $appointment->slug) }}" title="View Details">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @can('edit appointments')
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('appointments.edit', $appointment->slug) }}" title="Edit Appointment">
                                            <i class="ti ti-edit fs-lg"></i>
                                        </a>
                                        @endcan
                                        @can('delete appointments')
                                        @if($appointment->canBeCancelled())
                                        <form id="cancel-form-{{ $appointment->id }}" action="{{ route('appointments.destroy', $appointment->slug) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle" 
                                                onclick="confirmCancellation({{ $appointment->id }}, '{{ addslashes($appointment->customer->name) }}')" 
                                                title="Cancel Appointment">
                                            <i class="ti ti-trash fs-lg"></i>
                                        </button>
                                        @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-calendar-off fs-24 mb-2 d-block"></i>
                                        No appointments found. <a href="{{ route('appointments.create') }}">Create the first appointment</a>.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="appointments"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function confirmCancellation(appointmentId, customerName) {
            Swal.fire({
                title: 'Confirm Cancellation',
                text: `Are you sure you want to cancel ${customerName}'s appointment?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`cancel-form-${appointmentId}`).submit();
                }
            });
        }
    </script>
@endsection