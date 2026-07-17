@extends('layouts.vertical', ['title' => 'System Settings'])

@section('css')
    <style>
        /* Unsaved-dot indicator */
        .tab-dirty::after {
            content: '';
            display: inline-block;
            width: 8px; height: 8px;
            background: #f59e0b;
            border-radius: 50%;
            margin-left: 4px;
            vertical-align: super;
        }
        .tab-error::after {
            background: #ef4444;
            content: attr(data-errors) !important;
            width: auto; height: auto;
            padding: 0 4px;
            font-size: 9px;
            line-height: 14px;
            border-radius: 10px;
            color: #fff;
        }
        .setting-save-btn .spinner-border {
            width: 14px; height: 14px;
            border-width: 2px;
        }
        .nav-tabs { flex-wrap: nowrap; overflow-x: auto; white-space: nowrap; }
        .nav-tabs .nav-link { font-size: 0.85rem; padding: 0.5rem 0.75rem; }
        @media (max-width: 576px) {
            .nav-tabs { flex-wrap: wrap; }
            .nav-tabs .nav-link { font-size: 0.8rem; padding: 0.4rem 0.5rem; }
        }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', [
        'title' => 'System Settings',
        'subtitle' => 'Configure your business settings and preferences'
    ])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#business-tab">
                                <i class="ti ti-building-store me-1"></i> Business & Branding
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#appointment-tab">
                                <i class="ti ti-calendar me-1"></i> Appointment Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#notification-tab">
                                <i class="ti ti-bell me-1"></i> Notifications & Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#payment-tab">
                                <i class="ti ti-credit-card me-1"></i> Payment & Billing
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#attendance-tab">
                                <i class="ti ti-clock-check me-1"></i> Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#security-tab">
                                <i class="ti ti-shield-lock me-1"></i> Security
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#pos-tab">
                                <i class="ti ti-shopping-cart me-1"></i> POS
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content">
                        @include('settings.partials.business')
                        @include('settings.partials.appointment')
                        @include('settings.partials.notification')
                        @include('settings.partials.payment')
                        @include('settings.partials.attendance')
                        @include('settings.partials.security')
                        @include('settings.partials.pos')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/settings.js', ])
@endsection