@php
    $colors = match ($status) {
        'draft'  => 'bg-white/5 text-gray-300',
        'posted' => 'bg-green-100 text-green-700',
        'void'   => 'bg-red-100 text-red-700',
        default  => 'bg-white/5 text-gray-400',
    };
@endphp
<span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $colors }}">
    {{ ucfirst($status) }}
</span>
