@extends('layouts.vertical', ['title' => 'Bulk Coupon Generation'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
    <style>
        .pattern-chip {
            cursor: pointer;
            transition: all .15s;
            font-size: .8rem;
        }
        .pattern-chip:hover { transform: translateY(-1px); }
        .step-badge {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .8rem;
            flex-shrink: 0;
        }
        .preview-code {
            font-family: monospace;
            font-size: 1rem;
            letter-spacing: .06em;
            background: #f0f4ff;
            border: 1px solid #c9d8ff;
            border-radius: 6px;
            padding: 6px 14px;
            display: inline-block;
        }
        .field-icon { color: #6c757d; }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', [
        'title'    => 'Bulk Coupon Generation',
        'subtitle' => 'Create multiple unique coupon codes at once'
    ])

    <div class="row justify-content-center">
        <div class="col-xl-9">

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="ti ti-alert-circle me-2"></i>
                    <strong>Please fix the following:</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('coupons.bulk.generate') }}" id="bulk-form">
                @csrf

                {{-- ── STEP 1: Batch Info ───────────────────────────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="step-badge bg-primary text-white">1</span>
                            <h5 class="mb-0">Batch Details</h5>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Batch Name <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="name"
                                       value="{{ old('name') }}"
                                       placeholder="e.g. Summer Sale 2026"
                                       required>
                                <div class="form-text">A label for this group of coupons.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Number of Coupons <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="count"
                                           value="{{ old('count', 10) }}" min="1" max="500" required>
                                    <span class="input-group-text text-muted">max 500</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description <span class="text-muted">(optional)</span></label>
                                <textarea class="form-control" name="description" rows="2"
                                          placeholder="Internal note about this batch...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── STEP 2: Code Pattern ─────────────────────────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="step-badge bg-primary text-white">2</span>
                            <h5 class="mb-0">Code Pattern</h5>
                        </div>

                        <label class="form-label">Pattern <span class="text-danger">*</span></label>
                        <input class="form-control form-control-lg font-monospace" type="text" id="pattern" name="pattern"
                               value="{{ old('pattern', 'SALE-{RANDOM6}') }}" required
                               oninput="updatePreview()">

                        <div class="mt-2 mb-3">
                            <small class="text-muted me-1">Quick pick:</small>
                            @foreach([
                                'SALE-{RANDOM6}'        => 'SALE-XXXXXX',
                                'PROMO-{RANDOM8}'       => 'PROMO-XXXXXXXX',
                                'WELCOME-{SEQUENTIAL4}' => 'WELCOME-0001…',
                                '{DATE-YMD}-{RANDOM4}'  => 'DATE-XXXX',
                                '{RANDOM10}'            => 'Random 10',
                            ] as $p => $label)
                                <button type="button" class="btn btn-sm btn-outline-secondary pattern-chip me-1 mb-1"
                                        onclick="setPattern('{{ $p }}')">{{ $label }}</button>
                            @endforeach
                        </div>

                        <div class="bg-light rounded p-3 d-flex align-items-center gap-3">
                            <div>
                                <div class="text-muted small mb-1">Preview</div>
                                <span class="preview-code" id="code-preview">SALE-A3B9C7</span>
                            </div>
                            <div class="vr"></div>
                            <div class="small text-muted">
                                <code>{RANDOM<em>N</em>}</code> — N random chars &nbsp;|&nbsp;
                                <code>{SEQUENTIAL<em>N</em>}</code> — 0001, 0002… &nbsp;|&nbsp;
                                <code>{DATE-YMD}</code> — 20260308
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── STEP 3: Coupon Settings ──────────────────────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="step-badge bg-primary text-white">3</span>
                            <h5 class="mb-0">Discount Settings</h5>
                        </div>

                        <div class="row g-3">
                            {{-- Type --}}
                            <div class="col-md-4">
                                <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="coupon_type" id="coupon_type" onchange="toggleDiscountLabel()">
                                    <option value="fixed"      {{ old('coupon_type','fixed') === 'fixed'      ? 'selected' : '' }}>Fixed Amount</option>
                                    <option value="percentage" {{ old('coupon_type')         === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                </select>
                            </div>

                            {{-- Discount Value --}}
                            <div class="col-md-4">
                                <label class="form-label" id="discount-label">Discount Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="discount-prefix">{{ $currencySymbol }}</span>
                                    <input type="number" class="form-control" name="discount_value"
                                           value="{{ old('discount_value', 10) }}"
                                           min="0" step="0.01" required>
                                </div>
                            </div>

                            {{-- Min Purchase --}}
                            <div class="col-md-4">
                                <label class="form-label">Min. Purchase <span class="text-muted">(optional)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $currencySymbol }}</span>
                                    <input type="number" class="form-control" name="min_purchase"
                                           value="{{ old('min_purchase', 0) }}"
                                           min="0" step="0.01" placeholder="0 = no minimum">
                                </div>
                            </div>

                            {{-- Validity --}}
                            <div class="col-md-4">
                                <label class="form-label">Valid For <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="valid_days"
                                           value="{{ old('valid_days', 30) }}" min="1" max="3650" required>
                                    <span class="input-group-text">days from today</span>
                                </div>
                            </div>

                            {{-- Usage Limit --}}
                            <div class="col-md-4">
                                <label class="form-label">Uses Per Coupon <span class="text-muted">(optional)</span></label>
                                <input type="number" class="form-control" name="usage_limit"
                                       value="{{ old('usage_limit') }}"
                                       min="1" placeholder="Unlimited">
                                <div class="form-text">Leave blank for unlimited uses.</div>
                            </div>

                            {{-- Per Customer --}}
                            <div class="col-md-4">
                                <label class="form-label">Uses Per Customer <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="per_customer"
                                       value="{{ old('per_customer', 1) }}" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Actions ──────────────────────────────────────────────────────── --}}
                <div class="d-flex gap-2 justify-content-end mb-5">
                    <a href="{{ route('coupons.batches.index') }}" class="btn btn-light px-4">Cancel</a>
                    <button class="btn btn-primary px-5" type="submit" id="submit-btn">
                        <i class="ti ti-copy me-1"></i> Generate Coupons
                    </button>
                </div>
            </form>

        </div>
    </div>
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        const sym = @json($currencySymbol);

        function setPattern(p) {
            document.getElementById('pattern').value = p;
            updatePreview();
        }

        function updatePreview() {
            const pattern = document.getElementById('pattern').value || '';
            let preview = pattern
                .replace(/\{RANDOM(\d+)\}/g, (_, n) => randomStr(parseInt(n)))
                .replace(/\{SEQUENTIAL(\d+)\}/g, (_, n) => '1'.padStart(parseInt(n), '0'))
                .replace(/\{DATE-YMD\}/g, () => {
                    const d = new Date();
                    return d.getFullYear() + String(d.getMonth()+1).padStart(2,'0') + String(d.getDate()).padStart(2,'0');
                });
            document.getElementById('code-preview').textContent = preview || '—';
        }

        function randomStr(n) {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let s = '';
            for (let i = 0; i < n; i++) s += chars[Math.floor(Math.random() * chars.length)];
            return s;
        }

        function toggleDiscountLabel() {
            const type = document.getElementById('coupon_type').value;
            document.getElementById('discount-label').innerHTML =
                (type === 'percentage' ? 'Discount %' : 'Discount Amount') +
                ' <span class="text-danger">*</span>';
            document.getElementById('discount-prefix').textContent =
                type === 'percentage' ? '%' : sym;
        }

        document.getElementById('bulk-form').addEventListener('submit', function() {
            const btn = document.getElementById('submit-btn');
            const count = parseInt(document.querySelector('[name=count]').value) || 0;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating ' + count + ' coupons…';
        });

        // Init
        updatePreview();
        toggleDiscountLabel();

        @if(session('success'))
            Swal.fire({ title: 'Done!', text: @json(session('success')), icon: 'success', confirmButtonColor: '#3b76e1' });
        @endif
        @if(session('error'))
            Swal.fire({ title: 'Error', text: @json(session('error')), icon: 'error', confirmButtonColor: '#3b76e1' });
        @endif
    </script>
@endsection
