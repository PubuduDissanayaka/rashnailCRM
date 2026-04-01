@extends('layouts.vertical', ['title' => 'Point of Sale'])

@section('css')
    @vite([
        'node_modules/choices.js/public/assets/styles/choices.min.css',
        'node_modules/sweetalert2/dist/sweetalert2.min.css'
    ])
    <style>
        .pos-product-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .pos-product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1) !important;
            border-color: #dee2e6;
        }

        .pos-product-card.adding {
            animation: pulse 0.5s;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .cart-item {
            border-left: 4px solid #3e60d5;
            transition: all 0.3s ease;
        }

        .cart-item.new {
            animation: highlight 1s;
        }

        @keyframes highlight {
            0% { background-color: #d7e8ff; }
            100% { background-color: transparent; }
        }

        .numeric-keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .keypad-btn {
            height: 60px;
            font-size: 1.2rem;
        }

        .pos-search-container {
            position: relative;
        }

        .pos-search-container .ti-search {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 4;
            color: #adb5bd;
        }

        .pos-search-container input {
            padding-left: 40px !important;
        }

        .sticky-top-card {
            position: sticky;
            top: 20px;
        }

        /* Enhanced Enterprise Keypad Styles */
        .enterprise-keypad {
            max-width: 400px;
            margin: 0 auto;
            padding: 15px;
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            border-radius: 16px;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.6),
                0 10px 20px rgba(0,0,0,0.1);
        }

        .keypad-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            width: 100%;
        }

        .keypad-btn-enterprise {
            position: relative;
            overflow: hidden;
            border: none;
            border-radius: 12px;
            padding: 20px 10px;
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
            background: white;
            box-shadow:
                0 4px 6px rgba(0,0,0,0.1),
                0 1px 3px rgba(0,0,0,0.08);
            cursor: pointer;
            transition: all 0.2s ease;
            outline: none;
        }

        .keypad-btn-enterprise:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow:
                0 6px 12px rgba(0,0,0,0.15),
                0 2px 4px rgba(0,0,0,0.1);
        }

        .keypad-btn-enterprise:active {
            transform: translateY(0);
            box-shadow:
                0 2px 4px rgba(0,0,0,0.1),
                0 1px 2px rgba(0,0,0,0.06);
        }

        .keypad-btn-enterprise:focus {
            box-shadow:
                0 0 0 3px rgba(59, 125, 211, 0.25),
                0 4px 6px rgba(0,0,0,0.1);
        }

        /* Ripple effect for button press */
        .keypad-btn-ripple-effect {
            position: absolute;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(0,0,0,0.2);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        .keypad-btn-enterprise {
            position: relative;
            overflow: visible;
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* Button variations */
        .keypad-btn-action {
            background: #e7f1ff;
            color: #339af0;
        }

        .keypad-btn-action:hover {
            background: #d0ebff;
        }

        .keypad-btn-action-danger {
            background: #ffe3e3;
            color: #ff6b6b;
        }

        .keypad-btn-action-danger:hover {
            background: #ffc9c9;
        }

        .keypad-btn-exact-amount {
            background: linear-gradient(135deg, #40c057, #2b8a3e);
            color: white;
        }

        .keypad-btn-exact-amount:hover {
            background: linear-gradient(135deg, #37b34a, #277a37);
        }

        /* Ensure exact button has same sizing as others */
        .keypad-btn-exact-amount .keypad-btn-number {
            font-size: 1.3rem; /* Better font size for shorter OK text */
        }

        /* Special-sized buttons */
        .keypad-btn-zero {
            grid-column: span 2;
        }

        /* Animation for button press */
        @keyframes keypad-press {
            0% { transform: scale(1); }
            50% { transform: scale(0.95); }
            100% { transform: scale(1); }
        }

        .keypad-btn-pressed {
            animation: keypad-press 0.2s ease;
        }

        /* Make sure buttons have proper stacking context for ripple effect */
        .keypad-btn-enterprise {
            position: relative;
            overflow: hidden;
        }

        /* ─── Payment Modal Responsive ─── */
        @media (max-width: 991.98px) {
            #paymentModal .modal-dialog {
                max-width: 95vw;
                margin: 0.5rem auto;
            }
            #paymentModal .modal-body {
                padding: 0.75rem;
            }
            .enterprise-keypad {
                max-width: 100%;
                padding: 10px;
            }
            .keypad-grid {
                gap: 8px;
            }
            .keypad-btn-enterprise {
                padding: 14px 6px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 767.98px) {
            #paymentModal .modal-dialog {
                max-width: 100%;
                margin: 0;
                min-height: 100vh;
            }
            #paymentModal .modal-content {
                border-radius: 0;
                min-height: 100vh;
            }
            #paymentModal .modal-body {
                padding: 0.5rem;
                overflow-y: auto;
                max-height: calc(100vh - 130px);
            }
            .enterprise-keypad {
                max-width: 320px;
                padding: 8px;
            }
            .keypad-grid {
                gap: 6px;
            }
            .keypad-btn-enterprise {
                padding: 12px 4px;
                font-size: 1.1rem;
                border-radius: 8px;
            }
            /* Stack amount + change side by side on mobile */
            #paymentModal .input-group-lg .form-control {
                font-size: 1.25rem !important;
            }
        }

        @media (max-width: 575.98px) {
            .keypad-btn-enterprise {
                padding: 10px 2px;
                font-size: 1rem;
            }
            .keypad-grid {
                gap: 4px;
            }
        }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Point of Sale', 'subtitle' => 'Process sales and manage transactions'])

    <div class="row">
        <!-- Services/Service Packages Column (Left) -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <!-- Search Bar & Category Filters -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <label for="item-search" class="form-label">Search Services & Packages</label>
                                <div class="pos-search-container">
                                    <i class="ti ti-search position-absolute fs-16 text-muted"></i>
                                    <input type="text" class="form-control form-control-lg ps-5"
                                           id="item-search"
                                           placeholder="Search by service name, price, duration..."
                                           autocomplete="off">
                                    <span class="position-absolute end-0 top-50 translate-middle-y pe-2 d-none" id="search-loading">
                                        <i class="ti ti-loader-2 animate-spin text-muted"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Filter Category</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="category-filter" id="filter-all" value="all" checked>
                                    <label class="btn btn-outline-primary" for="filter-all">
                                        <i class="ti ti-layout-grid me-1"></i>All
                                    </label>
                                    <input type="radio" class="btn-check" name="category-filter" id="filter-services" value="service">
                                    <label class="btn btn-outline-primary" for="filter-services">
                                        <i class="ti ti-briefcase me-1"></i>Services
                                    </label>
                                    <input type="radio" class="btn-check" name="category-filter" id="filter-packages" value="package">
                                    <label class="btn btn-outline-primary" for="filter-packages">
                                        <i class="ti ti-package me-1"></i>Packages
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Services and Packages Tabs -->
                    <ul class="nav nav-tabs nav-bordered mb-3">
                        <li class="nav-item">
                            <a href="#services-tab" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                <i class="ti ti-briefcase me-1"></i> Services
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#packages-tab" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                <i class="ti ti-package me-1"></i> Packages
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane show active" id="services-tab">
                            <div class="row row-cols-xxl-4 row-cols-md-3 row-cols-2 g-3" id="services-grid">
                                @foreach($services as $service)
                                <div class="col">
                                    <div class="card pos-product-card h-100 border border-1 shadow-sm"
                                         data-service-id="{{ $service->id }}"
                                         data-service-type="service"
                                         data-service-name="{{ $service->name }}"
                                         data-service-price="{{ $service->price }}">
                                        <div class="card-body text-center p-3 position-relative">
                                            <div class="position-relative mb-2">
                                                <div class="avatar-lg mx-auto">
                                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                                        <i class="ti ti-scissors fs-24"></i>
                                                    </span>
                                                </div>
                                                <span class="position-absolute top-0 start-100 translate-middle badge bg-success">
                                                    <i class="ti ti-plus fs-14"></i>
                                                </span>
                                            </div>
                                            <h6 class="mb-1 fw-semibold">{{ $service->name }}</h6>
                                            <p class="text-primary mb-1 fs-4 fw-bold">{{ $currencySymbol }}{{ number_format($service->price, 2) }}</p>
                                            <div class="d-flex justify-content-center">
                                                <span class="badge badge-soft-info fs-xs">
                                                    <i class="ti ti-clock me-1"></i>{{ $service->duration }} min
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Service Packages Tab -->
                        <div class="tab-pane" id="packages-tab">
                            <div class="row row-cols-xxl-4 row-cols-md-3 row-cols-2 g-3" id="packages-grid">
                                @foreach($servicePackages as $package)
                                <div class="col">
                                    <div class="card pos-product-card h-100 border border-1 shadow-sm"
                                         data-service-id="{{ $package->id }}"
                                         data-service-type="package"
                                         data-service-name="{{ $package->name }}"
                                         data-service-price="{{ $package->price }}">
                                        <div class="card-body text-center p-3 position-relative">
                                            <div class="position-relative mb-2">
                                                <div class="avatar-lg mx-auto">
                                                    <span class="avatar-title bg-success-subtle text-success rounded-circle">
                                                        <i class="ti ti-package fs-24"></i>
                                                    </span>
                                                </div>
                                                <span class="position-absolute top-0 start-100 translate-middle badge bg-success">
                                                    <i class="ti ti-plus fs-14"></i>
                                                </span>
                                            </div>
                                            <h6 class="mb-1 fw-semibold">{{ $package->name }}</h6>
                                            <p class="text-primary mb-1 fs-4 fw-bold">{{ $currencySymbol }}{{ number_format($package->price, 2) }}</p>
                                            <div class="d-flex justify-content-center">
                                                <span class="badge badge-soft-success fs-xs">
                                                    <i class="ti ti-layers me-1"></i>{{ $package->session_count }} Sessions
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart Column (Right) -->
        <div class="col-lg-4">
            <div class="card sticky-top-card border border-1 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-shopping-cart fs-20 me-2"></i>
                            <h5 class="card-title mb-0">Current Sale</h5>
                        </div>
                        <span class="badge bg-white text-primary fs-14" id="cart-count">0</span>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Customer Selection -->
                    <div class="mb-3">
                        <label for="customer-select" class="form-label">Customer</label>
                        <select class="form-select" id="customer-select" data-choices data-choices-search-true>
                            <option value="">Walk-in Customer</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->first_name }} {{ $customer->last_name }}
                                @if($customer->email) ({{ $customer->email }}) @endif
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Staff Selection -->
                    <div class="mb-3">
                        <label for="staff-select" class="form-label">Staff Member</label>
                        <select class="form-select" id="staff-select" data-choices data-choices-search-true>
                            <option value="">Current User ({{ auth()->user()->name }})</option>
                            @foreach($staff as $staffMember)
                                @if($staffMember->id != auth()->id())
                                <option value="{{ $staffMember->id }}"
                                    {{ old('staff_id') == $staffMember->id ? 'selected' : '' }}>
                                    {{ $staffMember->name }}
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <!-- Quick Customer Add -->
                    <div class="mb-4">
                        <button class="btn btn-outline-primary w-100" id="quick-add-customer-btn"
                                data-bs-toggle="modal" data-bs-target="#customerModal">
                            <i class="ti ti-user-plus me-1"></i>Add New Customer
                        </button>
                    </div>

                    <!-- Cart Items -->
                    <div class="mb-3">
                        <!-- Empty State -->
                        <div id="empty-cart-state" class="text-center py-5">
                            <div class="avatar-lg mx-auto mb-3 bg-light rounded-circle">
                                <i class="ti ti-shopping-cart-off fs-32 text-muted d-flex align-items-center justify-content-center"
                                   style="height: 100%;"></i>
                            </div>
                            <h6 class="text-muted">Cart is empty</h6>
                            <p class="text-muted fs-sm mb-0">Select items to add</p>
                        </div>

                        <!-- Cart Items Container -->
                        <div id="cart-items-container"></div>
                    </div>

                    <!-- Discount Section -->
                    <div class="mb-3">
                        <label for="discount-amount" class="form-label">Discount</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="discount-amount"
                                   placeholder="0.00" step="0.01">
                            <button class="btn btn-outline-secondary dropdown-toggle"
                                    type="button" data-bs-toggle="dropdown">
                                <span id="discount-type-label">{{ $currencySymbol }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" data-discount-type="fixed">Fixed ({{ $currencySymbol }})</a></li>
                                <li><a class="dropdown-item" href="#" data-discount-type="percent">Percentage (%)</a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Coupon Section -->
                    <div class="mb-3">
                        <label for="coupon-code" class="form-label">Coupon Code</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="coupon-code"
                                   placeholder="Enter coupon code" autocomplete="off">
                            <button class="btn btn-outline-primary" type="button" id="validate-coupon-btn">
                                Validate
                            </button>
                        </div>
                        <div id="coupon-error" class="invalid-feedback" style="display: none;"></div>
                        <div id="applied-coupons-container" class="mt-2"></div>
                    </div>

                    <!-- Subtotals and Totals -->
                    <div class="border-top border-bottom border-light py-2 mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Subtotal:</span>
                            <span id="cart-subtotal" class="text-dark fw-semibold">{{ $currencySymbol }}0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Discount:</span>
                            <span id="cart-discount" class="text-success fw-semibold">-{{ $currencySymbol }}0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Tax:</span>
                            <span id="cart-tax" class="text-dark fw-semibold">{{ $currencySymbol }}0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                            <span class="fs-5 fw-bold text-dark">Total:</span>
                            <span id="cart-total" class="fs-5 fw-bold text-primary">{{ $currencySymbol }}0.00</span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="sale-notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="sale-notes" rows="3" placeholder="Add notes about this sale..."></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-lg" id="complete-sale-btn">
                            <i class="ti ti-check me-1"></i> Complete Sale (<span id="btn-total" class="fw-bold">{{ $currencySymbol }}0.00</span>)
                        </button>
                        <div class="d-grid gap-2">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-light" id="hold-order-btn">
                                    <i class="ti ti-clock-pause me-1"></i>Hold
                                </button>
                                <button type="button" class="btn btn-danger" id="clear-cart-btn">
                                    <i class="ti ti-trash me-1"></i>Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div class="toast" id="toast-template" role="alert" style="display: none;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="ti ti-check-circle me-2"></i>
                    <span class="toast-message">Action completed</span>
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <!-- Payment Modal - Enterprise Edition -->
    <div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="ti ti-credit-card me-2"></i>
                        <span id="modal-step-title">Complete Payment</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" id="payment-modal-close"></button>
                </div>

                <div class="modal-body">
                    <!-- Step 1: Payment Entry -->
                    <div id="payment-step-1" class="payment-step">
                        <div class="row">
                            <!-- Left: Payment Details -->
                            <div class="col-lg-5">
                                <!-- Order Summary Card -->
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">Order Summary</h6>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Subtotal:</span>
                                            <span id="modal-subtotal" class="fw-semibold">$0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Discount:</span>
                                            <span id="modal-discount" class="fw-semibold text-success">-$0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Tax:</span>
                                            <span id="modal-tax" class="fw-semibold">$0.00</span>
                                        </div>
                                        <div class="border-top mt-2 pt-2">
                                            <div class="d-flex justify-content-between fs-4 fw-bold text-primary">
                                                <span>Total:</span>
                                                <span id="modal-total">$0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Method Selection -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Payment Method</label>
                                    <div class="row g-2" id="payment-methods-container">
                                        @foreach($paymentMethods as $index => $method)
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="payment-method"
                                                   id="pm-{{ $method }}" value="{{ $method }}"
                                                   {{ $index === 0 ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary w-100 text-start payment-method-card"
                                                   for="pm-{{ $method }}">
                                                @if($method === 'cash')
                                                    <i class="ti ti-cash fs-22 d-block mb-1"></i>Cash
                                                @elseif($method === 'card')
                                                    <i class="ti ti-credit-card fs-22 d-block mb-1"></i>Card
                                                @elseif($method === 'check')
                                                    <i class="ti ti-file-check fs-22 d-block mb-1"></i>Check
                                                @elseif($method === 'mobile')
                                                    <i class="ti ti-device-mobile fs-22 d-block mb-1"></i>Mobile
                                                @elseif($method === 'bank_transfer')
                                                    <i class="ti ti-building-bank fs-22 d-block mb-1"></i>Bank Transfer
                                                @elseif($method === 'store_credit')
                                                    <i class="ti ti-gift-card fs-22 d-block mb-1"></i>Store Credit
                                                @else
                                                    <i class="ti ti-wallet fs-22 d-block mb-1"></i>{{ ucfirst($method) }}
                                                @endif
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Payment Method Specific Fields -->
                                <div id="payment-method-fields">
                                    <!-- Card Fields -->
                                    <div class="payment-fields" data-method="card" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Authorization Code / Last 4 Digits</label>
                                            <input type="text" class="form-control" id="card-reference"
                                                   placeholder="1234" maxlength="20">
                                            <small class="text-muted">Required for card payments</small>
                                        </div>
                                    </div>

                                    <!-- Check Fields -->
                                    <div class="payment-fields" data-method="check" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Check Number</label>
                                            <input type="text" class="form-control" id="check-reference"
                                                   placeholder="Check #" maxlength="20">
                                            <small class="text-muted">Required for check payments</small>
                                        </div>
                                    </div>

                                    <!-- Bank Transfer Fields -->
                                    <div class="payment-fields" data-method="bank_transfer" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Transfer Reference Number</label>
                                            <input type="text" class="form-control" id="bank-reference"
                                                   placeholder="REF123456" maxlength="50">
                                            <small class="text-muted">Required for bank transfers</small>
                                        </div>
                                    </div>

                                    <!-- Mobile Payment Fields -->
                                    <div class="payment-fields" data-method="mobile" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Transaction ID</label>
                                            <input type="text" class="form-control" id="mobile-reference"
                                                   placeholder="TXN123456" maxlength="50">
                                            <small class="text-muted">Required for mobile payments</small>
                                        </div>
                                    </div>

                                    <!-- Store Credit Fields -->
                                    <div class="payment-fields" data-method="store_credit" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Voucher / Credit Number</label>
                                            <input type="text" class="form-control" id="credit-reference"
                                                   placeholder="CREDIT123" maxlength="30">
                                            <small class="text-muted">Optional reference</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Notes -->
                                <div class="mb-3">
                                    <label class="form-label">Payment Notes (Optional)</label>
                                    <textarea class="form-control" id="payment-notes" rows="2"
                                              placeholder="Add payment notes..." maxlength="500"></textarea>
                                </div>

                                <!-- Amount Received -->
                                <div class="mb-3">
                                    <label for="amount-received-display" class="form-label fw-semibold">
                                        Amount Received
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text">{{ $currencySymbol }}</span>
                                        <input type="text" class="form-control text-end fs-4 fw-bold"
                                               id="amount-received-display" placeholder="0.00"
                                               inputmode="decimal" autocomplete="off">
                                    </div>
                                    <div class="invalid-feedback" id="amount-error"></div>
                                </div>

                                <!-- Change -->
                                <div class="card border-success border-2">
                                    <div class="card-body bg-success-subtle">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold fs-5">Change:</span>
                                            <span class="fs-4 fw-bold text-success" id="change-display">$0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Numeric Keypad -->
                            <div class="col-lg-7">
                                <!-- Quick Amounts -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Quick Amounts</label>
                                    <div class="row g-2" id="quick-amounts-container">
                                        <!-- Dynamically generated -->
                                    </div>
                                </div>

                                <!-- Numeric Keypad -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Enter Amount</label>
                                    <div class="enterprise-keypad">
                                        <div class="keypad-grid">
                                            <!-- Row 1 -->
                                            <button class="keypad-btn-enterprise" data-key="7">
                                                <span class="keypad-btn-number">7</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                            <button class="keypad-btn-enterprise" data-key="8">
                                                <span class="keypad-btn-number">8</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                            <button class="keypad-btn-enterprise" data-key="9">
                                                <span class="keypad-btn-number">9</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                            <button class="keypad-btn-enterprise keypad-btn-action" data-key="backspace">
                                                <i class="ti ti-backspace fs-20"></i>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>

                                            <!-- Row 2 -->
                                            <button class="keypad-btn-enterprise" data-key="4">
                                                <span class="keypad-btn-number">4</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                            <button class="keypad-btn-enterprise" data-key="5">
                                                <span class="keypad-btn-number">5</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                            <button class="keypad-btn-enterprise" data-key="6">
                                                <span class="keypad-btn-number">6</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                            <button class="keypad-btn-enterprise keypad-btn-action-danger" data-key="clear">
                                                <i class="ti ti-trash fs-20"></i>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>

                                            <!-- Row 3 -->
                                            <button class="keypad-btn-enterprise" data-key="1">
                                                <span class="keypad-btn-number">1</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                            <button class="keypad-btn-enterprise" data-key="2">
                                                <span class="keypad-btn-number">2</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                            <button class="keypad-btn-enterprise" data-key="3">
                                                <span class="keypad-btn-number">3</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                            <button class="keypad-btn-enterprise keypad-btn-exact-amount" id="exact-amount-btn">
                                                <span class="keypad-btn-number">OK</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>

                                            <!-- Row 4 -->
                                            <button class="keypad-btn-enterprise keypad-btn-zero" data-key="0">
                                                <span class="keypad-btn-number">0</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                            <button class="keypad-btn-enterprise keypad-btn-period" data-key=".">
                                                <span class="keypad-btn-number">.</span>
                                                <span class="keypad-btn-ripple-effect"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Receipt Preview -->
                    <div id="payment-step-2" class="payment-step" style="display: none;">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title text-center mb-4">Receipt Preview</h5>

                                        <!-- Receipt Preview Content -->
                                        <div id="receipt-preview-content" class="receipt-preview">
                                            <!-- Dynamically generated receipt content -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <!-- Step 1 Footer -->
                    <div id="step-1-footer" class="w-100 d-flex justify-content-between">
                        <button type="button" class="btn btn-light" id="cancel-payment-btn">
                            <i class="ti ti-x me-1"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="preview-payment-btn">
                            <i class="ti ti-eye me-1"></i> Preview Receipt
                        </button>
                    </div>

                    <!-- Step 2 Footer -->
                    <div id="step-2-footer" class="w-100 d-flex justify-content-between" style="display: none;">
                        <button type="button" class="btn btn-light" id="back-to-payment-btn">
                            <i class="ti ti-arrow-left me-1"></i> Edit Payment
                        </button>
                        <button type="button" class="btn btn-success btn-lg" id="confirm-payment-btn">
                            <i class="ti ti-check me-1"></i> Confirm & Complete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Customer Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalLabel">
                        <i class="ti ti-user-plus me-2"></i>Add New Customer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quick-add-customer-form">
                    <div class="modal-body">
                        <!-- Error Alert (hidden by default) -->
                        <div class="alert alert-danger d-none" id="customer-error-alert">
                            <ul id="customer-error-list" class="mb-0"></ul>
                        </div>

                        <!-- First Name (Required) -->
                        <div class="mb-3">
                            <label for="customer-first-name" class="form-label">
                                First Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="customer-first-name"
                                   name="first_name" required maxlength="255">
                        </div>

                        <!-- Last Name (Required) -->
                        <div class="mb-3">
                            <label for="customer-last-name" class="form-label">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="customer-last-name"
                                   name="last_name" required maxlength="255">
                        </div>

                        <!-- Phone (Required) -->
                        <div class="mb-3">
                            <label for="customer-phone" class="form-label">
                                Phone Number <span class="text-danger">*</span>
                            </label>
                            <input type="tel" class="form-control" id="customer-phone"
                                   name="phone" required placeholder="0771234567">
                            <small class="form-text text-muted">Phone must be unique</small>
                        </div>

                        <!-- Email (Optional) -->
                        <div class="mb-3">
                            <label for="customer-email" class="form-label">Email (Optional)</label>
                            <input type="email" class="form-control" id="customer-email"
                                   name="email" placeholder="customer@example.com">
                        </div>

                        <!-- Gender (Optional) -->
                        <div class="mb-3">
                            <label for="customer-gender" class="form-label">Gender (Optional)</label>
                            <select class="form-select" id="customer-gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                                <option value="prefer_not_to_say">Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="save-customer-btn">
                            <i class="ti ti-check me-1"></i>Save Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/pos.js'])
    <script>
        // Business hours data (from settings)
        window.businessHours = @json($businessHours);

        // POS settings (from database)
        window.posSettings = @json($posSettings);
    </script>
@endsection