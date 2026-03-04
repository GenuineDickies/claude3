@php
    $colors = match ($status) {
        'draft'  => 'bg-gray-100 text-gray-700',
        'posted' => 'bg-green-100 text-green-700',
        'void'   => 'bg-red-100 text-red-700',
        default  => 'bg-gray-100 text-gray-600',
    };
@endphp
<span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $colors }}">
    {{ ucfirst($status) }}
</span>
