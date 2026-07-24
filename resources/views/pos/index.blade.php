@extends('layouts.vertical', ['title' => 'Point of Sale'])

@section('css')
    
<style>
/* ============================================
   POS — Mobile-First (Base: < 768px)
   ============================================ */

/* ── Layout ── */
.pos-grid { display: flex; flex-direction: column; gap: 0.5rem; }
.pos-services { order: 1; }
.pos-cart-desktop { order: 2; }

/* ── Service Cards ── */
.services-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
@media (min-width: 576px) { .services-grid { grid-template-columns: 1fr 1fr 1fr; } }
@media (min-width: 992px) { .services-grid { grid-template-columns: 1fr 1fr 1fr 1fr; } }

.svc-card {
    cursor: pointer; border: 2px solid transparent; border-radius: 0.5rem;
    background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    transition: all 0.2s; padding: 0.75rem 0.5rem; text-align: center;
    position: relative; user-select: none;
}
.svc-card:active { transform: scale(0.96); }
.svc-card.adding { animation: svc-pulse 0.3s; }
@keyframes svc-pulse { 50% { transform: scale(1.05); } }
.svc-card .icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.4rem; font-size: 1.1rem; }
.svc-card .icon.service { background: #e7f1ff; color: #339af0; }
.svc-card .icon.package { background: #d3f9d8; color: #2f9e44; }
.svc-card h6 { font-size: 0.75rem; margin: 0 0 0.2rem; }
.svc-card .price { font-size: 0.85rem; font-weight: 700; color: #339af0; margin: 0; }
.svc-card .badge { font-size: 0.6rem; padding: 0.1rem 0.3rem; }
.svc-card .plus-badge {
    position: absolute; top: -0.3rem; right: -0.3rem;
    width: 20px; height: 20px; border-radius: 50%;
    background: #40c057; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

/* ── Mobile Bottom Cart Bar ── */
.cart-bar {
    display: flex; position: fixed; bottom: 0; left: 0; right: 0;
    z-index: 1035; background: #fff; border-top: 2px solid #339af0;
    padding: 0.5rem 0.75rem; gap: 0.5rem; align-items: center;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
}
.cart-bar-total { font-weight: 700; font-size: 1rem; white-space: nowrap; }
.cart-bar-count { background: #339af0; color: #fff; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600; }

/* ── Cart Drawer (Mobile) ── */
.cart-overlay {
    display: none; position: fixed; inset: 0; z-index: 1045;
    background: rgba(0,0,0,0.4);
}
.cart-overlay.show { display: block; }

.cart-drawer {
    display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    z-index: 1050; background: #fff; overflow-y: auto;
    flex-direction: column;
}
.cart-drawer.open { display: flex; }
.cart-drawer-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.75rem; background: #339af0; color: #fff;
    position: sticky; top: 0; z-index: 1;
}
.cart-drawer-body { flex: 1; padding: 0.75rem; padding-bottom: 5rem; overflow-y: auto; }

/* ── Desktop Cart Sidebar ── */
@media (min-width: 992px) {
    .pos-grid { flex-direction: row; }
    .pos-services { flex: 1; order: 1; min-width: 0; }
    .pos-cart-desktop { width: 360px; flex-shrink: 0; order: 2; }
    .cart-bar, .cart-overlay, .cart-drawer { display: none !important; }
    .cart-desktop { display: block !important; }
    .cart-desktop .card { position: sticky; top: 1rem; }
}

/* ── Search & Filter ── */
.search-wrap { position: relative; }
.search-wrap input { padding-left: 2.2rem; font-size: 0.9rem; }
.search-wrap .search-icon { position: absolute; left: 0.7rem; top: 50%; transform: translateY(-50%); color: #adb5bd; z-index: 4; }

/* ── Payment Modal ── */
.pay-totals { display: flex; flex-direction: column; gap: 0.25rem; }
@media (min-width: 576px) { .pay-totals { flex-direction: row; } }
.pay-totals > div { flex: 1; text-align: center; padding: 0.5rem; background: #f8f9fa; border-radius: 0.5rem; }

.pay-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.pay-btn {
    display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    padding: 0.75rem; border-radius: 0.5rem; border: 2px solid #dee2e6;
    background: #fff; cursor: pointer; font-weight: 600; font-size: 0.9rem;
    transition: all 0.15s;
}
.pay-btn.active { border-color: #339af0; background: #e7f1ff; }
.pay-btn .pay-icon { font-size: 1.3rem; }

.keypad { max-width: 320px; margin: 0 auto; }
.keypad-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0.4rem; }
.kbtn {
    border: none; border-radius: 0.5rem; padding: 0.7rem 0.3rem;
    font-size: 1.1rem; font-weight: 600; background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08); cursor: pointer;
    transition: all 0.1s; min-height: 44px;
}
.kbtn:active { transform: scale(0.95); }
.kbtn-action { background: #e7f1ff; color: #339af0; }
.kbtn-danger { background: #ffe3e3; color: #ff6b6b; }
.kbtn-ok { background: linear-gradient(135deg,#40c057,#2b8a3e); color: #fff; }
.kbtn-zero { grid-column: span 2; }

.quick-amounts { display: grid; grid-template-columns: repeat(4,1fr); gap: 0.3rem; }
.quick-amounts .btn { font-size: 0.8rem; padding: 0.35rem 0.3rem; min-height: 38px; }

/* ── Split Payments ── */
.split-row .d-flex { flex-wrap: wrap; gap: 0.25rem; }
.split-row select { width: 110px; font-size: 0.8rem; }
.split-row .input-group { width: 120px; }
.split-row .ref-input { flex: 1; min-width: 70px; }

/* ── Cart Items ── */
.cart-item {
    display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem;
    border-left: 3px solid #339af0; margin-bottom: 0.4rem;
    background: #f8f9fa; border-radius: 0.4rem;
}
.cart-item-name { flex: 1; font-size: 0.85rem; font-weight: 600; }
.cart-item-qty { display: flex; align-items: center; gap: 0.3rem; }
.cart-item-qty button { width: 28px; height: 28px; padding: 0; font-size: 0.8rem; }
.cart-item-price { font-size: 0.85rem; font-weight: 700; white-space: nowrap; }

/* ── Misc ── */
.empty-state { text-align: center; padding: 2rem 1rem; color: #adb5bd; }
.empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
select.form-select-sm { font-size: 0.8rem; }
</style>
@endsection

@section('content')
@include('layouts.partials.page-title', [
    'title' => isset($editSale) ? 'Edit Sale #' . $editSale['sale_number'] : 'Point of Sale',
    'subtitle' => isset($editSale) ? 'Modify sale items, customer, and payment' : 'Process sales and manage transactions'
])

@if(isset($editSale))
<div class="alert alert-info py-2 mb-2 d-flex align-items-center justify-content-between">
    <span><i class="ti ti-edit-circle me-1"></i> Editing Sale <strong>#{{ $editSale['sale_number'] }}</strong> — changes will update the existing record</span>
    <a href="{{ route('pos.transactions') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>
@endif

<div class="pos-grid">
    {{-- ========== SERVICES COLUMN ========== --}}
    <div class="pos-services">
        <div class="card shadow-sm">
            <div class="card-body p-2 p-md-3">
                {{-- Search --}}
                <div class="row g-2 mb-2">
                    <div class="col-12 col-md-7">
                        <label class="form-label fs-sm mb-1">Search Services & Packages</label>
                        <div class="search-wrap">
                            <i class="ti ti-search search-icon"></i>
                            <input type="text" class="form-control" id="item-search"
                                   placeholder="Type to search..." autocomplete="off">
                        </div>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label fs-sm mb-1">Filter</label>
                        <div class="btn-group w-100" role="group" id="filter-group">
                            <input type="radio" class="btn-check" name="cf" id="cf-all" value="all" checked>
                            <label class="btn btn-outline-primary btn-sm" for="cf-all">All</label>
                            <input type="radio" class="btn-check" name="cf" id="cf-svc" value="service">
                            <label class="btn btn-outline-primary btn-sm" for="cf-svc">Services</label>
                            <input type="radio" class="btn-check" name="cf" id="cf-pkg" value="package">
                            <label class="btn btn-outline-primary btn-sm" for="cf-pkg">Packs</label>
                        </div>
                    </div>
                </div>

                {{-- Tabs --}}
                <ul class="nav nav-tabs nav-bordered mb-2" style="flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;">
                    <li class="nav-item"><a href="#tab-services" data-bs-toggle="tab" class="nav-link active" style="white-space:nowrap;">Services</a></li>
                    <li class="nav-item"><a href="#tab-packages" data-bs-toggle="tab" class="nav-link" style="white-space:nowrap;">Packages</a></li>
                </ul>

                {{-- Service Grid --}}
                <div class="tab-content">
                    <div class="tab-pane active" id="tab-services">
                        <div class="services-grid" id="services-grid">
                            @foreach($services as $s)
                            <div class="svc-card" data-id="{{ $s->id }}" data-type="service" data-name="{{ $s->name }}" data-price="{{ $s->price }}" data-duration="{{ $s->duration }}">
                                <div class="plus-badge"><i class="ti ti-plus"></i></div>
                                <div class="icon service"><i class="ti ti-scissors"></i></div>
                                <h6>{{ $s->name }}</h6>
                                <div class="price">{{ $currencySymbol }}{{ number_format($s->price, 2) }}</div>
                                <span class="badge bg-info-subtle text-info mt-1">{{ $s->duration }}m</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-packages">
                        <div class="services-grid" id="packages-grid">
                            @foreach($servicePackages as $p)
                            <div class="svc-card" data-id="{{ $p->id }}" data-type="package" data-name="{{ $p->name }}" data-price="{{ $p->price }}">
                                <div class="plus-badge"><i class="ti ti-plus"></i></div>
                                <div class="icon package"><i class="ti ti-package"></i></div>
                                <h6>{{ $p->name }}</h6>
                                <div class="price">{{ $currencySymbol }}{{ number_format($p->price, 2) }}</div>
                                <span class="badge bg-success-subtle text-success mt-1">{{ $p->session_count }} ses</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== CART — DESKTOP SIDEBAR ========== --}}
    <div class="pos-cart-desktop cart-desktop" id="cart-desktop" style="display:none;">
        <div class="card shadow-sm" id="cart-card">
            <div class="card-header bg-primary text-white py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="ti ti-shopping-cart me-1"></i>Sale</span>
                    <span class="badge bg-white text-primary" id="cart-count-badge">0</span>
                </div>
            </div>
            <div class="card-body p-2" id="cart-body">
                {{-- Customer --}}
                <div class="mb-2">
                    <label class="form-label fs-xs mb-1">Customer</label>
                    <select class="form-select form-select-sm" id="customer-select" data-choices data-choices-search-true>
                        <option value="">Walk-in Customer</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Staff --}}
                <div class="mb-2">
                    <label class="form-label fs-xs mb-1">Staff</label>
                    <select class="form-select form-select-sm" id="staff-select" data-choices data-choices-search-true>
                        <option value="">Current User ({{ auth()->user()->name }})</option>
                        @foreach($staff as $m)
                        @if($m->id != auth()->id())
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endif
                        @endforeach
                    </select>
                </div>
                {{-- Quick add customer --}}
                <button class="btn btn-outline-primary btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#customerModal">
                    <i class="ti ti-user-plus me-1"></i>Add Customer
                </button>

                {{-- Cart items --}}
                <div id="cart-items-area">
                    <div class="empty-state" id="empty-cart">
                        <div class="empty-icon"><i class="ti ti-shopping-cart-off"></i></div>
                        <p class="mb-0 fs-sm">Cart is empty</p>
                    </div>
                    <div id="cart-items-list"></div>
                </div>

                {{-- Discount --}}
                <div class="mb-2">
                    <label class="form-label fs-xs mb-1">Discount</label>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control" id="discount-input" placeholder="0" step="0.01">
                        <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown">
                            <span id="discount-label">{{ $currencySymbol }}</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-dtype="fixed">Fixed ({{ $currencySymbol }})</a></li>
                            <li><a class="dropdown-item" href="#" data-dtype="percent">%</a></li>
                        </ul>
                    </div>
                </div>

                {{-- Coupon --}}
                <div class="mb-2">
                    <label class="form-label fs-xs mb-1">Coupon</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="coupon-input" placeholder="Code">
                        <button class="btn btn-outline-primary btn-sm" id="coupon-validate-btn">Go</button>
                    </div>
                    <div id="coupon-feedback" class="fs-xs mt-1"></div>
                </div>

                {{-- Totals --}}
                <div class="border-top pt-2 mb-2">
                    <div class="d-flex justify-content-between fs-sm"><span class="text-muted">Subtotal:</span><span id="cart-subtotal" class="fw-semibold">{{ $currencySymbol }}0.00</span></div>
                    <div class="d-flex justify-content-between fs-sm"><span class="text-muted">Discount:</span><span id="cart-discount" class="text-success fw-semibold">-{{ $currencySymbol }}0.00</span></div>
                    <div class="d-flex justify-content-between fs-sm"><span class="text-muted">Tax:</span><span id="cart-tax" class="fw-semibold">{{ $currencySymbol }}0.00</span></div>
                    <div class="d-flex justify-content-between fw-bold mt-1 pt-1 border-top"><span>Total:</span><span id="cart-total" class="text-primary">{{ $currencySymbol }}0.00</span></div>
                </div>

                {{-- Notes --}}
                <div class="mb-2">
                    <textarea class="form-control form-control-sm" id="sale-notes" rows="1" placeholder="Notes..."></textarea>
                </div>

                {{-- Buttons --}}
                <button class="btn btn-success btn-sm w-100 mb-1" id="complete-sale-btn">
                    @if(isset($editSale))
                        <i class="ti ti-device-floppy me-1"></i> Update Sale (<span id="btn-total">{{ $currencySymbol }}0.00</span>)
                    @else
                        Complete Sale (<span id="btn-total">{{ $currencySymbol }}0.00</span>)
                    @endif
                </button>
                <div class="btn-group w-100">
                    <button class="btn btn-light btn-sm" id="hold-btn"><i class="ti ti-clock-pause me-1"></i>Hold</button>
                    <button class="btn btn-danger btn-sm" id="clear-btn"><i class="ti ti-trash me-1"></i>Clear</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========== MOBILE CART BAR ========== --}}
<div class="cart-bar" id="cart-bar" style="display:none;">
    <span class="cart-bar-total" id="cart-bar-total">{{ $currencySymbol }}0.00</span>
    <span class="cart-bar-count" id="cart-bar-count">0</span>
    <button class="btn btn-outline-primary btn-sm" id="cart-bar-view" style="flex:1;">View Cart</button>
    <button class="btn btn-success btn-sm" id="cart-bar-pay" style="flex:1;">Pay</button>
</div>
<div class="cart-overlay" id="cart-overlay"></div>

{{-- ========== CART DRAWER (MOBILE) ========== --}}
<div class="cart-drawer" id="cart-drawer">
    <div class="cart-drawer-header">
        <span class="fw-bold"><i class="ti ti-shopping-cart me-1"></i>Current Sale</span>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-white text-primary" id="drawer-count">0</span>
            <button class="btn btn-sm btn-close btn-close-white" id="drawer-close"></button>
        </div>
    </div>
    <div class="cart-drawer-body" id="drawer-body">
        {{-- Copy of cart sidebar content (injected by JS) --}}
        <div id="drawer-content"></div>
    </div>
</div>

{{-- ========== PAYMENT MODAL ========== --}}
<div class="modal fade" id="payModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title"><i class="ti ti-credit-card me-1"></i> Complete Payment</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2 p-md-3">
                {{-- Totals --}}
                <div class="pay-totals mb-2">
                    <div><small class="text-muted d-block fs-xs">Total Due</small><span class="fw-bold fs-5 text-primary" id="pm-total">{{ $currencySymbol }}0.00</span></div>
                    <div><small class="text-muted d-block fs-xs">Received</small>
                        <div class="input-group input-group-sm mx-auto" style="max-width:160px;">
                            <span class="input-group-text px-1">{{ $currencySymbol }}</span>
                            <input type="text" class="form-control text-center fw-bold" id="pm-received" value="0.00" inputmode="numeric" autocomplete="off">
                        </div>
                    </div>
                    <div><small class="text-muted d-block fs-xs">Change</small><span class="fw-bold fs-5 text-success" id="pm-change">{{ $currencySymbol }}0.00</span></div>
                </div>

                <div class="row g-2 flex-column-reverse flex-md-row">
                    {{-- Left: Keypad + Quick amounts --}}
                    <div class="col-md-6">
                        <div class="quick-amounts mb-2" id="quick-amounts"></div>
                        <div class="keypad">
                            <div class="keypad-grid">
                                <button class="kbtn" data-k="7">7</button>
                                <button class="kbtn" data-k="8">8</button>
                                <button class="kbtn" data-k="9">9</button>
                                <button class="kbtn kbtn-action" data-k="bs"><i class="ti ti-backspace"></i></button>
                                <button class="kbtn" data-k="4">4</button>
                                <button class="kbtn" data-k="5">5</button>
                                <button class="kbtn" data-k="6">6</button>
                                <button class="kbtn kbtn-danger" data-k="clr"><i class="ti ti-trash"></i></button>
                                <button class="kbtn" data-k="1">1</button>
                                <button class="kbtn" data-k="2">2</button>
                                <button class="kbtn" data-k="3">3</button>
                                <button class="kbtn kbtn-ok" id="exact-btn">OK</button>
                                <button class="kbtn kbtn-zero" data-k="0">0</button>
                                <button class="kbtn" data-k=".">.</button>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Payment method + Summary --}}
                    <div class="col-md-6">
                        {{-- Order Summary --}}
                        <div class="card mb-2">
                            <div class="card-body py-2 px-3">
                                <a class="d-flex justify-content-between text-body text-decoration-none" data-bs-toggle="collapse" href="#osCollapse">
                                    <span class="fw-semibold fs-sm"><i class="ti ti-receipt me-1"></i>Summary</span>
                                    <i class="ti ti-chevron-down"></i>
                                </a>
                                <div class="collapse show" id="osCollapse">
                                    <div class="pt-2">
                                        <div class="d-flex justify-content-between fs-sm"><span class="text-muted">Sub:</span><span id="pm-sub">{{ $currencySymbol }}0.00</span></div>
                                        <div class="d-flex justify-content-between fs-sm"><span class="text-muted">Disc:</span><span id="pm-disc" class="text-danger">-{{ $currencySymbol }}0.00</span></div>
                                        <div class="d-flex justify-content-between fs-sm"><span class="text-muted">Tax:</span><span id="pm-tax">{{ $currencySymbol }}0.00</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Payment method --}}
                        <div class="mb-2">
                            <label class="form-label fs-sm mb-1">Payment Method</label>
                            <div class="pay-btns mb-1">
                                <div class="pay-btn active" data-method="cash" id="pay-cash">
                                    <span class="pay-icon">💵</span>
                                    <div class="text-start lh-sm">
                                        <div class="fw-bold">Cash</div>
                                        <small id="pay-cash-amt" class="opacity-75">Rs.0.00</small>
                                    </div>
                                </div>
                                <div class="pay-btn" data-method="card" id="pay-card">
                                    <span class="pay-icon">💳</span>
                                    <div class="text-start lh-sm">
                                        <div class="fw-bold">Card</div>
                                        <small id="pay-card-amt" class="opacity-75">──</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Split toggle --}}
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="form-check form-switch mb-0">
                                    <input type="checkbox" class="form-check-input" id="split-switch">
                                    <label class="form-check-label fs-sm" for="split-switch">Split Payment</label>
                                </div>
                                <span id="split-badge" class="badge bg-success fs-xs d-none">Covered ✓</span>
                            </div>

                            {{-- Split rows --}}
                            <div id="split-area" class="d-none"></div>
                        </div>

                        {{-- Notes --}}
                        <textarea class="form-control form-control-sm" id="pay-notes" rows="1" placeholder="Payment notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirm-pay-btn">Confirm & Complete</button>
            </div>
        </div>
    </div>
</div>

{{-- ========== CUSTOMER MODAL ========== --}}
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title"><i class="ti ti-user-plus me-1"></i>Add Customer</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="quick-customer-form">
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="cust-err"><ul id="cust-err-list" class="mb-0"></ul></div>
                    <div class="mb-2"><label class="form-label">First Name *</label><input type="text" class="form-control" id="cust-fname" required></div>
                    <div class="mb-2"><label class="form-label">Last Name *</label><input type="text" class="form-control" id="cust-lname" required></div>
                    <div class="mb-2"><label class="form-label">Phone *</label><input type="tel" class="form-control" id="cust-phone" required placeholder="0771234567"></div>
                    <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" id="cust-email" placeholder="customer@example.com"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="cust-save-btn"><i class="ti ti-check me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Toast --}}
<div class="toast-container position-fixed top-0 end-0 p-2" style="z-index:9999;">
    <div class="toast" id="toast-tpl" role="alert" style="display:none;">
        <div class="d-flex"><div class="toast-body"><span class="toast-msg"></span></div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button></div>
    </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/pages/pos.js'])
<script>
window.businessHours = @json($businessHours);
window.posSettings = @json($posSettings);
window.currencySymbol = "{{ $currencySymbol }}";
@if(isset($editSale))
window.editSale = @json($editSale);
@endif
</script>
@endsection