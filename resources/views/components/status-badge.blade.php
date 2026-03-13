@props(['status'])

@php
    $badgeClass = match($status) {
        'new'        => 'badge-new',
        'dispatched' => 'badge-dispatched',
        'en_route'   => 'badge-en-route',
        'on_scene'   => 'badge-on-scene',
        'completed'  => 'badge-completed',
        'cancelled'  => 'badge-cancelled',
        'invoiced'   => 'badge-invoiced',
        default      => 'badge-default',
    };
    $label = \App\Models\ServiceRequest::STATUS_LABELS[$status] ?? ucwords(str_replace('_', ' ', $status));
@endphp

<span {{ $attributes->merge(['class' => "badge-crystal $badgeClass"]) }}>
    {{ $label }}
</span>
