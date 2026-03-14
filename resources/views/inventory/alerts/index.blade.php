@extends('layouts.vertical', ['title' => 'Inventory Alerts'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Alert Management'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Inventory Alerts</h4>
                        <p class="text-muted mb-0">Monitor and manage supply alerts</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search alerts..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="ti ti-filter me-1"></i> Filter
                        </button>
                        <button type="button" class="btn btn-success" id="bulkResolveBtn" disabled>
                            <i class="ti ti-check me-1"></i> Resolve Selected
                        </button>
                        <a href="{{ route('inventory.alerts.export', request()->query()) }}" class="btn btn-secondary">
                            <i class="ti ti-download me-1"></i> Export
                        </a>
                    </div>
                </div>
                <div class="card-body border-top border-light">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Alerts</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['unresolved'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Unresolved</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-danger fw-normal mt-0 text-truncate">{{ $stats['critical'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Critical</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-warning fw-normal mt-0 text-truncate">{{ $stats['warning'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Warning</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0" id="alertsTable">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th>ID</th>
                                <th data-table-sort="sort-type">Alert Type</th>
                                <th data-table-sort="sort-severity">Severity</th>
                                <th>Message</th>
                                <th data-table-sort="sort-supply">Supply</th>
                                <th data-table-sort="sort-stock">Stock Level</th>
                                <th data-table-sort="sort-created">Created</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alerts as $alert)
                                @include('inventory.alerts.partials.alert-card', ['alert' => $alert])
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-bell-off fs-24 mb-2 d-block"></i>
                                        No alerts found.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="alerts"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="GET" action="{{ route('inventory.alerts.index') }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterModalLabel">Filter Alerts</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Alert Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">All Types</option>
                                    @foreach($alertTypes as $key => $label)
                                        <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="severity" class="form-label">Severity</label>
                                <select class="form-select" id="severity" name="severity">
                                    <option value="">All Severities</option>
                                    @foreach($severities as $key => $label)
                                        <option value="{{ $key }}" {{ request('severity') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="unresolved" {{ request('status') == 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="supply_id" class="form-label">Supply</label>
                                <input type="text" class="form-control" id="supply_id" name="supply_id" value="{{ request('supply_id') }}" placeholder="Supply ID">
                            </div>
                            <div class="col-md-6">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="{{ route('inventory.alerts.index') }}" class="btn btn-outline-secondary">Clear Filters</a>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Resolve Modal -->
    <div class="modal fade" id="bulkResolveModal" tabindex="-1" aria-labelledby="bulkResolveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('inventory.alerts.bulk-resolve') }}" id="bulkResolveForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkResolveModalLabel">Resolve Selected Alerts</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>You are about to resolve <span id="selectedCount">0</span> alert(s).</p>
                        <div class="mb-3">
                            <label for="resolution_notes" class="form-label">Resolution Notes (Optional)</label>
                            <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="3" placeholder="Add any notes about the resolution..."></textarea>
                        </div>
                        <div id="selectedAlertsList" class="alert alert-info">
                            <small>Selected alerts will be listed here...</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm Resolve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Individual Resolve Modals -->
    @foreach($alerts as $alert)
        @if(!$alert->is_resolved)
            @include('inventory.alerts.partials.resolve-modal', ['alert' => $alert])
        @endif
    @endforeach
@endsection

@section('scripts')
    @vite(['resources/js/pages/inventory-alerts.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const alertCheckboxes = document.querySelectorAll('.alert-checkbox');
            const bulkResolveBtn = document.getElementById('bulkResolveBtn');
            const selectedCountSpan = document.getElementById('selectedCount');
            const selectedAlertsList = document.getElementById('selectedAlertsList');
            
            // Select all functionality
            selectAll.addEventListener('change', function() {
                const isChecked = this.checked;
                alertCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                updateBulkResolveButton();
            });
            
            // Individual checkbox change
            alertCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateBulkResolveButton();
                    updateSelectAllState();
                });
            });
            
            // Update bulk resolve button state
            function updateBulkResolveButton() {
                const checkedCount = document.querySelectorAll('.alert-checkbox:checked').length;
                bulkResolveBtn.disabled = checkedCount === 0;
                bulkResolveBtn.textContent = `Resolve Selected (${checkedCount})`;
            }
            
            // Update select all checkbox state
            function updateSelectAllState() {
                const totalCheckboxes = alertCheckboxes.length;
                const checkedCount = document.querySelectorAll('.alert-checkbox:checked').length;
                selectAll.checked = totalCheckboxes > 0 && checkedCount === totalCheckboxes;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < totalCheckboxes;
            }
            
            // Bulk resolve button click
            bulkResolveBtn.addEventListener('click', function() {
                const checkedCheckboxes = document.querySelectorAll('.alert-checkbox:checked');
                const alertIds = Array.from(checkedCheckboxes).map(cb => cb.value);
                
                // Update selected count
                selectedCountSpan.textContent = alertIds.length;
                
                // Update form with selected alert IDs
                const form = document.getElementById('bulkResolveForm');
                let alertIdsInput = form.querySelector('input[name="alert_ids[]"]');
                if (!alertIdsInput) {
                    alertIdsInput = document.createElement('input');
                    alertIdsInput.type = 'hidden';
                    alertIdsInput.name = 'alert_ids[]';
                    form.appendChild(alertIdsInput);
                }
                alertIdsInput.value = alertIds.join(',');
                
                // Show selected alerts list
                const alertNames = Array.from(checkedCheckboxes).map(cb => {
                    const row = cb.closest('tr');
                    return row.querySelector('.alert-message').textContent.trim();
                });
                
                selectedAlertsList.innerHTML = '';
                if (alertNames.length > 0) {
                    const ul = document.createElement('ul');
                    ul.className = 'mb-0';
                    alertNames.slice(0, 5).forEach(name => {
                        const li = document.createElement('li');
                        li.textContent = name.length > 50 ? name.substring(0, 50) + '...' : name;
                        ul.appendChild(li);
                    });
                    if (alertNames.length > 5) {
                        const li = document.createElement('li');
                        li.textContent = `... and ${alertNames.length - 5} more`;
                        ul.appendChild(li);
                    }
                    selectedAlertsList.appendChild(ul);
                }
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('bulkResolveModal'));
                modal.show();
            });
            
            // Initialize
            updateBulkResolveButton();
            updateSelectAllState();
        });
    </script>
@endsection