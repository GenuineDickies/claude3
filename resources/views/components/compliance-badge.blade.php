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

    $classes = match($status) {
        'expired', 'failed' => 'bg-red-100 text-red-800',
        'expiring', 'pending' => 'bg-amber-100 text-amber-800',
        'valid', 'clear' => 'bg-green-100 text-green-800',
        default => '',
    };
@endphp

@if($label)
    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $classes }}">
        {{ $label }}
    </span>
@else
    <span class="text-gray-400">—</span>
@endif
