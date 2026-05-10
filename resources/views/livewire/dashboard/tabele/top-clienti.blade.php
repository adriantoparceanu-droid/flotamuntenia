<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
        <x-heroicon-o-trophy class="w-5 h-5 text-purple-500" />
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Top 10 clienti — luna curenta</h3>
    </div>

    @if($clienti->isEmpty())
        <div class="px-4 py-8 text-center text-sm text-gray-500">
            <x-heroicon-o-users class="w-10 h-10 mx-auto mb-2 text-gray-300" />
            Niciun client cu comenzi luna curenta inca.
        </div>
    @else
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($clienti as $idx => $c)
                <li class="px-4 py-2 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/50">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-purple-100 text-purple-700 text-xs font-semibold flex items-center justify-center">{{ $idx + 1 }}</span>
                        <a href="{{ route('clienti.detalii', ['client' => $c->id]) }}" wire:navigate
                           class="text-sm text-gray-900 dark:text-gray-100 hover:text-purple-700 truncate">
                            {{ $c->denumire }}
                        </a>
                    </div>
                    <span class="flex-shrink-0 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ $c->comenzi_count }} <span class="text-xs text-gray-400">comenzi</span>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
