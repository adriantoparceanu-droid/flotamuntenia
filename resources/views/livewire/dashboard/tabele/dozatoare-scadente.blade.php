<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
        <x-heroicon-o-wrench-screwdriver class="w-5 h-5 text-rose-500" />
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            Dozatoare scadente urgent — top 10
        </h3>
    </div>

    @if($randuri->isEmpty())
        <div class="px-4 py-8 text-center text-sm text-gray-500">
            <x-heroicon-o-check-circle class="w-10 h-10 mx-auto mb-2 text-emerald-300" />
            Niciun dozator scadent in fereastra critica.
        </div>
    @else
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($randuri as $r)
                <li class="px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-start gap-2.5 min-w-0 flex-1">
                            {{-- Icon tip dozator --}}
                            <div class="flex-shrink-0 p-1.5 rounded-md
                                        {{ $r->culoare_tip === 'cyan' ? 'bg-cyan-100 text-cyan-600' : 'bg-amber-100 text-amber-600' }}">
                                <x-dynamic-component :component="'heroicon-o-' . $r->icon" class="w-4 h-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                @if($r->id_client)
                                    <a href="{{ $r->href }}" wire:navigate
                                       class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-rose-700 truncate block">
                                        {{ $r->client }}
                                    </a>
                                @else
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $r->client }}</span>
                                @endif
                                <p class="text-xs text-gray-500 truncate" title="{{ $r->adresa }}">
                                    {{ $r->adresa }}@if($r->serie) <span class="text-gray-400">· {{ $r->serie }}</span>@endif
                                </p>
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            @if($r->zile < 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    <x-heroicon-m-exclamation-triangle class="w-3 h-3" />
                                    Expirat {{ abs($r->zile) }}z
                                </span>
                            @elseif($r->zile <= 7)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                    <x-heroicon-m-clock class="w-3 h-3" />
                                    {{ $r->zile === 0 ? 'Azi' : "{$r->zile}z ramase" }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ $r->zile }}z ramase
                                </span>
                            @endif
                            <div class="text-[10px] text-gray-400 mt-0.5">{{ $r->data_scadenta->format('d.m.Y') }}</div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
