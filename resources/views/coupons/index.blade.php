@extends('layouts.vertical', ['title' => 'Coupons'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Coupons'])

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
                                <td>{{ $coupon->name }}</td>
                                <td><span class="badge bg-info-subtle text-info">{{ ucfirst($coupon->type) }}</span></td>
                                <td>{{ $coupon->type === 'percentage' ? $coupon->discount_value.'%' : '$'.number_format($coupon->discount_value, 2) }}</td>
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
                                        <form action="{{ route('coupons.destroy', $coupon) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this coupon?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-danger btn-icon btn-sm rounded-circle" title="Delete"><i class="ti ti-trash fs-lg"></i></button>
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
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
@endsection
