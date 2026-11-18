@extends('layouts.vertical', ['title' => $coupon->name . ' - Coupon Details'])

@section('css')
    
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Coupon Details', 'subtitle' => 'Coupons'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">{{ $coupon->name }}</h4>
                        <p class="text-muted mb-0">Code: <code class="fw-semibold fs-sm">{{ $coupon->code }}</code></p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @can('edit coupons')
                            @if($coupon->isIssued())
                                @unless($coupon->redemptions->count())
                                <form action="{{ route('coupons.unissue', $coupon) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="ti ti-arrow-back-up me-1"></i> Mark not issued
                                    </button>
                                </form>
                                @endunless
                            @else
                                <form action="{{ route('coupons.issue', $coupon) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="ti ti-check me-1"></i> Mark as issued
                                    </button>
                                </form>
                            @endif
                        @endcan
                        <a href="{{ route('coupons.index') }}" class="btn btn-light">
                            <i class="ti ti-arrow-left me-1"></i> Back
                        </a>
                        @can('edit coupons')
                        <a href="{{ route('coupons.edit', $coupon) }}" class="btn btn-primary">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        @endcan
                        @can('delete coupons')
                        <form action="{{ route('coupons.destroy', $coupon) }}" method="POST" class="d-inline" id="delete-form" onsubmit="return confirm('Delete this coupon?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger"><i class="ti ti-trash me-1"></i> Delete</button>
                        </form>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Coupon Type</h6>
                                    @php
                                        $typeLabels = ['percentage' => 'Percentage', 'fixed' => 'Fixed Amount', 'bogo' => 'BOGO', 'free_shipping' => 'Free Shipping', 'tiered' => 'Tiered'];
                                        $typeBadges = ['percentage' => 'info', 'fixed' => 'primary', 'bogo' => 'success', 'free_shipping' => 'warning', 'tiered' => 'dark'];
                                    @endphp
                                    <p class="mb-3"><span class="badge bg-{{ $typeBadges[$coupon->type] ?? 'info' }}-subtle text-{{ $typeBadges[$coupon->type] ?? 'info' }}">{{ $typeLabels[$coupon->type] ?? ucfirst($coupon->type) }}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Status</h6>
                                    <p class="mb-3">
                                        @if($coupon->isActive())
                                            <span class="badge bg-success-subtle text-success"><i class="ti ti-circle-filled fs-xs"></i> Active</span>
                                        @elseif($coupon->isExpired())
                                            <span class="badge bg-danger-subtle text-danger"><i class="ti ti-circle-filled fs-xs"></i> Expired</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary"><i class="ti ti-circle-filled fs-xs"></i> Inactive</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Discount Value</h6>
                                    <p class="mb-3 fw-semibold">
                                        @if($coupon->type === 'percentage')
                                            {{ $coupon->discount_value }}%
                                            @if($coupon->max_discount_amount) <span class="text-muted fw-normal">(max {{ $currencySymbol }}{{ number_format($coupon->max_discount_amount, 2) }})</span> @endif
                                        @elseif($coupon->type === 'fixed')
                                            {{ $currencySymbol }}{{ number_format($coupon->discount_value, 2) }}
                                        @elseif($coupon->type === 'bogo')
                                            Buy One Get One Free
                                        @elseif($coupon->type === 'free_shipping')
                                            Free Shipping
                                        @elseif($coupon->type === 'tiered')
                                            Tiered Discount
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Minimum Purchase</h6>
                                    <p class="mb-3">
                                        @if($coupon->minimum_purchase_amount > 0)
                                            {{ $currencySymbol }}{{ number_format($coupon->minimum_purchase_amount, 2) }}
                                        @else
                                            <span class="text-muted">No minimum</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Validity Period</h6>
                                    <p class="mb-3">
                                        {{ $coupon->start_date->format('M d, Y') }}
                                        @if($coupon->end_date)
                                            &mdash; {{ $coupon->end_date->format('M d, Y') }}
                                        @else
                                            <span class="text-muted">&mdash; No expiry</span>
                                        @endif
                                        <br><small class="text-muted">Timezone: {{ $coupon->timezone }}</small>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Issue status</h6>
                                    <div class="mb-3">
                                        @if($coupon->isIssued())
                                            <span class="badge bg-success-subtle text-success mb-1">Issued</span>
                                            <div class="fs-sm text-muted">
                                                Handed over {{ $coupon->issued_at->format('M d, Y') }}
                                                @if($coupon->issued_by)
                                                    by {{ optional(\App\Models\User::find($coupon->issued_by))->name }}
                                                @endif
                                            </div>
                                            @if($coupon->end_date)
                                                <div class="fs-sm {{ $coupon->isExpired() ? 'text-danger' : 'text-muted' }}">
                                                    {{ $coupon->isExpired() ? 'Expired on' : 'Valid until' }}
                                                    {{ $coupon->end_date->format('M d, Y') }}
                                                    @if(! $coupon->isExpired() && $coupon->daysUntilExpiry() !== null)
                                                        ({{ $coupon->daysUntilExpiry() }} day(s) left)
                                                    @endif
                                                </div>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary mb-1">Not issued</span>
                                            <div class="fs-sm text-muted">
                                                Printed but not handed to a customer, so it cannot be redeemed yet.
                                                @if($coupon->valid_days)
                                                    Expiry starts when the card is issued ({{ $coupon->valid_days }} days).
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    @if($coupon->isValueBased())
                                        @php
                                            $usedPct = $coupon->valueUsedPercent();
                                            $barClass = $usedPct >= 99 ? 'danger' : ($usedPct >= 70 ? 'warning' : 'success');
                                        @endphp
                                        <h6 class="text-uppercase text-muted fs-xs mb-2">Balance</h6>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                                <span class="fw-semibold {{ $coupon->remainingValue() <= 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ $currencySymbol }}{{ number_format($coupon->remainingValue(), 2) }} left
                                                </span>
                                                <span class="fs-xs text-muted">{{ number_format($usedPct, 0) }}% used</span>
                                            </div>
                                            <div class="progress" style="height:10px" role="progressbar"
                                                 aria-valuenow="{{ (int) $usedPct }}" aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar bg-{{ $barClass }}"
                                                     style="width: {{ $usedPct <= 0 ? 0 : max(3, $usedPct) }}%"></div>
                                            </div>
                                            <div class="d-flex justify-content-between fs-xs text-muted mt-1">
                                                <span>{{ $currencySymbol }}{{ number_format((float) $coupon->consumed_value, 2) }} used</span>
                                                <span>of {{ $currencySymbol }}{{ number_format($coupon->valueLimit(), 2) }}</span>
                                            </div>
                                            <div class="fs-xxs text-muted mt-1">
                                                Partial use is on: this code keeps its balance and can be used again until it reaches zero.
                                            </div>
                                        </div>
                                    @endif

                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Usage Limits</h6>
                                    <p class="mb-3">
                                        @if($coupon->isValueBased())
                                            Limited by balance: this code can be used until the
                                            {{ $currencySymbol }}{{ number_format($coupon->valueLimit(), 2) }} runs out.<br>
                                            Used so far: {{ $stats['total_redemptions'] ?? 0 }} time(s),
                                            {{ $currencySymbol }}{{ number_format((float) $coupon->consumed_value, 2) }} of value.
                                        @else
                                            Total: {{ $coupon->total_usage_limit ?: '&#8734;' }}<br>
                                            Per Customer: {{ $coupon->per_customer_limit ?: '&#8734;' }}<br>
                                            Used: {{ $stats['total_redemptions'] ?? 0 }} times
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Stackable</h6>
                                    <p class="mb-3">
                                        @if($coupon->stackable)
                                            <span class="badge bg-success-subtle text-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">No</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Batch</h6>
                                    <p class="mb-3">
                                        @if($coupon->batch)
                                            {{ $coupon->batch->name }}
                                        @else
                                            <span class="text-muted">No batch</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($coupon->description)
                            <div class="mt-3">
                                <h6 class="text-uppercase text-muted fs-xs mb-2">Description</h6>
                                <p>{{ $coupon->description }}</p>
                            </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            {{-- Redemption Stats --}}
                            <div class="card border mb-3">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Redemption Stats</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <h2 class="mb-1 ff-secondary">{{ $stats['total_redemptions'] ?? 0 }}</h2>
                                        <p class="text-muted mb-0">Total Redemptions</p>
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h5 class="mb-1 ff-secondary">{{ $stats['unique_customers'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0 fs-xs">Unique Customers</p>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="mb-1 ff-secondary">{{ $currencySymbol }}{{ number_format($stats['total_discount_amount'] ?? 0, 2) }}</h5>
                                            <p class="text-muted mb-0 fs-xs">Total Discount Given</p>
                                        </div>
                                    </div>
                                    <hr class="my-3">
                                    <div class="row text-center fs-sm text-muted">
                                        <div class="col-4"><strong>{{ $stats['redemptions_today'] ?? 0 }}</strong><br>Today</div>
                                        <div class="col-4"><strong>{{ $stats['redemptions_this_week'] ?? 0 }}</strong><br>This Week</div>
                                        <div class="col-4"><strong>{{ $stats['redemptions_this_month'] ?? 0 }}</strong><br>This Month</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Restrictions --}}
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Restrictions</h5>
                                </div>
                                <div class="card-body">
                                    <h6 class="fs-sm fw-semibold mb-2">Customer Eligibility</h6>
                                    <p class="mb-2">
                                        {{ ucfirst($coupon->customer_eligibility_type) }}
                                        @if($coupon->customer_eligibility_type === 'groups' && $coupon->customerGroups->count())
                                            <br><small>
                                                Groups:
                                                @foreach($coupon->customerGroups as $group)
                                                    <span class="badge bg-primary-subtle text-primary">{{ $group->name }}</span>
                                                @endforeach
                                            </small>
                                        @endif
                                    </p>

                                    <h6 class="fs-sm fw-semibold mb-2 mt-3">Location Restrictions</h6>
                                    <p class="mb-2">
                                        {{ ucfirst($coupon->location_restriction_type) }}
                                        @if($coupon->location_restriction_type === 'specific' && $coupon->locations->count())
                                            <br><small>
                                                @foreach($coupon->locations as $location)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $location->name }}</span>
                                                @endforeach
                                            </small>
                                        @endif
                                    </p>

                                    <h6 class="fs-sm fw-semibold mb-2 mt-3">Product Restrictions</h6>
                                    <p class="mb-2">
                                        {{ ucfirst($coupon->product_restriction_type) }}
                                        @if($coupon->product_restriction_type === 'specific' && $coupon->products->count())
                                            <br><small>{{ $coupon->products->count() }} product(s)/service(s) selected</small>
                                        @endif
                                        @if($coupon->product_restriction_type === 'categories' && $coupon->categories->count())
                                            <br><small>
                                                @foreach($coupon->categories as $cat)
                                                    <span class="badge bg-info-subtle text-info">{{ $cat->name }}</span>
                                                @endforeach
                                            </small>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Redemption History --}}
                    <div class="mt-5">
                        <h5 class="mb-3">Redemption History</h5>
                        @if($coupon->redemptions->count())
                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th class="ps-3">Date</th>
                                        <th>Sale</th>
                                        <th>Customer</th>
                                        <th>Discount Applied</th>
                                        <th>Sale Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($coupon->redemptions as $redemption)
                                    <tr>
                                        <td class="ps-3">{{ $redemption->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            @if($redemption->sale)
                                            <a href="{{ route('pos.receipt', $redemption->sale) }}" target="_blank" class="fw-semibold">#{{ $redemption->sale->sale_number }}</a>
                                            @else
                                            <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($redemption->customer)
                                                {{ $redemption->customer->full_name ?? $redemption->customer->first_name . ' ' . $redemption->customer->last_name }}
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td class="text-success fw-semibold">{{ $currencySymbol }}{{ number_format($redemption->discount_amount, 2) }}</td>
                                        <td>
                                            @if($redemption->sale)
                                                {{ $currencySymbol }}{{ number_format($redemption->sale->total_amount, 2) }}
                                            @else
                                                <span class="text-muted">&mdash;</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-light"><i class="ti ti-history me-2"></i> No redemption history yet.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    
    <script>
        @if(session('success'))
            Swal.fire({ title: 'Success!', text: @json(session('success')), icon: 'success', confirmButtonClass: 'btn btn-primary' });
        @endif
        @if(session('error'))
            Swal.fire({ title: 'Error!', text: @json(session('error')), icon: 'error', confirmButtonClass: 'btn btn-primary' });
        @endif
    </script>
@endsection
