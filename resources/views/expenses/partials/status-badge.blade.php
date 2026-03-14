@php
    $statusColors = [
        'draft' => 'secondary',
        'pending' => 'warning',
        'approved' => 'info',
        'rejected' => 'danger',
        'paid' => 'success'
    ];

    $statusIcons = [
        'draft' => 'ti-file-draft',
        'pending' => 'ti-clock',
        'approved' => 'ti-check',
        'rejected' => 'ti-x',
        'paid' => 'ti-cash'
    ];

    $statusTexts = [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'paid' => 'Paid'
    ];

    $color = $statusColors[$status] ?? 'secondary';
    $icon = $statusIcons[$status] ?? 'ti-circle';
    $text = $statusTexts[$status] ?? ucfirst($status);
@endphp

<span class="badge bg-{{ $color }}-subtle text-{{ $color }} d-inline-flex align-items-center">
    <i class="ti {{ $icon }} fs-xs me-1"></i>
    {{ $text }}
</span>