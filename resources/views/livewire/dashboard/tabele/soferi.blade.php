<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
        <x-heroicon-o-truck class="w-5 h-5 text-blue-500" />
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Performanta soferi — luna curenta</h3>
    </div>

    @if($statistici->isEmpty())
        <div class="px-4 py-8 text-center text-sm text-gray-500">
            <x-heroicon-o-truck class="w-10 h-10 mx-auto mb-2 text-gray-300" />
            Nicio masina cu comenzi luna curenta.
        </div>
    @else
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($statistici as $s)
                <li class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $s->denumire }}</span>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                            {{ $s->livrate }} <span class="text-gray-400 font-normal">/ {{ $s->asignate }}</span>
                        </span>
                    </div>
                    {{-- Progress bar --}}
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full rounded-full transition-all
                                    {{ $s->procent >= 90 ? 'bg-emerald-500' : ($s->procent >= 60 ? 'bg-blue-500' : 'bg-amber-500') }}"
                             style="width: {{ $s->procent }}%"></div>
                    </div>
                    <div class="text-right text-[10px] text-gray-400 mt-0.5">{{ $s->procent }}% livrate</div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
