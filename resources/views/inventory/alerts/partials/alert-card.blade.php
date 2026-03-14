<tr class="{{ $alert->is_resolved ? 'table-light' : '' }}" data-alert-id="{{ $alert->id }}">
    <td class="ps-3">
        @if(!$alert->is_resolved)
            <div class="form-check">
                <input class="form-check-input alert-checkbox" type="checkbox" value="{{ $alert->id }}">
            </div>
        @endif
    </td>
    <td>{{ $alert->id }}</td>
    <td data-sort="sort-type">
        <span class="badge bg-{{ $alert->alert_type === 'low_stock' ? 'warning' : ($alert->alert_type === 'out_of_stock' ? 'danger' : ($alert->alert_type === 'expiring_soon' ? 'info' : 'secondary')) }}-subtle text-{{ $alert->alert_type === 'low_stock' ? 'warning' : ($alert->alert_type === 'out_of_stock' ? 'danger' : ($alert->alert_type === 'expiring_soon' ? 'info' : 'secondary')) }}">
            <i class="ti ti-{{ $alert->alert_type === 'low_stock' ? 'alert-triangle' : ($alert->alert_type === 'out_of_stock' ? 'x' : ($alert->alert_type === 'expiring_soon' ? 'clock' : 'calendar')) }} fs-xs"></i>
            {{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}
        </span>
    </td>
    <td data-sort="sort-severity">
        @if($alert->severity === 'critical')
            <span class="badge bg-danger-subtle text-danger">
                <i class="ti ti-alert-circle fs-xs"></i> Critical
            </span>
        @elseif($alert->severity === 'warning')
            <span class="badge bg-warning-subtle text-warning">
                <i class="ti ti-alert-triangle fs-xs"></i> Warning
            </span>
        @else
            <span class="badge bg-info-subtle text-info">
                <i class="ti ti-info-circle fs-xs"></i> Info
            </span>
        @endif
    </td>
    <td>
        <div class="alert-message" title="{{ $alert->message }}">
            {{ Str::limit($alert->message, 60) }}
        </div>
        @if($alert->is_resolved && $alert->resolved_at)
            <small class="text-muted d-block">Resolved: {{ $alert->resolved_at->format('M d, Y H:i') }}</small>
        @endif
    </td>
    <td>
        @if($alert->supply)
            <a href="{{ route('inventory.supplies.show', $alert->supply_id) }}" class="text-primary">
                {{ $alert->supply->name }}
            </a>
        @else
            <span class="text-muted">Supply deleted</span>
        @endif
    </td>
    <td data-sort="sort-stock">
        @if($alert->supply)
            <div class="d-flex align-items-center">
                <div class="me-2">
                    @include('inventory.supplies.partials.stock-badge', ['supply' => $alert->supply])
                </div>
                <small class="text-muted">
                    {{ $alert->current_stock }} / {{ $alert->min_stock_level }}
                </small>
            </div>
        @else
            <span class="text-muted">N/A</span>
        @endif
    </td>
    <td data-sort="sort-created">{{ $alert->created_at->format('d M, Y H:i') }}</td>
    <td data-sort="sort-status">
        @if($alert->is_resolved)
            <span class="badge bg-success-subtle text-success">
                <i class="ti ti-check fs-xs"></i> Resolved
            </span>
        @else
            <span class="badge bg-warning-subtle text-warning">
                <i class="ti ti-clock fs-xs"></i> Unresolved
            </span>
        @endif
    </td>
    <td class="text-center">
        <div class="d-flex justify-content-center gap-1">
            <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('inventory.alerts.show', $alert->id) }}" title="View Alert">
                <i class="ti ti-eye fs-lg"></i>
            </a>
            @if(!$alert->is_resolved)
                <button type="button" class="btn btn-success btn-icon btn-sm rounded-circle" 
                        data-bs-toggle="modal" 
                        data-bs-target="#resolveModal{{ $alert->id }}"
                        title="Resolve Alert">
                    <i class="ti ti-check fs-lg"></i>
                </button>
            @endif
            @can('inventory.alerts.manage')
                <form id="delete-form-{{ $alert->id }}" action="{{ route('inventory.alerts.destroy', $alert->id) }}" method="POST" style="display: none;">
                    @csrf
                    @method('DELETE')
                </form>
                <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                        onclick="confirmDelete('{{ $alert->id }}', 'Alert #{{ $alert->id }}')"
                        title="Delete Alert">
                    <i class="ti ti-trash fs-lg"></i>
                </button>
            @endcan
        </div>
    </td>
</tr>