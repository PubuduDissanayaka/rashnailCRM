@extends('layouts.vertical', ['title' => 'Coupons'])

@section('css')
    
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Coupons'])

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-circle-check me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-triangle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-3"><div class="card card-animate"><div class="card-body"><p class="fw-medium text-muted mb-0">Total Coupons</p><h3 class="mt-2 ff-secondary fw-semibold">{{ $stats['total'] }}</h3></div></div></div>
        <div class="col-md-3"><div class="card card-animate"><div class="card-body"><p class="fw-medium text-muted mb-0">Active</p><h3 class="mt-2 ff-secondary fw-semibold text-success">{{ $stats['active'] }}</h3></div></div></div>
        <div class="col-md-3"><div class="card card-animate"><div class="card-body"><p class="fw-medium text-muted mb-0">Expired</p><h3 class="mt-2 ff-secondary fw-semibold text-danger">{{ $stats['expired'] }}</h3></div></div></div>
        <div class="col-md-3"><div class="card card-animate"><div class="card-body"><p class="fw-medium text-muted mb-0">Redemptions</p><h3 class="mt-2 ff-secondary fw-semibold text-primary">{{ $stats['total_redemptions'] }}</h3></div></div></div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search coupons..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div>
                            <select class="form-select form-control my-1 my-md-0" data-table-set-rows-per-page="">
                                <option value="5">5</option><option value="10" selected>10</option><option value="15">15</option><option value="20">20</option>
                            </select>
                        </div>
                        @can('create coupons')
                        <a href="{{ route('coupons.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i> Create Coupon</a>
                        @endcan
                        @can('manage coupon batches')
                        <a href="{{ route('coupons.batches') }}" class="btn btn-outline-secondary"><i class="ti ti-stack me-1"></i> Batches</a>
                        <a href="{{ route('coupons.bulk.create') }}" class="btn btn-outline-primary"><i class="ti ti-copy me-1"></i> Bulk Generate</a>
                        @endcan
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th data-table-sort="sort-code">Code</th>
                                <th data-table-sort="sort-name">Name</th>
                                <th data-table-sort="sort-type">Type</th>
                                <th data-table-sort="sort-discount">Discount</th>
                                <th data-table-sort="sort-usage">Usage</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th class="text-center" style="width:1%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coupons as $coupon)
                            @php
                                $isExpired = $coupon->isExpired();
                                $isActive = $coupon->isActive();
                                $used = $coupon->redemptions->count();
                                $limit = $coupon->total_usage_limit;
                            @endphp
                            <tr>
                                <td><code class="fw-semibold">{{ $coupon->code }}</code></td>
                                <td>
                                    {{ $coupon->name }}
                                    @if($coupon->batch)
                                        <div class="fs-xs">
                                            <a href="{{ route('coupons.batches.show', $coupon->batch) }}" class="text-muted text-decoration-none">
                                                <i class="ti ti-stack me-1"></i>{{ $coupon->batch->name }}
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td><span class="badge bg-info-subtle text-info">{{ ucfirst($coupon->type) }}</span></td>
                                <td>{{ $coupon->type === 'percentage' ? $coupon->discount_value.'%' : $currencySymbol.number_format($coupon->discount_value, 2) }}</td>
                                <td>{{ $used }}/{{ $limit ?: '∞' }}</td>
                                <td>
                                    @if($isActive)<span class="badge bg-success-subtle text-success">Active</span>
                                    @elseif($isExpired)<span class="badge bg-danger-subtle text-danger">Expired</span>
                                    @else<span class="badge bg-warning-subtle text-warning">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('coupons.show', $coupon) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="View"><i class="ti ti-eye fs-lg"></i></a>
                                        @can('edit coupons')
                                        <a href="{{ route('coupons.edit', $coupon) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="Edit"><i class="ti ti-edit fs-lg"></i></a>
                                        @endcan
                                        @can('delete coupons')
                                        <form action="{{ route('coupons.destroy', $coupon) }}" method="POST" class="d-inline js-delete-coupon"
                                              data-code="{{ $coupon->code }}" data-used="{{ $used }}">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle js-delete-btn" title="Delete"><i class="ti ti-trash fs-lg"></i></button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">No coupons found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">{{ $coupons->links() }}</div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if(session('success'))
                if (window.Swal) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: @json(session('success')), showConfirmButton: false, timer: 3500, timerProgressBar: true });
            @endif
            @if(session('error'))
                if (window.Swal) Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: @json(session('error')), showConfirmButton: false, timer: 6000, timerProgressBar: true });
            @endif

            document.querySelectorAll('.js-delete-coupon').forEach(function (form) {
                const btn = form.querySelector('.js-delete-btn');
                if (!btn) return;

                btn.addEventListener('click', function () {
                    const code = form.dataset.code || 'this coupon';
                    const used = parseInt(form.dataset.used || '0', 10);

                    // Deleted coupons are soft deleted: past redemptions stay on record and the
                    // unique index keeps the code reserved, so the wording must say exactly that.
                    const confirmText = used > 0
                        ? 'This coupon has been used ' + used + ' time(s). It will be removed from the list, the past redemptions stay on record, and the code stays reserved.'
                        : 'The coupon will be removed from the list. The code stays reserved, so old receipts and reports keep pointing at the right coupon.';

                    if (!window.Swal) {
                        if (confirm('Delete coupon ' + code + '?\n\n' + confirmText)) form.submit();
                        return;
                    }

                    Swal.fire({
                        title: 'Delete coupon ' + code + '?',
                        text: confirmText,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it',
                        cancelButtonText: 'Keep it',
                        buttonsStyling: false,
                        customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-secondary' }
                    }).then(function (result) {
                        if (result.isConfirmed) form.submit();
                    });
                });
            });
        });
    </script>
@endsection
