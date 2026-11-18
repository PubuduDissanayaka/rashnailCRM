@extends('layouts.vertical', ['title' => 'Bulk Generate Coupons'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Bulk Generate Coupons', 'subtitle' => 'Coupons'])

    @php
        // Present in every normal request via ShareErrorsFromSession; guarded so the
        // view can also be rendered outside a request (tests, exports).
        if (! isset($errors)) {
            $errors = new Illuminate\Support\ViewErrorBag();
        }
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
            <i class="ti ti-alert-triangle fs-lg mt-1"></i>
            <div>
                <p class="fw-semibold mb-1">These coupons were not generated. Please check the following:</p>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @php $currencySymbol = \App\Models\Setting::get('payment.currency_symbol', 'Rs'); @endphp

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('coupons.bulk.generate') }}" id="bulk-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Batch Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required maxlength="255" value="{{ old('name') }}" placeholder="e.g. Summer Sale 2026">
                                @error('name')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label">Code Pattern <span class="text-danger">*</span></label>
                                <input type="text" name="pattern" id="pattern" class="form-control text-uppercase" required maxlength="100"
                                       value="{{ old('pattern', 'COUPON-{RANDOM:6}') }}" placeholder="e.g. SUMMER-{RANDOM:6}">
                                <div class="form-text">
                                    <code>{RANDOM:4}</code> random letters <strong>and numbers</strong> (e.g. <code>N9JH</code>) &middot;
                                    <code>{NUMBER:4}</code> numbers only (e.g. <code>4821</code>) &middot;
                                    <code>{SEQ:4}</code> running number &middot;
                                    <code>{DATE}</code> today &middot; <code>{TIME}</code> timestamp.
                                    A pattern without a placeholder can only produce one code.
                                </div>
                                <div class="mt-1 fs-xs text-muted">Example codes: <span id="pattern-preview">—</span></div>
                                @error('pattern')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label" for="seq_start">Start number for {SEQ}</label>
                                <input type="number" name="seq_start" id="seq_start" class="form-control" min="0" max="999999999" step="1"
                                       value="{{ old('seq_start', 1) }}">
                                <div class="form-text">The first code gets this number, then it counts up (e.g. 501, 502, 503).</div>
                                @error('seq_start')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" maxlength="2000">{{ old('description') }}</textarea>
                            @error('description')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Number of Coupons <span class="text-danger">*</span></label>
                                <input type="number" name="count" class="form-control" value="{{ old('count', 10) }}" min="1" max="500" required>
                                @error('count')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Coupon Type <span class="text-danger">*</span></label>
                                <select name="coupon_type" id="coupon_type" class="form-select" required>
                                    <option value="fixed" {{ old('coupon_type') === 'percentage' ? '' : 'selected' }}>Fixed Amount</option>
                                    <option value="percentage" {{ old('coupon_type') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                </select>
                                @error('coupon_type')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="discount-prefix">{{ $currencySymbol }}</span>
                                    <input type="number" name="discount_value" class="form-control" value="{{ old('discount_value', 10) }}" min="0.01" step="0.01" required>
                                </div>
                                @error('discount_value')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Valid For (Days) <span class="text-danger">*</span></label>
                                <input type="number" name="valid_days" class="form-control" value="{{ old('valid_days', 30) }}" min="1" max="3650" required>
                                @error('valid_days')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Usage Limit (per code)</label>
                                <input type="number" name="usage_limit" class="form-control" value="{{ old('usage_limit', 1) }}" min="1">
                                <div class="form-text">Leave empty for unlimited.</div>
                                @error('usage_limit')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Per Customer <span class="text-danger">*</span></label>
                                <input type="number" name="per_customer" class="form-control" value="{{ old('per_customer', 1) }}" min="1" required>
                                @error('per_customer')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Min Purchase</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $currencySymbol }}</span>
                                    <input type="number" name="min_purchase" class="form-control" value="{{ old('min_purchase', 0) }}" min="0" step="0.01">
                                </div>
                                @error('min_purchase')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="{{ route('coupons.batches') }}" class="btn btn-light me-2">All batches</a>
                            <a href="{{ route('coupons.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="ti ti-copy me-1"></i> Generate Coupons</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pattern = document.getElementById('pattern');
            const preview = document.getElementById('pattern-preview');
            const type = document.getElementById('coupon_type');
            const prefix = document.getElementById('discount-prefix');
            const symbol = @json($currencySymbol);

            const DIGITS = '0123456789';
            const LETTERS = 'BCDFGHJKLMNPQRSTVWXYZ';
            const seqInput = document.getElementById('seq_start');

            // mixed: at least one digit and one letter, like the server does
            function random(length) {
                if (length === 1) return (DIGITS + LETTERS)[Math.floor(Math.random() * (DIGITS.length + LETTERS.length))];
                const chars = [DIGITS[Math.floor(Math.random() * 10)], LETTERS[Math.floor(Math.random() * LETTERS.length)]];
                while (chars.length < length) chars.push((DIGITS + LETTERS)[Math.floor(Math.random() * (DIGITS.length + LETTERS.length))]);
                return chars.sort(() => Math.random() - 0.5).join('');
            }

            function numeric(length) {
                return Array.from({ length }, () => DIGITS[Math.floor(Math.random() * 10)]).join('');
            }

            function sample(text, index) {
                const seqStart = parseInt((seqInput && seqInput.value) || '1', 10);
                return String(text)
                    .replace(/\{(?:NUMBER|NUM|DIGITS):?(\d*)\}/gi, (m, n) => numeric(parseInt(n || '6', 10)))
                    .replace(/\{RANDOM:?(\d*)\}/gi, (m, n) => random(parseInt(n || '6', 10)))
                    .replace(/\{(?:SEQUENTIAL|SEQ):?(\d*)(?::(\d+))?\}/gi, (m, n, start) => {
                        const from = start ? parseInt(start, 10) : (isNaN(seqStart) ? 1 : seqStart);
                        return String(from + index).padStart(parseInt(n || '4', 10), '0');
                    })
                    .replace(/\{DATE-YMD\}|\{DATE\}/gi, new Date().toISOString().slice(0, 10).replace(/-/g, ''))
                    .replace(/\{TIME\}/gi, String(Math.floor(Date.now() / 1000)))
                    .toUpperCase();
            }

            function refreshPreview() {
                if (!pattern || !preview) return;
                const value = pattern.value || '';
                preview.textContent = value ? [0, 1, 2].map((i) => sample(value, i)).join(', ') : '—';
            }

            function refreshDiscountLabel() {
                if (!type || !prefix) return;
                prefix.textContent = type.value === 'percentage' ? '%' : symbol;
            }

            pattern?.addEventListener('input', refreshPreview);
            seqInput?.addEventListener('input', refreshPreview);
            type?.addEventListener('change', refreshDiscountLabel);
            refreshPreview();
            refreshDiscountLabel();
        });
    </script>
@endsection
