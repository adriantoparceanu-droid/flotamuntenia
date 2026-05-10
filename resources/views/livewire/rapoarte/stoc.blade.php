<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-chart-pie class="w-6 h-6 text-indigo-600" />
            Raport stoc curent
        </h2>
    </x-slot>

    {{-- Stiluri print: ascundem filtrele si sidebar la print --}}
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; }
        }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-rapoarte.tabs />

            {{-- Card-uri sumar pe produsele cheie --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-4 no-print">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-blue-700 dark:text-blue-300">APA 19L</p>
                    <p class="text-xl font-semibold text-blue-900 dark:text-blue-100 tabular-nums">
                        {{ $sumar['apa19']['total'] }} <span class="text-xs font-normal text-blue-700">buc total firma</span>
                    </p>
                    <p class="text-[11px] text-blue-700 dark:text-blue-300 mt-0.5">
                        Depozit: {{ $sumar['apa19']['sold'] }} / Custodie: {{ $sumar['apa19']['custodie'] }}
                    </p>
                </div>
                <div class="p-3 bg-cyan-50 dark:bg-cyan-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-cyan-700 dark:text-cyan-300">APA 11L</p>
                    <p class="text-xl font-semibold text-cyan-900 dark:text-cyan-100 tabular-nums">
                        {{ $sumar['apa11']['total'] }} <span class="text-xs font-normal text-cyan-700">buc total firma</span>
                    </p>
                    <p class="text-[11px] text-cyan-700 dark:text-cyan-300 mt-0.5">
                        Depozit: {{ $sumar['apa11']['sold'] }} / Custodie: {{ $sumar['apa11']['custodie'] }}
                    </p>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-emerald-700 dark:text-emerald-300">DOZATOR PODEA</p>
                    <p class="text-xl font-semibold text-emerald-900 dark:text-emerald-100 tabular-nums">
                        {{ $sumar['dozatorPodea']['total'] }} <span class="text-xs font-normal text-emerald-700">buc</span>
                    </p>
                    <p class="text-[11px] text-emerald-700 dark:text-emerald-300 mt-0.5">
                        Depozit: {{ $sumar['dozatorPodea']['sold'] }} / Custodie: {{ $sumar['dozatorPodea']['custodie'] }}
                    </p>
                </div>
                <div class="p-3 bg-violet-50 dark:bg-violet-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-violet-700 dark:text-violet-300">DOZATOR CUSTODIE</p>
                    <p class="text-xl font-semibold text-violet-900 dark:text-violet-100 tabular-nums">
                        {{ $sumar['dozatorCustodie']['total'] }} <span class="text-xs font-normal text-violet-700">buc</span>
                    </p>
                    <p class="text-[11px] text-violet-700 dark:text-violet-300 mt-0.5">
                        Depozit: {{ $sumar['dozatorCustodie']['sold'] }} / Custodie: {{ $sumar['dozatorCustodie']['custodie'] }}
                    </p>
                </div>
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-amber-700 dark:text-amber-300">DOZATOR FILTRU</p>
                    <p class="text-xl font-semibold text-amber-900 dark:text-amber-100 tabular-nums">
                        {{ $sumar['dozatorFiltru']['total'] }} <span class="text-xs font-normal text-amber-700">buc</span>
                    </p>
                    <p class="text-[11px] text-amber-700 dark:text-amber-300 mt-0.5">
                        Depozit: {{ $sumar['dozatorFiltru']['sold'] }} / Custodie: {{ $sumar['dozatorFiltru']['custodie'] }}
                    </p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                {{-- Filtre --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 no-print">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Cauta produs</label>
                        <div class="relative mt-1">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                            <input type="text" wire:model.live.debounce.300ms="cautare"
                                   placeholder="Denumire produs..."
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
                            Reseteaza filtre
                        </button>
                    </div>
                </div>

                {{-- Selector depozite --}}
                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-md no-print">
                    <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 mb-2">Depozite afisate</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach($depoziteToate as $d)
                            @php $selectat = in_array($d->id, $depoziteSelectate); @endphp
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox"
                                       {{ $selectat ? 'checked' : '' }}
                                       wire:click="comutaDepozit({{ $d->id }})"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-xs {{ $selectat ? 'text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-500' }}">
                                    {{ $d->denumire }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Tabel matrice --}}
                @if(count($randuri) === 0)
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-archive-box class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                        <p class="text-sm">Nu exista produse cu mişcari de stoc pentru depozitele si filtrele alese.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                {{-- Header 1: depozit names + Total general --}}
                                <tr class="bg-gray-100 dark:bg-gray-900">
                                    <th rowspan="2" class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300 align-bottom border-b-2 border-gray-300 dark:border-gray-700">
                                        Produs
                                    </th>
                                    <th rowspan="2" class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300 align-bottom border-b-2 border-gray-300 dark:border-gray-700 hidden md:table-cell">
                                        Categorie
                                    </th>
                                    @foreach($depoziteVizibile as $d)
                                        <th colspan="3" class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-300 border-l border-b border-gray-300 dark:border-gray-700">
                                            {{ $d->denumire }}
                                        </th>
                                    @endforeach
                                    <th colspan="3" class="px-3 py-2 text-center font-bold text-indigo-700 dark:text-indigo-300 border-l border-b border-gray-300 dark:border-gray-700 bg-indigo-50 dark:bg-indigo-900/20">
                                        Total firma
                                    </th>
                                </tr>
                                {{-- Header 2: sub-coloane Sold/Custodie/Total --}}
                                <tr class="bg-gray-50 dark:bg-gray-900 text-[11px]">
                                    @foreach($depoziteVizibile as $d)
                                        <th class="px-2 py-1.5 text-right font-medium text-gray-600 dark:text-gray-400 border-l border-b-2 border-gray-300 dark:border-gray-700"
                                            title="Sold = ce e fizic in depozit (IN − OUT − CUSTODIE)">Sold</th>
                                        <th class="px-2 py-1.5 text-right font-medium text-gray-600 dark:text-gray-400 border-b-2 border-gray-300 dark:border-gray-700"
                                            title="Custodie = la clienti, urmaribil">Custodie</th>
                                        <th class="px-2 py-1.5 text-right font-semibold text-gray-700 dark:text-gray-300 border-b-2 border-gray-300 dark:border-gray-700"
                                            title="Total = Sold + Custodie">Total</th>
                                    @endforeach
                                    <th class="px-2 py-1.5 text-right font-medium text-indigo-700 dark:text-indigo-300 border-l border-b-2 border-indigo-300 bg-indigo-50 dark:bg-indigo-900/20">Sold</th>
                                    <th class="px-2 py-1.5 text-right font-medium text-indigo-700 dark:text-indigo-300 border-b-2 border-indigo-300 bg-indigo-50 dark:bg-indigo-900/20">Custodie</th>
                                    <th class="px-2 py-1.5 text-right font-bold text-indigo-700 dark:text-indigo-300 border-b-2 border-indigo-300 bg-indigo-50 dark:bg-indigo-900/20">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @foreach($randuri as $r)
                                    @php $p = $r['produs']; @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                            {{ $p->denumire }}
                                            <span class="block text-[10px] text-gray-400">#{{ $p->id }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-500 text-xs hidden md:table-cell">
                                            {{ $p->categorie?->denumire ?? '—' }}
                                        </td>
                                        @foreach($depoziteVizibile as $d)
                                            @php $cell = $r['celule'][$d->id]; @endphp
                                            <td class="px-2 py-2 text-right tabular-nums text-xs border-l border-gray-200 dark:border-gray-700 {{ $cell['sold'] < 0 ? 'text-red-600 font-semibold' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $cell['sold'] }}
                                            </td>
                                            <td class="px-2 py-2 text-right tabular-nums text-xs {{ $cell['custodie'] > 0 ? 'text-violet-600 font-medium' : 'text-gray-400' }}">
                                                {{ $cell['custodie'] }}
                                            </td>
                                            <td class="px-2 py-2 text-right tabular-nums text-xs font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $cell['total'] }}
                                            </td>
                                        @endforeach
                                        {{-- Total firma per produs --}}
                                        <td class="px-2 py-2 text-right tabular-nums text-xs border-l border-indigo-200 bg-indigo-50/30 dark:bg-indigo-900/10 {{ $r['totalProdusSold'] < 0 ? 'text-red-600 font-semibold' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ $r['totalProdusSold'] }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs bg-indigo-50/30 dark:bg-indigo-900/10 {{ $r['totalProdusCustodie'] > 0 ? 'text-violet-600 font-medium' : 'text-gray-400' }}">
                                            {{ $r['totalProdusCustodie'] }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50/30 dark:bg-indigo-900/10">
                                            {{ $r['totalProdus'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 dark:bg-gray-900 font-semibold border-t-2 border-gray-300 dark:border-gray-700">
                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100">Total coloana</td>
                                    <td class="px-3 py-2 hidden md:table-cell"></td>
                                    @php $totalGenSold = 0; $totalGenCustodie = 0; @endphp
                                    @foreach($depoziteVizibile as $d)
                                        @php
                                            $tc = $totaluriColoane[$d->id];
                                            $totalGenSold += $tc['sold'];
                                            $totalGenCustodie += $tc['custodie'];
                                        @endphp
                                        <td class="px-2 py-2 text-right tabular-nums text-xs border-l border-gray-300 dark:border-gray-700 {{ $tc['sold'] < 0 ? 'text-red-600' : '' }}">{{ $tc['sold'] }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs {{ $tc['custodie'] > 0 ? 'text-violet-600' : 'text-gray-400' }}">{{ $tc['custodie'] }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums text-xs font-bold">{{ $tc['total'] }}</td>
                                    @endforeach
                                    <td class="px-2 py-2 text-right tabular-nums text-xs border-l border-indigo-300 bg-indigo-100 dark:bg-indigo-900/30 {{ $totalGenSold < 0 ? 'text-red-600' : 'text-indigo-700 dark:text-indigo-300' }}">{{ $totalGenSold }}</td>
                                    <td class="px-2 py-2 text-right tabular-nums text-xs bg-indigo-100 dark:bg-indigo-900/30 {{ $totalGenCustodie > 0 ? 'text-violet-600' : 'text-gray-400' }}">{{ $totalGenCustodie }}</td>
                                    <td class="px-2 py-2 text-right tabular-nums text-sm font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/30">{{ $totalGeneralFirma }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                        <strong>Sold</strong> = ce e fizic in depozit (IN − OUT − CUSTODIE).
                        <strong>Custodie</strong> = la clienti, urmaribil.
                        <strong>Total</strong> = Sold + Custodie = ce e inca al firmei.
                        Sold negativ (rosu) indica vanzari/iesiri fara intrari corespunzatoare — verifica miscarile pe acel produs.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
