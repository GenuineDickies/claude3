@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'input-crystal shadow-xs']) }}>
