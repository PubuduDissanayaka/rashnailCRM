@php
    $percentage = $supply->max_stock_level > 0 ? ($supply->current_stock / $supply->max_stock_level) * 100 : 0;
    
    if ($supply->isOutOfStock()) {
        $badgeClass = 'bg-danger-subtle text-danger';
        $icon = 'ti ti-circle-x';
        $text = 'Out of Stock';
    } elseif ($supply->isLowStock()) {
        $badgeClass = 'bg-warning-subtle text-warning';
        $icon = 'ti ti-alert-triangle';
        $text = 'Low Stock';
    } elseif ($percentage >= 80) {
        $badgeClass = 'bg-success-subtle text-success';
        $icon = 'ti ti-circle-check';
        $text = 'High Stock';
    } else {
        $badgeClass = 'bg-info-subtle text-info';
        $icon = 'ti ti-circle-check';
        $text = 'In Stock';
    }
@endphp

<span class="badge {{ $badgeClass }}">
    <i class="{{ $icon }} fs-xs"></i> {{ $text }}
</span>

@if($supply->max_stock_level > 0)
<div class="progress mt-1" style="height: 4px;">
    <div class="progress-bar {{ $supply->isLowStock() ? 'bg-warning' : ($supply->isOutOfStock() ? 'bg-danger' : 'bg-success') }}" 
         role="progressbar" 
         style="width: {{ min($percentage, 100) }}%;" 
         aria-valuenow="{{ $percentage }}" 
         aria-valuemin="0" 
         aria-valuemax="100">
    </div>
</div>
<small class="text-muted">{{ number_format($supply->current_stock, 2) }} / {{ number_format($supply->max_stock_level, 2) }} {{ $supply->unit_type }}</small>
@else
<small class="text-muted">{{ number_format($supply->current_stock, 2) }} {{ $supply->unit_type }}</small>
@endif