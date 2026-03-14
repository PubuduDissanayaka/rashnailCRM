@php
    $statusColors = [
        'draft' => 'secondary',
        'pending' => 'warning',
        'ordered' => 'info',
        'partial' => 'primary',
        'received' => 'success',
        'cancelled' => 'danger',
    ];
    
    $statusIcons = [
        'draft' => 'ti-file-draft',
        'pending' => 'ti-clock',
        'ordered' => 'ti-shopping-cart',
        'partial' => 'ti-package',
        'received' => 'ti-check',
        'cancelled' => 'ti-x',
    ];
    
    $statusLabels = [
        'draft' => 'Draft',
        'pending' => 'Pending Approval',
        'ordered' => 'Ordered',
        'partial' => 'Partially Received',
        'received' => 'Fully Received',
        'cancelled' => 'Cancelled',
    ];
    
    $color = $statusColors[$status] ?? 'secondary';
    $icon = $statusIcons[$status] ?? 'ti-file';
    $label = $statusLabels[$status] ?? ucfirst($status);
@endphp

<span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
    <i class="ti {{ $icon }} fs-xs me-1"></i> {{ $label }}
</span>