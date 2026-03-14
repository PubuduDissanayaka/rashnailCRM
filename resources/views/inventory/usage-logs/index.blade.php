@extends('layouts.vertical')

@section('title', 'Usage Logs')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('inventory.supplies.index') }}">Inventory</a></li>
                        <li class="breadcrumb-item active">Usage Logs</li>
                    </ol>
                </div>
                <h4 class="page-title">Usage Logs</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="header-title">Usage History</h4>
                                @can('inventory.usage.create')
                                    <a href="{{ route('inventory.usage-logs.create') }}" class="btn btn-primary">
                                        <i class="ri-add-line me-1"></i> Log Usage
                                    </a>
                                @endcan
                            </div>
                            <p class="text-muted">Track supply usage from appointments and manual logs</p>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Filters</h5>
                                    <form id="filter-form">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="supply_id" class="form-label">Supply</label>
                                                    <select class="form-select" id="supply_id" name="supply_id">
                                                        <option value="">All Supplies</option>
                                                        @foreach($supplies as $supply)
                                                            <option value="{{ $supply->id }}">{{ $supply->name }} ({{ $supply->sku }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="service_id" class="form-label">Service</label>
                                                    <select class="form-select" id="service_id" name="service_id">
                                                        <option value="">All Services</option>
                                                        @foreach($services as $service)
                                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="used_by" class="form-label">Staff</label>
                                                    <select class="form-select" id="used_by" name="used_by">
                                                        <option value="">All Staff</option>
                                                        @foreach($staff as $user)
                                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="date_from" class="form-label">Date From</label>
                                                    <input type="date" class="form-control" id="date_from" name="date_from">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="date_to" class="form-label">Date To</label>
                                                    <input type="date" class="form-control" id="date_to" name="date_to">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <button type="button" class="btn btn-primary" id="apply-filters">Apply Filters</button>
                                                <button type="button" class="btn btn-secondary" id="reset-filters">Reset</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="table-responsive">
                        <table id="usage-logs-datatable" class="table table-striped dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Supply</th>
                                    <th>Quantity Used</th>
                                    <th>Unit Cost</th>
                                    <th>Total Cost</th>
                                    <th>Appointment</th>
                                    <th>Service</th>
                                    <th>Staff</th>
                                    <th>Customer</th>
                                    <th>Used At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#usage-logs-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('inventory.usage-logs.index') }}",
                    data: function(d) {
                        d.supply_id = $('#supply_id').val();
                        d.service_id = $('#service_id').val();
                        d.used_by = $('#used_by').val();
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'supply_name', name: 'supply.name' },
                    { data: 'quantity_used', name: 'quantity_used' },
                    { data: 'unit_cost', name: 'unit_cost' },
                    { data: 'total_cost_formatted', name: 'total_cost' },
                    { data: 'appointment_reference', name: 'appointment.slug', orderable: false },
                    { data: 'service_name', name: 'service.name' },
                    { data: 'staff_name', name: 'user.name' },
                    { data: 'customer_name', name: 'customer.name' },
                    { data: 'used_at_formatted', name: 'used_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                language: {
                    paginate: {
                        previous: "<i class='ri-arrow-left-s-line'></i>",
                        next: "<i class='ri-arrow-right-s-line'></i>"
                    }
                },
                drawCallback: function() {
                    $(".dataTables_paginate > .pagination").addClass("pagination-rounded");
                }
            });

            // Apply filters
            $('#apply-filters').click(function() {
                table.ajax.reload();
            });

            // Reset filters
            $('#reset-filters').click(function() {
                $('#filter-form')[0].reset();
                table.ajax.reload();
            });

            // Initialize date inputs with default values (last 30 days)
            var today = new Date();
            var lastMonth = new Date();
            lastMonth.setDate(today.getDate() - 30);
            
            $('#date_from').val(lastMonth.toISOString().split('T')[0]);
            $('#date_to').val(today.toISOString().split('T')[0]);
        });
    </script>
@endsection