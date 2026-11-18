@extends('layouts.vertical', ['title' => 'Edit Batch - ' . $batch->name])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Edit Batch', 'subtitle' => 'Coupon Batches'])

    @php
        $s = $batch->settings ?? [];
        $currencySymbol = \App\Models\Setting::get('payment.currency_symbol', 'Rs');
        $startDate = ! empty($s['start_date']) ? \Illuminate\Support\Carbon::parse($s['start_date'])->format('Y-m-d') : '';
        $endDate = ! empty($s['end_date']) ? \Illuminate\Support\Carbon::parse($s['end_date'])->format('Y-m-d') : '';
        $isPercentage = ($s['type'] ?? 'fixed') === 'percentage';
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
            <i class="ti ti-alert-triangle fs-lg mt-1"></i>
            <div>
                <p class="fw-semibold mb-1">Nothing was saved. Please check the following:</p>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Batch details</h5>
                    <a href="{{ route('coupons.batches.show', $batch) }}" class="btn btn-light btn-sm">
                        <i class="ti ti-eye me-1"></i> View {{ $batch->coupons_count }} code(s)
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('coupons.batches.update', $batch) }}" id="batch-form">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <label class="form-label">Batch Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required maxlength="255"
                                       value="{{ old('name', $batch->name) }}">
                                @error('name')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label">Code Pattern <span class="text-danger">*</span></label>
                                <input type="text" name="pattern" class="form-control text-uppercase" required maxlength="100"
                                       value="{{ old('pattern', $batch->pattern) }}">
                                <div class="form-text">Changing the pattern only affects codes you generate from now on.</div>
                                @error('pattern')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" maxlength="2000">{{ old('description', $batch->description) }}</textarea>
                            @error('description')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                        </div>

                        <hr class="my-3">
                        <h6 class="mb-3">Coupon settings</h6>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="coupon_type" id="coupon_type" class="form-select" required>
                                    <option value="fixed" {{ old('coupon_type', $s['type'] ?? 'fixed') === 'percentage' ? '' : 'selected' }}>Fixed Amount</option>
                                    <option value="percentage" {{ old('coupon_type', $s['type'] ?? 'fixed') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Discount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="discount-prefix">{{ $isPercentage ? '%' : $currencySymbol }}</span>
                                    <input type="number" name="discount_value" class="form-control" min="0.01" step="0.01" required
                                           value="{{ old('discount_value', $s['discount_value'] ?? '') }}">
                                </div>
                                @error('discount_value')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Valid From</label>
                                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $startDate) }}">
                                @error('start_date')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Valid Until</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $endDate) }}">
                                <div class="form-text">Empty = no expiry.</div>
                                @error('end_date')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Usage Limit (per code)</label>
                                <input type="number" name="usage_limit" class="form-control" min="1"
                                       value="{{ old('usage_limit', $s['total_usage_limit'] ?? '') }}">
                                <div class="form-text">Empty = unlimited.</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Per Customer <span class="text-danger">*</span></label>
                                <input type="number" name="per_customer" class="form-control" min="1" required
                                       value="{{ old('per_customer', $s['per_customer_limit'] ?? 1) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Min Purchase</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $currencySymbol }}</span>
                                    <input type="number" name="min_purchase" class="form-control" min="0" step="0.01"
                                           value="{{ old('min_purchase', $s['minimum_purchase_amount'] ?? 0) }}">
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Start Number for {SEQ}</label>
                                <input type="number" name="seq_start" class="form-control" min="0" max="999999999"
                                       value="{{ old('seq_start', $s['seq_start'] ?? 1) }}">
                            </div>
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" value="1"
                                   {{ old('active', $s['active'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">Coupons in this batch are active</label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="apply_to_coupons" name="apply_to_coupons" value="1"
                                   {{ old('apply_to_coupons') ? 'checked' : '' }}>
                            <label class="form-check-label" for="apply_to_coupons">
                                Apply these settings to the {{ $batch->coupons_count }} coupon(s) already generated
                            </label>
                            <div class="form-text">Leave unticked to change the batch only — existing coupons keep the discount and dates they were created with.</div>
                        </div>

                        <div class="text-end">
                            <a href="{{ route('coupons.batches') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-danger border-opacity-25">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0 text-danger">Delete this batch</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Unused codes in this batch are removed for good and can be issued again.
                        Coupons that were already redeemed are kept (without a batch) so sale history stays correct.
                    </p>
                    <form method="POST" action="{{ route('coupons.batches.destroy', $batch) }}" id="delete-batch-form">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger w-100" id="delete-batch-btn">
                            <i class="ti ti-trash me-1"></i> Delete Batch
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const type = document.getElementById('coupon_type');
            const prefix = document.getElementById('discount-prefix');
            const symbol = @json($currencySymbol);

            type?.addEventListener('change', function () {
                prefix.textContent = type.value === 'percentage' ? '%' : symbol;
            });

            const btn = document.getElementById('delete-batch-btn');
            const form = document.getElementById('delete-batch-form');

            btn?.addEventListener('click', function () {
                const text = 'The batch and its unused codes are removed for good. This cannot be undone.';

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
