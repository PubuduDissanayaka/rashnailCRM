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
                                <option value="10">10</option><option value="25" selected>25</option><option value="50">50</option><option value="100">100</option>
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
                @canany(['delete coupons', 'edit coupons'])
                <div id="bulk-bar" class="px-3 py-2 border-top border-bottom bg-primary bg-opacity-10 d-none align-items-center gap-2 flex-wrap">
                    <span class="fw-semibold me-2"><span id="bulk-count">0</span> selected</span>
                    <button type="button" class="btn btn-sm btn-success" data-bulk="activate"><i class="ti ti-circle-check me-1"></i>Activate</button>
                    <button type="button" class="btn btn-sm btn-warning" data-bulk="deactivate"><i class="ti ti-circle-minus me-1"></i>Deactivate</button>
                    <button type="button" class="btn btn-sm btn-primary" data-bulk="issue"><i class="ti ti-check me-1"></i>Mark issued</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bulk="unissue"><i class="ti ti-arrow-back-up me-1"></i>Mark not issued</button>
                    <button type="button" class="btn btn-sm btn-danger" data-bulk="delete"><i class="ti ti-trash me-1"></i>Delete permanently</button>
                    <button type="button" class="btn btn-sm btn-light ms-auto" id="bulk-clear">Clear selection</button>
                </div>
                @endcanany

                <div class="table-responsive">
                    <table class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width:1%">
                                    <input class="form-check-input" type="checkbox" id="select-all" title="Select all coupons in this list" aria-label="Select all">
                                </th>
                                <th data-table-sort="sort-code">Code</th>
                                <th data-table-sort="sort-name">Name</th>
                                <th data-table-sort="sort-type">Type</th>
                                <th data-table-sort="sort-discount">Discount</th>
                                <th data-table-sort="sort-usage">Usage / Balance</th>
                                <th data-table-sort="sort-issued">Issued</th>
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
                                <td class="text-center">
                                    <input class="form-check-input js-row-check" type="checkbox" value="{{ $coupon->id }}"
                                           data-code="{{ $coupon->code }}" data-used="{{ $used }}" aria-label="Select {{ $coupon->code }}">
                                </td>
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
                                <td>
                                    @if($coupon->isValueBased())
                                        @php
                                            $usedPct = $coupon->valueUsedPercent();
                                            $barClass = $usedPct >= 99 ? 'danger' : ($usedPct >= 70 ? 'warning' : 'success');
                                            $remaining = $coupon->remainingValue();
                                        @endphp
                                        <div class="coupon-balance" style="min-width:150px">
                                            <div class="d-flex justify-content-between align-items-baseline">
                                                <span class="fw-semibold {{ $remaining <= 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ $currencySymbol }}{{ number_format($remaining, 2) }}
                                                    <span class="fs-xxs text-muted fw-normal">left</span>
                                                </span>
                                                <span class="fs-xxs text-muted">{{ number_format($usedPct, 0) }}%</span>
                                            </div>
                                            <div class="progress mt-1" style="height:6px" role="progressbar"
                                                 aria-valuenow="{{ (int) $usedPct }}" aria-valuemin="0" aria-valuemax="100"
                                                 aria-label="{{ $coupon->code }} balance used">
                                                <div class="progress-bar bg-{{ $barClass }}"
                                                     style="width: {{ $usedPct <= 0 ? 0 : max(3, $usedPct) }}%"></div>
                                            </div>
                                            <div class="fs-xxs text-muted mt-1">
                                                {{ $currencySymbol }}{{ number_format((float) $coupon->consumed_value, 2) }} of
                                                {{ $currencySymbol }}{{ number_format($coupon->valueLimit(), 2) }} used
                                            </div>
                                        </div>
                                    @else
                                        {{ $used }}/{{ $limit ?: '∞' }}
                                    @endif
                                </td>
                                <td>
                                    {{-- Cards are printed and handed over: this switch records that --}}
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input js-issue-toggle" type="checkbox" role="switch"
                                                   id="issue-{{ $coupon->id }}"
                                                   data-id="{{ $coupon->id }}"
                                                   data-code="{{ $coupon->code }}"
                                                   data-url="{{ route('coupons.issue', $coupon) }}"
                                                   aria-label="Mark {{ $coupon->code }} as issued"
                                                   title="{{ $coupon->isIssued() ? 'Issued — switch off to mark as not issued' : 'Not issued — switch on when the card is handed over' }}"
                                                   {{ $coupon->isIssued() ? 'checked' : '' }}
                                                   @cannot('edit coupons') disabled @endcannot>
                                        </div>
                                        <div class="js-issue-info">
                                            @if($coupon->isIssued())
                                                <span class="fs-xs text-muted d-block">Issued {{ $coupon->issued_at->format('M d, Y') }}</span>
                                                @if($coupon->end_date)
                                                    <span class="fs-xxs {{ $coupon->isExpired() ? 'text-danger' : 'text-muted' }}">
                                                        {{ $coupon->isExpired() ? 'Expired' : 'Valid to' }} {{ $coupon->end_date->format('M d, Y') }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="fs-xs text-muted d-block">Not issued</span>
                                                <span class="fs-xxs text-muted">card not handed over</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="js-status-cell">
                                    @php $statusLabel = $coupon->statusLabel(); @endphp
                                    @switch($statusLabel)
                                        @case('not_issued')
                                            <span class="badge bg-secondary-subtle text-secondary">Not issued</span>
                                            @break
                                        @case('expired')
                                            <span class="badge bg-danger-subtle text-danger">Expired</span>
                                            @break
                                        @case('used_up')
                                            <span class="badge bg-dark-subtle text-dark">Used up</span>
                                            @break
                                        @case('inactive')
                                            <span class="badge bg-warning-subtle text-warning">Inactive</span>
                                            @break
                                        @default
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                    @endswitch
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
                            <tr><td colspan="9" class="text-center py-5 text-muted">No coupons found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0 d-flex justify-content-between align-items-center">
                    <span class="fs-xs text-muted">Showing all {{ $coupons->count() }} coupon(s)</span>
                </div>
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

            // ---------- single delete (permanent) ----------
            document.querySelectorAll('.js-delete-coupon').forEach(function (form) {
                const btn = form.querySelector('.js-delete-btn');
                if (!btn) return;

                btn.addEventListener('click', function () {
                    const code = form.dataset.code || 'this coupon';
                    const used = parseInt(form.dataset.used || '0', 10);

                    // Redeemed coupons cannot be deleted: coupon_redemptions cascades,
                    // so a hard delete would erase the discount history of past sales.
                    if (used > 0) {
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'info',
                                title: code + ' has already been used',
                                text: 'It was redeemed ' + used + ' time(s), so it cannot be deleted without losing sale history. Deactivate it instead to stop further use.',
                                confirmButtonText: 'Got it'
                            });
                        } else {
                            alert(code + ' has already been used ' + used + ' time(s) and cannot be deleted. Deactivate it instead.');
                        }
                        return;
                    }

                    const confirmText = 'This is permanent: the coupon is removed from the database and the code can be issued again. It cannot be undone.';

                    if (!window.Swal) {
                        if (confirm('Permanently delete coupon ' + code + '?' + String.fromCharCode(10, 10) + confirmText)) form.submit();
                        return;
                    }

                    Swal.fire({
                        title: 'Permanently delete ' + code + '?',
                        text: confirmText,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete permanently',
                        cancelButtonText: 'Cancel',
                        buttonsStyling: false,
                        customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-secondary' }
                    }).then(function (result) {
                        if (result.isConfirmed) form.submit();
                    });
                });
            });

            // ---------- issue toggle (printed cards) ----------
            function issueToast(message, icon) {
                if (window.Swal) {
                    Swal.fire({ toast: true, position: 'top-end', icon: icon, title: message, showConfirmButton: false, timer: icon === 'error' ? 6000 : 3000, timerProgressBar: true });
                }
            }

            document.querySelectorAll('.js-issue-toggle').forEach(function (input) {
                input.addEventListener('change', function () {
                    const issuing = input.checked;
                    const url = input.dataset.url;
                    input.disabled = true;

                    fetch(url, {
                        method: issuing ? 'POST' : 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(function (res) {
                        return res.json().catch(function () { return null; }).then(function (data) {
                            return { ok: res.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.data || !result.data.ok) {
                            input.checked = !issuing;
                            issueToast((result.data && result.data.message) || 'We could not update this coupon. Please try again.', 'error');
                            return;
                        }

                        const coupon = result.data.coupon || {};
                        const info = input.closest('td').querySelector('.js-issue-info');
                        const cell = input.closest('tr').querySelector('.js-status-cell');

                        if (info) {
                            if (coupon.issued) {
                                info.innerHTML = '<span class="fs-xs text-muted d-block">Issued ' + (coupon.issued_at || '') + '</span>' +
                                    (coupon.end_date ? '<span class="fs-xxs text-muted">Valid to ' + coupon.end_date + '</span>' : '');
                            } else {
                                info.innerHTML = '<span class="fs-xs text-muted d-block">Not issued</span>' +
                                    '<span class="fs-xxs text-muted">card not handed over</span>';
                            }
                        }

                        if (cell) {
                            const badges = {
                                not_issued: ['bg-secondary-subtle text-secondary', 'Not issued'],
                                expired: ['bg-danger-subtle text-danger', 'Expired'],
                                used_up: ['bg-dark-subtle text-dark', 'Used up'],
                                inactive: ['bg-warning-subtle text-warning', 'Inactive'],
                                active: ['bg-success-subtle text-success', 'Active']
                            };
                            const badge = badges[coupon.status] || badges.active;
                            cell.innerHTML = '<span class="badge ' + badge[0] + '">' + badge[1] + '</span>';
                        }

                        input.title = coupon.issued
                            ? 'Issued — switch off to mark as not issued'
                            : 'Not issued — switch on when the card is handed over';

                        issueToast(result.data.message, 'success');
                    })
                    .catch(function () {
                        input.checked = !issuing;
                        issueToast('We could not reach the server. Please try again.', 'error');
                    })
                    .then(function () {
                        input.disabled = false;
                    });
                });
            });

            // ---------- bulk selection ----------
            const bulkBar = document.getElementById('bulk-bar');
            const selectAll = document.getElementById('select-all');
            const countEl = document.getElementById('bulk-count');
            const allChecks = () => Array.from(document.querySelectorAll('.js-row-check'));

            // Rows hidden by the search filter or pagination should not be selected
            // by "select all" — only what the user can actually see.
            const visibleChecks = () => allChecks().filter(function (cb) {
                const tr = cb.closest('tr');
                return tr && tr.offsetParent !== null;
            });

            const selectedChecks = () => allChecks().filter(function (cb) { return cb.checked; });

            function syncSelection() {
                const chosen = selectedChecks();

                if (countEl) countEl.textContent = chosen.length;
                if (bulkBar) {
                    bulkBar.classList.toggle('d-none', chosen.length === 0);
                    bulkBar.classList.toggle('d-flex', chosen.length > 0);
                }

                if (selectAll) {
                    const visible = visibleChecks();
                    const checkedVisible = visible.filter(function (cb) { return cb.checked; });
                    selectAll.checked = visible.length > 0 && checkedVisible.length === visible.length;
                    selectAll.indeterminate = !selectAll.checked && checkedVisible.length > 0;
                }
            }

            allChecks().forEach(function (cb) { cb.addEventListener('change', syncSelection); });

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    visibleChecks().forEach(function (cb) { cb.checked = selectAll.checked; });
                    syncSelection();
                });
            }

            document.getElementById('bulk-clear')?.addEventListener('click', function () {
                allChecks().forEach(function (cb) { cb.checked = false; });
                syncSelection();
            });

            function submitBulk(action, ids) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = @json(route('coupons.bulk.action'));
                form.style.display = 'none';

                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = @json(csrf_token());
                form.appendChild(token);

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = action;
                form.appendChild(actionInput);

                ids.forEach(function (id) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }

            document.querySelectorAll('[data-bulk]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const action = btn.dataset.bulk;
                    const chosen = selectedChecks();
                    if (!chosen.length) return;

                    const ids = chosen.map(function (cb) { return cb.value; });
                    const used = chosen.filter(function (cb) { return parseInt(cb.dataset.used || '0', 10) > 0; });

                    if (action !== 'delete') {
                        submitBulk(action, ids);
                        return;
                    }

                    let text = 'This is permanent: ' + ids.length + ' coupon(s) will be removed from the database and their codes can be issued again. It cannot be undone.';
                    if (used.length) {
                        text += String.fromCharCode(10, 10) + used.length + ' of them have already been redeemed and will be kept so sale history stays correct.';
                    }

                    if (!window.Swal) {
                        if (confirm('Permanently delete ' + ids.length + ' coupon(s)?')) submitBulk('delete', ids);
                        return;
                    }

                    Swal.fire({
                        title: 'Permanently delete ' + ids.length + ' coupon(s)?',
                        text: text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete permanently',
                        cancelButtonText: 'Cancel',
                        buttonsStyling: false,
                        customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-secondary' }
                    }).then(function (result) {
                        if (result.isConfirmed) submitBulk('delete', ids);
                    });
                });
            });

            syncSelection();
        });
    </script>
@endsection
