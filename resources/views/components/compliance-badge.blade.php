@props(['status'])

@php
    $label = match($status) {
        'expired' => 'Expired',
        'expiring' => 'Expiring',
        'valid' => 'Valid',
        'clear' => 'Clear',
        'pending' => 'Pending',
        'failed' => 'Failed',
        default => null,
    };

    $badgeClass = match($status) {
        'expired', 'failed' => 'badge-expired',
        'expiring', 'pending' => 'badge-expiring',
        'valid', 'clear' => 'badge-valid',
        default => '',
    };
@endphp

@if($label)
    <span class="badge-crystal {{ $badgeClass }}">
        {{ $label }}
    </span>
@else
    <span class="text-gray-500">—</span>
@endif
