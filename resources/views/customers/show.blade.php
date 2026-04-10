@extends('layouts.vertical', ['title' => $customer->full_name . '\'s Profile'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
    <style>
        .timeline {
            position: relative;
            padding-left: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 7px;
            height: 100%;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-dot {
            position: absolute;
            left: -16px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .timeline-content {
            padding-left: 20px;
            border-left: 1px solid #dee2e6;
            margin-left: 10px;
        }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => $customer->full_name . '\'s Profile'])

    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-sm-center flex-sm-row flex-column my-3">
                <div class="flex-grow-1">
                    <h4 class="fs-xl mb-1">{{ $customer->full_name }}</h4>
                    <p class="text-muted mb-0">Customer profile details and history</p>
                </div>
                <div class="text-end mt-3 mt-sm-0">
                    <a class="btn btn-secondary" href="{{ route('customers.index') }}">
                        <i class="ti ti-arrow-left me-1"></i> Back to Customers
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 col-lg-3">
                    <div class="card">
                        <div class="position-absolute top-0 end-0" style="width: 180px;">
                            <svg fill="none" height="560" style="opacity: 0.075; width: 100%; height: auto;"
                                viewBox="0 0 600 560" width="600" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_948_1464)">
                                    <mask height="1200" id="mask0_948_1464" maskUnits="userSpaceOnUse"
                                        style="mask-type:luminance" width="600" x="0" y="0">
                                        <path d="M0 0L0 1200H600L600 0H0Z" fill="white"></path>
                                    </mask>
                                    <g mask="url(#mask0_948_1464)">
                                        <path d="M537.448 166.697L569.994 170.892L550.644 189.578L537.448 166.697Z"
                                            fill="#FF4C3E"></path>
                                    </g>
                                    <g mask="url(#mask1_948_1464)">
                                        <path
                                            d="M403.998 311.555L372.211 343.342C361.79 353.763 344.894 353.763 334.473 343.342L302.686 311.555C292.265 301.134 292.265 284.238 302.686 273.817L334.473 242.03C344.894 231.609 361.79 231.609 372.211 242.03L403.998 273.817C414.419 284.238 414.419 301.134 403.998 311.555Z"
                                            fill="#089df1"></path>
                                        <path
                                            d="M714.621 64.24L541.575 237.286C525.986 252.875 500.711 252.875 485.122 237.286L312.076 64.24C296.487 48.651 296.487 23.376 312.076 7.787L485.122 -165.259C500.711 -180.848 525.986 -180.848 541.575 -165.259L714.621 7.787C730.21 23.377 730.21 48.651 714.621 64.24Z"
                                            fill="#f9bf59"></path>
                                    </g>
                                </g>
                            </svg>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div
                                        class="avatar-xl rounded bg-soft-primary d-flex align-items-center justify-content-center">
                                        <span class="avatar-title rounded-circle text-uppercase fs-24">
                                            {{ $customer->initials }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-1">{{ $customer->full_name }}</h5>
                                    <p class="text-muted mb-0 fs-base">{{ $customer->email }}</p>
                                </div>
                                <div class="ms-auto">
                                    <div class="dropdown">
                                        <a class="text-muted fs-xl" data-bs-toggle="dropdown" href="#">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('edit customers')
                                            <li><a class="dropdown-item" href="{{ route('customers.edit', $customer->slug) }}"><i
                                                        class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            @endcan
                                            @can('delete customers')
                                            <form id="delete-customer-form-{{ $customer->id }}" method="POST" action="{{ route('customers.destroy', $customer->slug) }}" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" onclick="confirmDeleteCustomer({{ $customer->id }}, '{{ $customer->name }}')">
                                                    <i class="ti ti-trash me-2"></i>Delete
                                                </button>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <h6 class="mb-2 mt-4">Information</h6>
                            <ul class="list-unstyled mb-3">
                                <li class="d-flex align-items-center mb-2">
                                    <span class="ti ti-phone fs-lg text-primary me-2"></span>
                                    <span class="text-capitalize">{{ $customer->phone }}</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <span class="ti ti-mail fs-lg text-primary me-2"></span>
                                    <span class="text-capitalize">{{ $customer->email ?? 'N/A' }}</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <span class="ti ti-cake fs-lg text-primary me-2"></span>
                                    <span class="text-capitalize">{{ $customer->date_of_birth ? $customer->date_of_birth->format('M d, Y') : 'N/A' }}</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <span class="ti ti-calendar-plus fs-lg text-primary me-2"></span>
                                    <span>Joined: {{ $customer->joined_date ? $customer->joined_date->format('M d, Y') : $customer->created_at->format('M d, Y') }}</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <span class="ti ti-gender-bigender fs-lg text-primary me-2"></span>
                                    <span class="text-capitalize">{{ $customer->gender ?? 'N/A' }}</span>
                                </li>
                            </ul>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted fs-xs"><i class="ti ti-clock me-1"></i>Joined {{ ($customer->joined_date ?? $customer->created_at)->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Card -->
                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <div class="border-bottom pb-2">
                                    <p class="text-muted mb-0">Total Appointments</p>
                                    <h5 class="mb-0">{{ $customer->totalAppointments() }}</h5>
                                </div>
                                <div class="border-bottom pb-2">
                                    <p class="text-muted mb-0">Total Spent</p>
                                    <h5 class="mb-0">{{ \App\Models\Setting::get('payment.currency_symbol', '$') }}{{ number_format($customer->totalSpent(), 2) }}</h5>
                                </div>
                                <div class="border-bottom pb-2">
                                    <p class="text-muted mb-0">Last Visit</p>
                                    <h5 class="mb-0">{{ $customer->getLastVisitDate() ? $customer->getLastVisitDate()->format('M d, Y') : 'Never' }}</h5>
                                </div>
                                <div>
                                    <p class="text-muted mb-0">Favorite Service</p>
                                    <h5 class="mb-0">{{ $customer->favoriteService() ? $customer->favoriteService()->name : 'N/A' }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-9">
                    <div class="card">
                        <div class="card-body p-0">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#timeline-tab" role="tab">
                                        <i class="ti ti-timeline me-1"></i> Timeline
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#appointments-tab" role="tab">
                                        <i class="ti ti-calendar-event me-1"></i> Appointments
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#bills-tab" role="tab">
                                        <i class="ti ti-receipt me-1"></i> Bills
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#transactions-tab" role="tab">
                                        <i class="ti ti-coin me-1"></i> Transactions
                                    </a>
                                </li>
                            </ul>

                            <!-- Tab content -->
                            <div class="tab-content p-4">
                                <!-- Timeline Tab -->
                                <div class="tab-pane active" id="timeline-tab" role="tabpanel">
                                    <h5 class="mb-3">Activity Timeline</h5>
                                    <div class="timeline">
                                        @forelse($customer->getAllHistory(10) as $history)
                                            <div class="timeline-item">
                                                <div class="timeline-dot bg-{{ $history['type'] === 'appointment' ? 'primary' : 'success' }}"></div>
                                                <div class="timeline-content">
                                                    <div class="d-flex">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1">
                                                                @if($history['type'] === 'appointment')
                                                                    <i class="ti ti-calendar-event text-primary me-1"></i>
                                                                    Appointment
                                                                @else
                                                                    <i class="ti ti-receipt text-success me-1"></i>
                                                                    POS Sale
                                                                @endif
                                                            </h6>
                                                            <p class="text-muted mb-1">
                                                                {{ $history['service'] }}
                                                                @if(isset($history['staff']) && $history['staff'])
                                                                    <br>Staff: {{ $history['staff'] }}
                                                                @endif
                                                            </p>
                                                            <p class="mb-0">
                                                                <i class="ti ti-calendar me-1"></i>
                                                                {{ \Carbon\Carbon::parse($history['date'])->format('M d, Y g:i A') }}
                                                                |
                                                                <i class="ti ti-currency-dollar me-1"></i>
                                                                {{ \App\Models\Setting::get('payment.currency_symbol', '$') }}{{ number_format($history['amount'], 2) }}
                                                                |
                                                                <span class="badge bg-{{ $history['status'] === 'completed' ? 'success' : ($history['status'] === 'cancelled' ? 'danger' : 'warning') }}-subtle text-{{ $history['status'] === 'completed' ? 'success' : ($history['status'] === 'cancelled' ? 'danger' : 'warning') }} badge-label">
                                                                    {{ ucfirst($history['status']) }}
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-5">
                                                <div class="avatar-lg mx-auto mb-4">
                                                    <div class="avatar-title bg-light rounded-circle text-primary">
                                                        <i class="ti ti-timeline fs-24"></i>
                                                    </div>
                                                </div>
                                                <h5 class="mb-2">No Activity History</h5>
                                                <p class="mb-0 text-muted">No activity history found for this customer</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Appointments Tab -->
                                <div class="tab-pane" id="appointments-tab" role="tabpanel">
                                    <h5 class="mb-3">Appointment History</h5>
                                    <!-- Recent appointments table -->
                                    @if($customer->appointments->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-centered table-hover table-nowrap mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Service</th>
                                                    <th>Staff</th>
                                                    <th>Status</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customer->appointments->sortByDesc('appointment_date') as $appointment)
                                                <tr>
                                                    <td>{{ $appointment->appointment_date->format('M d, Y g:i A') }}</td>
                                                    <td>{{ $appointment->service->name ?? 'N/A' }}</td>
                                                    <td>{{ $appointment->user->name ?? 'N/A' }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : ($appointment->status === 'cancelled' ? 'danger' : 'warning') }}-subtle text-{{ $appointment->status === 'completed' ? 'success' : ($appointment->status === 'cancelled' ? 'danger' : 'warning') }}">
                                                            {{ ucfirst($appointment->status) }}
                                                        </span>
                                                    </td>
                                                    <td>${{ number_format($appointment->service->price ?? 0, 2) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @else
                                    <p class="text-muted text-center py-4 mb-0">No appointments found for this customer</p>
                                    @endif
                                </div>

                                <!-- Bills Tab -->
                                <div class="tab-pane" id="bills-tab" role="tabpanel">
                                    <h5 class="mb-3">Bill History</h5>
                                    <!-- Sales/Bills table -->
                                    @if($sales->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-centered table-hover table-nowrap mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Sale #</th>
                                                    <th>Items</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sales as $sale)
                                                <tr>
                                                    <td>{{ $sale->sale_date->format('M d, Y g:i A') }}</td>
                                                    <td>
                                                        <a href="{{ route('pos.receipt', $sale) }}" class="fw-medium">
                                                            {{ $sale->sale_number }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        {{ $sale->items->count() }} item(s)
                                                        <div class="text-muted fs-xs">
                                                            @foreach($sale->items->take(2) as $item)
                                                                {{ $item->item_name }}<br>
                                                            @endforeach
                                                            @if($sale->items->count() > 2)
                                                                +{{ $sale->items->count() - 2 }} more
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>{{ \App\Models\Setting::get('payment.currency_symbol', '$') }}{{ number_format($sale->total_amount, 2) }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $sale->status === 'completed' ? 'success' : ($sale->status === 'cancelled' ? 'danger' : 'warning') }}-subtle text-{{ $sale->status === 'completed' ? 'success' : ($sale->status === 'cancelled' ? 'danger' : 'warning') }} badge-label">
                                                            {{ ucfirst($sale->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @else
                                    <p class="text-muted text-center py-4 mb-0">No bills/sales found for this customer</p>
                                    @endif
                                </div>

                                <!-- Transactions Tab -->
                                <div class="tab-pane" id="transactions-tab" role="tabpanel">
                                    <h5 class="mb-3">Transaction History</h5>
                                    @if($transactions->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-centered table-hover table-nowrap mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($transactions as $transaction)
                                                <tr>
                                                    <td>{{ $transaction->created_at->format('M d, Y g:i A') }}</td>
                                                    <td>{{ ucfirst($transaction->transaction_type) }}</td>
                                                    <td>{{ \App\Models\Setting::get('payment.currency_symbol', '$') }}{{ number_format($transaction->amount, 2) }}</td>
                                                    <td>{{ ucfirst($transaction->payment_method) }}</td>
                                                    <td>
                                                        <span class="badge bg-success-subtle text-success badge-label">
                                                            {{ ucfirst($transaction->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @else
                                    <p class="text-muted text-center py-4 mb-0">No transactions found for this customer</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end row-->
        </div> <!-- end col-->
    </div> <!-- end row-->
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

        // Function to confirm customer deletion
        function confirmDeleteCustomer(customerId, customerName) {
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
                    // Submit the form for the specified customer
                    document.getElementById('delete-customer-form-' + customerId).submit();
                }
            });
        }
    </script>
@endsection