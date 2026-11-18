@extends('layouts.vertical', ['title' => 'Coupon Batches'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Coupon Batches', 'subtitle' => 'Coupons'])

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

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Generated batches</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('coupons.bulk.create') }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-copy me-1"></i> New Batch
                        </a>
                        <a href="{{ route('coupons.index') }}" class="btn btn-light btn-sm">
                            <i class="ti ti-arrow-left me-1"></i> Coupons
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>Batch</th>
                                <th>Pattern</th>
                                <th>Codes</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-center" style="width:1%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                                <tr>
                                    <td>
                                        <a href="{{ route('coupons.batches.show', $batch) }}" class="fw-semibold text-decoration-none">
                                            {{ $batch->name }}
                                        </a>
                                        @if($batch->description)
                                            <div class="text-muted fs-xs">{{ \Illuminate\Support\Str::limit($batch->description, 60) }}</div>
                                        @endif
                                    </td>
                                    <td><code>{{ $batch->pattern }}</code></td>
                                    <td>
                                        <span class="fw-semibold">{{ $batch->generated_count }}</span>
                                        <span class="text-muted">/ {{ $batch->count }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $badge = match ($batch->status) {
                                                'completed' => 'bg-success-subtle text-success',
                                                'failed' => 'bg-danger-subtle text-danger',
                                                'generating' => 'bg-info-subtle text-info',
                                                default => 'bg-warning-subtle text-warning',
                                            };
                                        @endphp
                                        <span class="badge {{ $badge }}">{{ ucfirst($batch->status) }}</span>
                                    </td>
                                    <td>{{ $batch->created_at?->format('d M Y, H:i') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('coupons.batches.show', $batch) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="View codes">
                                                <i class="ti ti-eye fs-lg"></i>
                                            </a>
                                            <a href="{{ route('coupons.batches.edit', $batch) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="Edit batch">
                                                <i class="ti ti-edit fs-lg"></i>
                                            </a>
                                            @if($batch->generated_count > 0)
                                                <a href="{{ route('coupons.batches.export', $batch) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="Export CSV">
                                                    <i class="ti ti-download fs-lg"></i>
                                                </a>
                                            @endif
                                            <form method="POST" action="{{ route('coupons.batches.destroy', $batch) }}" class="js-delete-batch d-inline"
                                                  data-name="{{ $batch->name }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle js-delete-batch-btn" title="Delete batch">
                                                    <i class="ti ti-trash fs-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        No batches yet.
                                        <a href="{{ route('coupons.bulk.create') }}">Generate your first batch</a>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">{{ $batches->links() }}</div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-delete-batch').forEach(function (form) {
                const btn = form.querySelector('.js-delete-batch-btn');
                if (!btn) return;

                btn.addEventListener('click', function () {
                    const name = form.dataset.name || 'this batch';
                    const text = 'Unused codes in this batch are removed for good and can be issued again. Redeemed coupons are kept. This cannot be undone.';

                    if (!window.Swal) {
                        if (confirm('Delete batch ' + name + '?')) form.submit();
                        return;
                    }

                    Swal.fire({
                        title: 'Delete batch ' + name + '?',
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
        });
    </script>
@endsection
