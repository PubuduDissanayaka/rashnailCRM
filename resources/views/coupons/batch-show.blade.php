@extends('layouts.vertical', ['title' => 'Batch - ' . $batch->name])

@section('content')
    @include('layouts.partials.page-title', ['title' => $batch->name, 'subtitle' => 'Coupon Batches'])

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
        <div class="col-md-3">
            <div class="card card-animate"><div class="card-body">
                <p class="fw-medium text-muted mb-0">Codes Generated</p>
                <h3 class="mt-2 ff-secondary fw-semibold">{{ $batch->generated_count }} <span class="fs-sm text-muted">/ {{ $batch->count }}</span></h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate"><div class="card-body">
                <p class="fw-medium text-muted mb-0">Status</p>
                <h3 class="mt-2 ff-secondary fw-semibold text-capitalize">{{ $batch->status }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate"><div class="card-body">
                <p class="fw-medium text-muted mb-0">Pattern</p>
                <h5 class="mt-2 ff-secondary fw-semibold"><code>{{ $batch->pattern }}</code></h5>
                @if(preg_match('/\{(?:SEQUENTIAL|SEQ):?/i', $batch->pattern))
                    <p class="text-muted fs-xs mb-0">Numbering starts at {{ (int) ($batch->settings['seq_start'] ?? 1) }}</p>
                @endif
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate"><div class="card-body">
                <p class="fw-medium text-muted mb-0">Created</p>
                <h5 class="mt-2 ff-secondary fw-semibold">{{ $batch->created_at?->format('d M Y, H:i') }}</h5>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Coupon codes</h5>
                        @if($batch->description)
                            <p class="text-muted fs-xs mb-0">{{ $batch->description }}</p>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        @can('manage coupon batches')
                            <a href="{{ route('coupons.batches.edit', $batch) }}" class="btn btn-light btn-sm">
                                <i class="ti ti-edit me-1"></i> Edit
                            </a>
                            <a href="{{ route('coupons.bulk.create', ['batch' => $batch->id]) }}" class="btn btn-outline-primary btn-sm">
                                <i class="ti ti-plus me-1"></i> Add codes
                            </a>
                        @endcan
                        @if($batch->generated_count > 0)
                            <form action="{{ route('coupons.batches.issue', $batch) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" title="Hand these cards over and start their validity window">
                                    <i class="ti ti-check me-1"></i> Issue all codes
                                </button>
                            </form>
                            <a href="{{ route('coupons.batches.export', $batch) }}" class="btn btn-primary btn-sm">
                                <i class="ti ti-download me-1"></i> Export CSV
                            </a>
                        @endif
                        @can('manage coupon batches')
                            <form method="POST" action="{{ route('coupons.batches.destroy', $batch) }}" id="delete-batch-form" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm" id="delete-batch-btn">
                                    <i class="ti ti-trash me-1"></i> Delete
                                </button>
                            </form>
                        @endcan
                        <a href="{{ route('coupons.batches') }}" class="btn btn-light btn-sm">
                            <i class="ti ti-arrow-left me-1"></i> All batches
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>Code</th>
                                <th>Name</th>
                                <th>Discount</th>
                                <th>Issued</th>
                                <th>Valid Until</th>
                                <th>Usage</th>
                                <th>Status</th>
                                <th class="text-center" style="width:1%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batch->coupons as $coupon)
                                <tr>
                                    <td><code class="fw-semibold">{{ $coupon->code }}</code></td>
                                    <td>{{ $coupon->name }}</td>
                                    <td>
                                        @if($coupon->type === 'percentage')
                                            {{ rtrim(rtrim(number_format((float) $coupon->discount_value, 2), '0'), '.') }}%
                                        @else
                                            {{ \App\Models\Setting::get('payment.currency_symbol', 'Rs') }}{{ number_format((float) $coupon->discount_value, 2) }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($coupon->isIssued())
                                            <span class="text-success fw-semibold">Issued</span>
                                            <div class="fs-xxs text-muted">{{ $coupon->issued_at->format('d M Y') }}</div>
                                        @else
                                            <span class="text-muted">Not issued</span>
                                        @endif
                                    </td>
                                    <td>{{ $coupon->end_date?->format('d M Y') ?? 'No expiry' }}</td>
                                    <td>{{ $coupon->redemptions_count ?? $coupon->redemptions()->count() }}{{ $coupon->total_usage_limit ? ' / ' . $coupon->total_usage_limit : '' }}</td>
                                    <td>
                                        @if(! $coupon->isIssued())
                                            <span class="badge bg-secondary-subtle text-secondary">Not issued</span>
                                        @elseif($coupon->isExpired())
                                            <span class="badge bg-danger-subtle text-danger">Expired</span>
                                        @elseif($coupon->isActive())
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('coupons.show', $coupon) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="View coupon">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        No codes in this batch yet.
                                        @if($batch->status === 'failed')
                                            Generation failed — check the pattern and create the batch again.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('delete-batch-btn');
            const form = document.getElementById('delete-batch-form');
            if (!btn || !form) return;

            btn.addEventListener('click', function () {
                const text = 'Unused codes in this batch are removed for good and can be issued again. Redeemed coupons are kept. This cannot be undone.';

                if (!window.Swal) {
                    if (confirm('Delete batch ' + @json($batch->name) + '?')) form.submit();
                    return;
                }

                Swal.fire({
                    title: 'Delete batch ' + @json($batch->name) + '?',
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel',
                    buttonsStyling: false,
                    customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-secondary' }
                }).then(function (result) {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    </script>
@endsection
