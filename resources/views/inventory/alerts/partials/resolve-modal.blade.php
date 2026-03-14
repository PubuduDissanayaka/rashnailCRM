<div class="modal fade" id="resolveModal{{ $alert->id }}" tabindex="-1" aria-labelledby="resolveModalLabel{{ $alert->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.alerts.resolve', $alert->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="resolveModalLabel{{ $alert->id }}">Resolve Alert #{{ $alert->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <div class="me-2">
                                <i class="ti ti-info-circle fs-lg"></i>
                            </div>
                            <div>
                                <h6 class="alert-heading mb-1">{{ $alert->message }}</h6>
                                <p class="mb-0">
                                    <strong>Supply:</strong> {{ $alert->supply->name ?? 'Unknown' }}<br>
                                    <strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}<br>
                                    <strong>Severity:</strong> {{ ucfirst($alert->severity) }}<br>
                                    <strong>Created:</strong> {{ $alert->created_at->format('d M, Y H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resolution_notes{{ $alert->id }}" class="form-label">Resolution Notes (Optional)</label>
                        <textarea class="form-control" id="resolution_notes{{ $alert->id }}" name="resolution_notes" rows="3" placeholder="Add any notes about how this alert was resolved..."></textarea>
                        <div class="form-text">
                            These notes will be appended to the alert message for future reference.
                        </div>
                    </div>
                    
                    @if($alert->alert_type === 'low_stock' || $alert->alert_type === 'out_of_stock')
                        <div class="alert alert-warning">
                            <div class="d-flex">
                                <div class="me-2">
                                    <i class="ti ti-alert-triangle fs-lg"></i>
                                </div>
                                <div>
                                    <h6 class="alert-heading mb-1">Stock Issue Detected</h6>
                                    <p class="mb-0">
                                        Consider creating a purchase order to replenish stock.
                                        <a href="{{ route('inventory.purchase-orders.create') }}?supply_id={{ $alert->supply_id }}" class="alert-link">
                                            Create Purchase Order
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Resolve</button>
                </div>
            </form>
        </div>
    </div>
</div>