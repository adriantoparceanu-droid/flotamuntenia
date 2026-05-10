<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
        <x-heroicon-o-archive-box class="w-5 h-5 text-amber-500" />
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            Recipienti de recuperat — top 10 adrese
        </h3>
    </div>

    @if($randuri->isEmpty())
        <div class="px-4 py-8 text-center text-sm text-gray-500">
            <x-heroicon-o-archive-box class="w-10 h-10 mx-auto mb-2 text-gray-300" />
            Nicio adresa cu sold pozitiv mai mare de 5 bidoane.
        </div>
    @else
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($randuri as $r)
                <li class="px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            @if($r->id_client)
                                <a href="{{ route('clienti.detalii', ['client' => $r->id_client]) }}?tab=recipienti" wire:navigate
                                   class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-amber-700 truncate block">
                                    {{ $r->client }}
                                </a>
                            @else
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $r->client }}</span>
                            @endif
                            <p class="text-xs text-gray-500 truncate" title="{{ $r->adresa_text }}">{{ $r->adresa_text }}</p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <div class="text-sm font-semibold text-amber-700">{{ $r->total }} <span class="text-xs font-normal text-gray-400">total</span></div>
                            <div class="text-[11px] text-gray-500">
                                @if($r->sold19l > 0) <span>{{ $r->sold19l }}×19L</span> @endif
                                @if($r->sold19l > 0 && $r->sold11l > 0) <span class="text-gray-300">·</span> @endif
                                @if($r->sold11l > 0) <span>{{ $r->sold11l }}×11L</span> @endif
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
