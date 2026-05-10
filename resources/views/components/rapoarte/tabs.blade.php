{{-- Strip de tab-uri pentru navigare intre rapoartele financiare (Faza 5.2+).
     Folosit ca <x-rapoarte.tabs /> in view-urile rapoartelor active. --}}
@php
    $tabs = [
        ['route' => 'rapoarte.stoc', 'label' => 'Stoc curent', 'icon' => 'archive-box'],
        ['route' => 'rapoarte.cheltuieli-vanzari', 'label' => 'Cheltuieli vs vanzari', 'icon' => 'arrows-right-left'],
        ['route' => 'rapoarte.abonamente-lipsa', 'label' => 'Abonamente lipsa', 'icon' => 'exclamation-circle'],
        ['route' => 'rapoarte.financiar-bidoane', 'label' => 'Financiar bidoane', 'icon' => 'banknotes'],
    ];
@endphp

<div class="flex flex-wrap gap-1 mb-4 border-b border-gray-200 dark:border-gray-700 no-print">
    @foreach($tabs as $tab)
        @php $activ = request()->routeIs($tab['route']); @endphp
        <a href="{{ route($tab['route']) }}" wire:navigate
           class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium border-b-2 -mb-px transition
                  {{ $activ
                      ? 'border-indigo-600 text-indigo-700 dark:text-indigo-300'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
            <x-dynamic-component :component="'heroicon-o-' . $tab['icon']" class="w-4 h-4" />
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
