<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-arrows-right-left class="w-6 h-6 text-indigo-600" />
            Raport cheltuieli vs vanzari
        </h2>
    </x-slot>

    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; }
        }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-rapoarte.tabs />

            {{-- Card-uri sumar perioada --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 no-print">
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-amber-700 dark:text-amber-300">Total achizitii (IN)</p>
                    <p class="text-xl font-semibold text-amber-900 dark:text-amber-100 tabular-nums">
                        {{ number_format($totalIn, 2, ',', '.') }} lei
                    </p>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Total vanzari (OUT)</p>
                    <p class="text-xl font-semibold text-emerald-900 dark:text-emerald-100 tabular-nums">
                        {{ number_format($totalOut, 2, ',', '.') }} lei
                    </p>
                </div>
                <div class="p-3 rounded-md {{ $profitTotal >= 0 ? 'bg-indigo-50 dark:bg-indigo-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                    <p class="text-[11px] uppercase tracking-wider {{ $profitTotal >= 0 ? 'text-indigo-700 dark:text-indigo-300' : 'text-red-700 dark:text-red-300' }}">
                        Profit perioada
                    </p>
                    <p class="text-xl font-semibold tabular-nums {{ $profitTotal >= 0 ? 'text-indigo-900 dark:text-indigo-100' : 'text-red-900 dark:text-red-100' }}">
                        {{ number_format($profitTotal, 2, ',', '.') }} lei
                    </p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                {{-- Filtre --}}
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4 no-print">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Data de la</label>
                        <input type="date" wire:model.live="deLa"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Data pana la</label>
                        <input type="date" wire:model.live="panaLa"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Cauta produs</label>
                        <div class="relative mt-1">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                            <input type="text" wire:model.live.debounce.300ms="cautare"
                                   placeholder="Denumire..."
                                   class="pl-9 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Categorie</label>
                        <select wire:model.live="filtruCategorie"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="">Toate</option>
                            @foreach($categorii as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->denumire }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" wire:click="reseteazaFiltre"
                                class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600">
                            <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                            Reseteaza
                        </button>
                    </div>
                </div>

                {{-- Tabel principal --}}
                @if(count($randuri) === 0)
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-arrows-right-left class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                        <p class="text-sm">Nu exista produse cu activitate (achizitii sau vanzari livrate) in intervalul ales.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="w-8 px-2 py-2"></th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Produs</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300 hidden md:table-cell">Categorie</th>
                                    <th class="px-2 py-2 text-right font-medium text-amber-700 dark:text-amber-300 border-l border-gray-300 dark:border-gray-700">Cant IN</th>
                                    <th class="px-2 py-2 text-right font-medium text-amber-700 dark:text-amber-300">Suma IN</th>
                                    <th class="px-2 py-2 text-right font-medium text-emerald-700 dark:text-emerald-300 border-l border-gray-300 dark:border-gray-700">Cant OUT</th>
                                    <th class="px-2 py-2 text-right font-medium text-emerald-700 dark:text-emerald-300">Suma OUT</th>
                                    <th class="px-2 py-2 text-right font-medium text-gray-600 dark:text-gray-300 border-l border-gray-300 dark:border-gray-700">Dif. cant.</th>
                                    <th class="px-2 py-2 text-right font-medium text-indigo-700 dark:text-indigo-300">Profit</th>
                                    <th class="px-2 py-2 text-right font-medium text-indigo-700 dark:text-indigo-300">Profit %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @foreach($randuri as $r)
                                    @php $p = $r['produs']; $hasSubs = count($r['subRanduri']) > 1; @endphp
                                    <tr x-data="{ open: false }">
                                        <td class="px-2 py-2 text-center">
                                            @if($hasSubs)
                                                <button type="button" x-on:click="open = !open"
                                                        class="text-gray-500 hover:text-indigo-600">
                                                    <x-heroicon-m-chevron-right class="w-4 h-4 transition-transform" x-bind:class="open ? 'rotate-90' : ''" />
                                                </button>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                            {{ $p->denumire }}
                                            <span class="block text-[10px] text-gray-400">#{{ $p->id }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-500 text-xs hidden md:table-cell">
                                            {{ $p->categorie?->denumire ?? '—' }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs border-l border-gray-200 dark:border-gray-700">
                                            {{ $r['cantIn'] }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs text-amber-700 dark:text-amber-300 font-medium">
                                            {{ number_format($r['sumaIn'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs border-l border-gray-200 dark:border-gray-700">
                                            {{ $r['cantOut'] }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs text-emerald-700 dark:text-emerald-300 font-medium">
                                            {{ number_format($r['sumaOut'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs border-l border-gray-200 dark:border-gray-700 {{ $r['difCant'] < 0 ? 'text-red-600 font-semibold' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ $r['difCant'] }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs font-bold {{ $r['profit'] >= 0 ? 'text-indigo-700 dark:text-indigo-300' : 'text-red-600' }}">
                                            {{ number_format($r['profit'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs font-semibold {{ ($r['profitPct'] ?? 0) >= 0 ? 'text-indigo-700 dark:text-indigo-300' : 'text-red-600' }}">
                                            {{ $r['profitPct'] !== null ? number_format($r['profitPct'], 1, ',', '.') . '%' : '—' }}
                                        </td>
                                    </tr>

                                    {{-- Sub-randuri lunare --}}
                                    @foreach($r['subRanduri'] as $sub)
                                        <tr x-show="open" x-cloak class="bg-gray-50/50 dark:bg-gray-900/30 text-xs"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100">
                                            <td class="px-2 py-1.5"></td>
                                            <td class="px-3 py-1.5 text-gray-500 italic" colspan="{{ 'md:table-cell' ? 1 : 2 }}">
                                                ↳ {{ $sub['lunaEticheta'] }}
                                            </td>
                                            <td class="px-3 py-1.5 hidden md:table-cell"></td>
                                            <td class="px-2 py-1.5 text-right tabular-nums border-l border-gray-200 dark:border-gray-700 text-gray-600">{{ $sub['cantIn'] }}</td>
                                            <td class="px-2 py-1.5 text-right tabular-nums text-amber-600">{{ number_format($sub['sumaIn'], 2, ',', '.') }}</td>
                                            <td class="px-2 py-1.5 text-right tabular-nums border-l border-gray-200 dark:border-gray-700 text-gray-600">{{ $sub['cantOut'] }}</td>
                                            <td class="px-2 py-1.5 text-right tabular-nums text-emerald-600">{{ number_format($sub['sumaOut'], 2, ',', '.') }}</td>
                                            <td class="px-2 py-1.5 text-right tabular-nums border-l border-gray-200 dark:border-gray-700"></td>
                                            <td class="px-2 py-1.5 text-right tabular-nums {{ $sub['profit'] >= 0 ? 'text-indigo-600' : 'text-red-600' }}">{{ number_format($sub['profit'], 2, ',', '.') }}</td>
                                            <td class="px-2 py-1.5"></td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 dark:bg-gray-900 font-semibold border-t-2 border-gray-300 dark:border-gray-700">
                                    <td colspan="3" class="px-3 py-2 text-gray-900 dark:text-gray-100">Total perioada</td>
                                    <td class="px-2 py-2 border-l border-gray-300 dark:border-gray-700"></td>
                                    <td class="px-2 py-2 text-right tabular-nums text-amber-700 dark:text-amber-300">{{ number_format($totalIn, 2, ',', '.') }}</td>
                                    <td class="px-2 py-2 border-l border-gray-300 dark:border-gray-700"></td>
                                    <td class="px-2 py-2 text-right tabular-nums text-emerald-700 dark:text-emerald-300">{{ number_format($totalOut, 2, ',', '.') }}</td>
                                    <td class="px-2 py-2 border-l border-gray-300 dark:border-gray-700"></td>
                                    <td class="px-2 py-2 text-right tabular-nums {{ $profitTotal >= 0 ? 'text-indigo-700 dark:text-indigo-300' : 'text-red-600' }}">{{ number_format($profitTotal, 2, ',', '.') }}</td>
                                    <td class="px-2 py-2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                        <strong>IN</strong> = achizitii (facturi cheltuieli din interval).
                        <strong>OUT</strong> = vanzari livrate (comenzi + comenzi rapide cu <code>livrat=1</code> in interval).
                        <strong>Dif. cant.</strong> = IN − OUT (pozitiv = stoc acumulat in perioada; negativ = vanzari peste achizitii).
                        <strong>Profit %</strong> raportat la suma achizitiilor; „—" daca admin n-are achizitii pentru produsul respectiv.
                        Click pe sageata din primul col pentru drill-down lunar.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
