@extends('layouts.vertical', ['title' => 'System Settings'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
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
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content">
                        @include('settings.partials.business')
                        @include('settings.partials.appointment')
                        @include('settings.partials.notification')
                        @include('settings.partials.payment')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/settings.js', 'node_modules/choices.js/public/assets/scripts/choices.min.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
@endsection