<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-indigo-600" />
            Comenzi — Gestiune
            <span class="text-sm font-normal text-gray-500">
                {{ \Carbon\Carbon::parse($data)->locale('ro')->isoFormat('dddd, D MMMM YYYY') }}
            </span>
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">

            {{-- Toolbar filtre --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-3">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="navigheazaZi(-1)"
                                class="inline-flex items-center justify-center p-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-md">
                            <x-heroicon-m-chevron-left class="w-4 h-4" />
                        </button>
                        <input type="date" wire:model.live="data"
                               class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm py-1.5" />
                        <button type="button" wire:click="navigheazaZi(1)"
                                class="inline-flex items-center justify-center p-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-md">
                            <x-heroicon-m-chevron-right class="w-4 h-4" />
                        </button>
                    </div>

                    <select wire:model.live="filtruMasina"
                            class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm py-1.5">
                        <option value="">Toate masinile</option>
                        <option value="0">Doar nealocate</option>
                        @foreach($masini as $m)
                            <option value="{{ $m->id }}">{{ $m->denumire }}</option>
                        @endforeach
                    </select>

                    @if($depozite->count() > 1)
                        <select wire:model.live="idDepozit"
                                class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm py-1.5">
                            <option value="">Toate depozitele</option>
                            @foreach($depozite as $d)
                                <option value="{{ $d->id }}">{{ $d->denumire }}</option>
                            @endforeach
                        </select>
                    @endif

                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ $totalComenzi }} {{ $totalComenzi === 1 ? 'comanda' : 'comenzi' }}
                    </span>
                </div>
            </div>

            {{-- Caseta Observatii — titlu = labelul filtrului de masina --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <x-heroicon-m-chat-bubble-left-ellipsis class="w-4 h-4 text-amber-500" />
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ $labelFiltruMasina }}
                    </h3>
                    @if($cuObservatii->isNotEmpty())
                        <span class="ml-auto text-xs text-gray-400">
                            {{ $cuObservatii->count() }} {{ $cuObservatii->count() === 1 ? 'observatie' : 'observatii' }}
                        </span>
                    @endif
                </div>

                @if($cuObservatii->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-gray-400 italic">
                        Nicio comanda cu observatii pentru aceasta zi / filtru selectat.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 text-[11px] uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-2 text-left w-56">Nume client / Destinatar</th>
                                    <th class="px-4 py-2 text-left">Observatii</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($cuObservatii as $item)
                                    <tr class="hover:bg-amber-50/40 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-2.5 align-top font-medium text-gray-800 dark:text-gray-100 whitespace-nowrap">
                                            {{ $item['nume'] }}
                                        </td>
                                        <td class="px-4 py-2.5 align-top text-gray-700 dark:text-gray-300">
                                            <div class="whitespace-pre-line">{{ $item['obs'] }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Sumar produse in taburi (un tab per masina + tab Total) --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden"
                 x-data="{ tab: 'total' }">

                {{-- Header cu tab-uri --}}
                <div class="border-b border-gray-100 dark:border-gray-700 flex items-center gap-0 overflow-x-auto">
                    {{-- Tab Total --}}
                    <button type="button"
                            @click="tab = 'total'"
                            :class="tab === 'total'
                                ? 'border-b-2 border-indigo-600 text-indigo-700 dark:text-indigo-400 bg-indigo-50/60 dark:bg-indigo-900/20'
                                : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30'"
                            class="flex items-center gap-1.5 px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                        <x-heroicon-m-cube class="w-4 h-4" />
                        Total
                        @if(!empty($produseTotale))
                            <span class="ml-1 text-xs tabular-nums text-gray-400">({{ array_sum($produseTotale) }})</span>
                        @endif
                    </button>

                    {{-- Tab per masina --}}
                    @foreach($produsePerMasina as $grup)
                        <button type="button"
                                @click="tab = '{{ $grup['key'] }}'"
                                :class="tab === '{{ $grup['key'] }}'
                                    ? 'border-b-2 border-indigo-600 text-indigo-700 dark:text-indigo-400 bg-indigo-50/60 dark:bg-indigo-900/20'
                                    : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30'"
                                class="flex items-center gap-1.5 px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                            <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0"
                                  style="background-color: {{ $grup['culoare'] }}"></span>
                            {{ $grup['denumire'] }}
                            <span class="ml-1 text-xs tabular-nums text-gray-400">({{ $grup['total'] }})</span>
                        </button>
                    @endforeach
                </div>

                {{-- Continut tab Total --}}
                <div x-show="tab === 'total'" x-cloak>
                    @if(empty($produseTotale))
                        <div class="px-4 py-8 text-center text-sm text-gray-400 italic">
                            Nicio comanda cu produse pentru aceasta zi / filtru selectat.
                        </div>
                    @else
                        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            @foreach($produseTotale as $denumire => $cantitate)
                                <div class="flex flex-col items-center justify-center bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 gap-1 text-center">
                                    <span class="text-3xl font-bold tabular-nums text-indigo-700 dark:text-indigo-300">
                                        {{ $cantitate }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 leading-tight">{{ $denumire }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Continut tab per masina --}}
                @foreach($produsePerMasina as $grup)
                    <div x-show="tab === '{{ $grup['key'] }}'" x-cloak>
                        @if(empty($grup['produse']))
                            <div class="px-4 py-8 text-center text-sm text-gray-400 italic">
                                Nicio comanda cu produse pentru aceasta masina.
                            </div>
                        @else
                            <div class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                @foreach($grup['produse'] as $denumire => $cantitate)
                                    <div class="flex flex-col items-center justify-center rounded-xl p-4 gap-1 text-center"
                                         style="background-color: {{ $grup['culoare'] }}1a;">
                                        <span class="text-3xl font-bold tabular-nums"
                                              style="color: {{ $grup['culoare'] }};">
                                            {{ $cantitate }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 leading-tight">{{ $denumire }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

            </div>

            {{-- Sumar zi: produse + financiar --}}
            @if($totalComenzi > 0)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                        <x-heroicon-o-calculator class="w-4 h-4 text-gray-400" />
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Sumar zi</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">

                        {{-- Produse --}}
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">Produse livrate</p>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <th class="text-left pb-1.5 font-medium text-gray-500 dark:text-gray-400">Produs</th>
                                        <th class="text-right pb-1.5 font-medium text-gray-500 dark:text-gray-400 w-20">Cant.</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    @forelse($produseTotale as $denumire => $cantitate)
                                        <tr>
                                            <td class="py-1.5 text-gray-700 dark:text-gray-300">{{ $denumire }}</td>
                                            <td class="py-1.5 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $cantitate }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="py-3 text-gray-400 dark:text-gray-500 italic text-xs">Niciun produs pe comenzile din aceasta zi.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if(!empty($produseTotale))
                                    <tfoot>
                                        <tr class="border-t border-gray-200 dark:border-gray-600">
                                            <td class="pt-2 text-xs text-gray-500 dark:text-gray-400">Total bucăți</td>
                                            <td class="pt-2 text-right font-bold tabular-nums text-gray-800 dark:text-gray-200">{{ array_sum($produseTotale) }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>

                        {{-- Financiar --}}
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">Sumar financiar</p>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <th class="text-left pb-1.5 font-medium text-gray-500 dark:text-gray-400">Modalitate</th>
                                        <th class="text-right pb-1.5 font-medium text-gray-500 dark:text-gray-400 w-36">Sumă</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    <tr>
                                        <td class="py-1.5 text-gray-700 dark:text-gray-300">Cash</td>
                                        <td class="py-1.5 text-right tabular-nums font-semibold text-emerald-700 dark:text-emerald-400">{{ number_format($totalPePlata[1] ?? 0, 2, ',', '.') }} lei</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 text-gray-700 dark:text-gray-300">OP</td>
                                        <td class="py-1.5 text-right tabular-nums font-semibold text-blue-700 dark:text-blue-400">{{ number_format($totalPePlata[2] ?? 0, 2, ',', '.') }} lei</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 text-gray-700 dark:text-gray-300">Card</td>
                                        <td class="py-1.5 text-right tabular-nums font-semibold text-purple-700 dark:text-purple-400">{{ number_format($totalPePlata[3] ?? 0, 2, ',', '.') }} lei</td>
                                    </tr>
                                    @if(($totalPePlata[4] ?? 0) > 0)
                                        <tr>
                                            <td class="py-1.5 text-gray-700 dark:text-gray-300">Altă</td>
                                            <td class="py-1.5 text-right tabular-nums font-semibold text-gray-700 dark:text-gray-300">{{ number_format($totalPePlata[4], 2, ',', '.') }} lei</td>
                                        </tr>
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-gray-200 dark:border-gray-600">
                                        <td class="pt-2 font-bold text-gray-800 dark:text-gray-200">Total</td>
                                        <td class="pt-2 text-right font-bold tabular-nums text-gray-900 dark:text-gray-100 text-base">{{ number_format($totalGlobal, 2, ',', '.') }} lei</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
