@extends('layouts.vertical', ['title' => 'Coupon Management'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css'])
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function confirmDelete(couponId, couponCode, couponSlug) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete the coupon "${couponCode}"?`,
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
                    document.getElementById(`delete-form-${couponSlug}`).submit();
                }
            });
        }
    </script>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Coupon Management'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Coupons</h4>
                        <p class="text-muted mb-0">Manage discount coupons and vouchers</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search coupons..."
                                type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        @can('create coupons')
                        <a href="{{ route('coupons.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Create Coupon
                        </a>
                        <a href="{{ route('coupons.bulk.create') }}" class="btn btn-outline-primary">
                            <i class="ti ti-copy me-1"></i> Bulk Generate
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Coupons</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['active'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Active Coupons</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['expired'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Expired Coupons</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total_redemptions'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Redemptions</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">ID</th>
                                <th data-table-sort="sort-code">Coupon Code</th>
                                <th data-table-sort="sort-name">Name</th>
                                <th data-table-sort="sort-type">Type</th>
                                <th data-table-sort="sort-discount">Discount</th>
                                <th data-table-sort="sort-usage">Usage</th>
                                <th data-table-sort="sort-validity">Validity</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coupons as $coupon)
                            <tr>
                                <td class="ps-3">{{ $coupon->id }}</td>
                                <td data-sort="sort-code">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-circle bg-soft-primary me-2">
                                            <span class="avatar-title rounded-circle text-uppercase">
                                                {{ substr($coupon->code, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="fs-base mb-0">{{ $coupon->code }}</h5>
                                            <p class="text-muted mb-0 fs-xs">{{ $coupon->description ? Str::limit($coupon->description, 30) : 'No description' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td data-sort="sort-name">{{ $coupon->name }}</td>
                                <td data-sort="sort-type">
                                    <span class="badge bg-info-subtle text-info">
                                        {{ ucfirst(str_replace('_', ' ', $coupon->type)) }}
                                    </span>
                                </td>
                                <td data-sort="sort-discount">
                                    @if($coupon->type === 'percentage')
                                        {{ $coupon->discount_percentage }}% @if($coupon->max_discount_amount) (max {{ $currencySymbol }}{{ number_format($coupon->max_discount_amount, 2) }}) @endif
                                    @elseif($coupon->type === 'fixed')
                                        {{ $currencySymbol }}{{ number_format($coupon->discount_value, 2) }}
                                    @elseif($coupon->type === 'bogo')
                                        Buy 1 Get 1
                                    @elseif($coupon->type === 'free_shipping')
                                        Free Shipping
                                    @elseif($coupon->type === 'tiered')
                                        Tiered Discount
                                    @endif
                                </td>
                                <td data-sort="sort-usage">
                                    {{ $coupon->usage_count ?? 0 }} / {{ $coupon->usage_limit ?: '∞' }}
                                </td>
                                <td data-sort="sort-validity">
                                    @if($coupon->valid_from && $coupon->valid_until)
                                        {{ $coupon->valid_from->format('M d, Y') }} - {{ $coupon->valid_until->format('M d, Y') }}
                                    @elseif($coupon->valid_from)
                                        From {{ $coupon->valid_from->format('M d, Y') }}
                                    @else
                                        No expiry
                                    @endif
                                </td>
                                <td data-sort="sort-status">
                                    @php
                                        $statusClass = $coupon->isActive() ? 'success' : ($coupon->isExpired() ? 'danger' : 'secondary');
                                        $statusText = $coupon->isActive() ? 'Active' : ($coupon->isExpired() ? 'Expired' : 'Inactive');
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                        <i class="ti ti-circle-filled fs-xs"></i> {{ $statusText }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('coupons.show', $coupon) }}" title="View Details">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @can('edit coupons')
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('coupons.edit', $coupon) }}" title="Edit Coupon">
                                            <i class="ti ti-edit fs-lg"></i>
                                        </a>
                                        <form id="delete-form-{{ $coupon->id }}" action="{{ route('coupons.destroy', $coupon) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                                                onclick="confirmDelete('{{ $coupon->id }}', '{{ addslashes($coupon->code) }}', '{{ $coupon->id }}')"
                                                title="Delete Coupon">
                                            <i class="ti ti-trash fs-lg"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-ticket fs-24 mb-2 d-block"></i>
                                        No coupons found. <a href="{{ route('coupons.create') }}">Create the first coupon</a>.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="coupons"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection