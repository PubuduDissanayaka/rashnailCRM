@extends('layouts.vertical', ['title' => 'Customer Management'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Customer Management'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search customers..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        <button class="btn btn-danger d-none" data-table-delete-selected="">Delete</button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="me-2 fw-semibold">Filter By:</span>
                        <!-- Gender Filter -->
                        <div class="app-search">
                            <select class="form-select form-control my-1 my-md-0" data-table-filter="gender">
                                <option value="All">Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <i class="app-search-icon text-muted" data-lucide="user"></i>
                        </div>
                        <!-- Records Per Page -->
                        <div>
                            <select class="form-select form-control my-1 my-md-0" data-table-set-rows-per-page="">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                        @can('create customers')
                        <a href="{{ route('customers.create') }}" class="btn btn-primary">
                            <i class="ti ti-user-plus me-1"></i> Add Customer
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input class="form-check-input form-check-input-light fs-14 mt-0" data-table-select-all="" type="checkbox" value="option" />
                                </th>
                                <th data-table-sort="sort-name">Name</th>
                                <th data-table-sort="sort-phone">Phone</th>
                                <th data-table-sort="sort-email">Email</th>
                                <th data-table-sort="sort-gender">Gender</th>
                                <th data-table-sort="sort-appointments">Appointments</th>
                                <th data-table-sort="sort-spent">Total Spent</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                            <tr>
                                <td class="ps-3">
                                    <input class="form-check-input form-check-input-light fs-14 product-item-check mt-0" type="checkbox" value="option" />
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-circle bg-soft-primary me-2">
                                            <span class="avatar-title rounded-circle text-uppercase">
                                                {{ $customer->initials }}
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="fs-base mb-0">
                                                <a class="link-reset" href="{{ route('customers.show', $customer->slug) }}">
                                                    {{ $customer->full_name }}
                                                </a>
                                            </h5>
                                        </div>
                                    </div>
                                </td>
                                <td data-sort="sort-phone">{{ $customer->phone }}</td>
                                <td data-sort="sort-email">{{ $customer->email ?? 'N/A' }}</td>
                                <td data-sort="sort-gender">
                                    <span class="badge bg-{{ $customer->gender === 'Female' ? 'danger' : ($customer->gender === 'Male' ? 'primary' : 'secondary') }}-subtle text-{{ $customer->gender === 'Female' ? 'danger' : ($customer->gender === 'Male' ? 'primary' : 'secondary') }}">
                                        {{ $customer->gender ?? 'N/A' }}
                                    </span>
                                </td>
                                <td data-sort="sort-appointments">{{ $customer->appointments_count }}</td>
                                <td data-sort="sort-spent">${{ number_format($customer->transactions_sum_amount ?? 0, 2) }}</td>
                                <td data-sort="sort-status">
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="ti ti-circle-filled fs-xs"></i> Active
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('customers.show', $customer->slug) }}" title="View Profile">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @can('edit customers')
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('customers.edit', $customer->slug) }}" title="Edit Customer">
                                            <i class="ti ti-edit fs-lg"></i>
                                        </a>
                                        @endcan
                                        @can('delete customers')
                                        <form method="POST" action="{{ route('customers.destroy', $customer->slug) }}" style="display: inline;" id="delete-form-{{ $customer->slug }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                                                onclick="confirmDelete('{{ $customer->id }}', '{{ addslashes($customer->name) }}', '{{ $customer->slug }}')"
                                                title="Delete Customer">
                                                <i class="ti ti-trash fs-lg"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="customers"></div>
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
        function confirmDelete(customerId, customerName, customerSlug) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete customer "${customerName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit the form with the specific customer slug
                    document.getElementById(`delete-form-${customerSlug}`).submit();

                    // Show a success message after deletion
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'The customer has been successfully deleted.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });
        }
    </script>
@endsection