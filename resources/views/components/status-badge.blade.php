@props(['status'])

@php
    $colors = match($status) {
        'new'        => 'bg-yellow-100 text-yellow-800',
        'dispatched', 'en_route' => 'bg-blue-100 text-blue-800',
        'on_scene'   => 'bg-purple-100 text-purple-800',
        'completed'  => 'bg-green-100 text-green-800',
        'cancelled'  => 'bg-red-100 text-red-800',
        default      => 'bg-gray-100 text-gray-800',
    };
    $label = \App\Models\ServiceRequest::STATUS_LABELS[$status] ?? ucwords(str_replace('_', ' ', $status));
@endphp

<span {{ $attributes->merge(['class' => "inline-block px-2 py-0.5 rounded-full text-xs font-semibold $colors"]) }}>
    {{ $label }}
</span>
