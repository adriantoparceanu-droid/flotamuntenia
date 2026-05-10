@props(['active' => false, 'href', 'icon' => null])

@php
$classes = $active
    ? 'bg-gray-800 text-white'
    : 'text-gray-300 hover:bg-gray-800 hover:text-white';
@endphp

<a href="{{ $href }}"
   {{ $active ? '' : 'wire:navigate' }}
   {{ $attributes->merge(['class' => "flex items-center gap-3 px-3 py-2 rounded text-sm font-medium transition $classes"]) }}>
    @if ($icon)
        <x-dynamic-component :component="'heroicon-o-' . $icon" class="w-5 h-5 flex-shrink-0" />
    @else
        <span class="w-5 h-5 flex-shrink-0"></span>
    @endif
    <span class="flex-1 flex items-center justify-between gap-2">{{ $slot }}</span>
</a>
