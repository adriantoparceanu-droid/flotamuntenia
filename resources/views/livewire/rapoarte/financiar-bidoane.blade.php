<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-banknotes class="w-6 h-6 text-indigo-600" />
            Raport financiar bidoane
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

            {{-- Card-uri sumar --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 no-print">
                <div class="p-3 bg-sky-50 dark:bg-sky-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-sky-700 dark:text-sky-300">Bidoane 19L</p>
                    <p class="text-xl font-semibold text-sky-900 dark:text-sky-100 tabular-nums">{{ number_format($totalNr19l) }}</p>
                </div>
                <div class="p-3 bg-cyan-50 dark:bg-cyan-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-cyan-700 dark:text-cyan-300">Bidoane 11L</p>
                    <p class="text-xl font-semibold text-cyan-900 dark:text-cyan-100 tabular-nums">{{ number_format($totalNr11l) }}</p>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Total venit</p>
                    <p class="text-xl font-semibold text-emerald-900 dark:text-emerald-100 tabular-nums">{{ number_format($totalGeneral, 2, ',', '.') }} lei</p>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-gray-600 dark:text-gray-400">Linii raport</p>
                    <p class="text-xl font-semibold text-gray-800 dark:text-gray-100 tabular-nums">{{ count($randuri) }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                {{-- Filtre --}}
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4 no-print items-end">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">De la</label>
                        <input type="date" wire:model.live="deLa"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Pana la</label>
                        <input type="date" wire:model.live="panaLa"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Modalitate plata</label>
                        <select wire:model.live="idModalitatePlata"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="0">Toate</option>
                            <option value="1">Cash</option>
                            <option value="2">Ordin de plata</option>
                            <option value="3">Card</option>
                            <option value="4">Alta</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Achitat</label>
                        <select wire:model.live="achitat"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="-1">Toate</option>
                            <option value="1">Da</option>
                            <option value="0">Nu</option>
                        </select>
                    </div>
                    <div>
                        <button type="button" wire:click="reseteazaFiltre"
                                class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600">
                            <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                            Reseteaza
                        </button>
                    </div>
                </div>

                @if(count($randuri) === 0)
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-document-magnifying-glass class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                        <p class="text-sm">Nu exista date pentru intervalul si filtrele selectate.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th rowspan="2" class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300 align-bottom">Ziua</th>
                                    <th rowspan="2" class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300 align-bottom">Sursa</th>
                                    <th colspan="2" class="px-3 py-2 text-center font-medium text-gray-600 dark:text-gray-300 border-l border-gray-200 dark:border-gray-700">
                                        Bidoane livrate
                                    </th>
                                    <th colspan="4" class="px-3 py-2 text-center font-medium text-gray-600 dark:text-gray-300 border-l border-gray-200 dark:border-gray-700">
                                        Bani incasati (lei)
                                    </th>
                                    <th rowspan="2" class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300 align-bottom border-l border-gray-200 dark:border-gray-700">
                                        Total zi
                                    </th>
                                </tr>
                                <tr>
                                    <th class="px-3 py-2 text-right text-[11px] font-medium text-sky-700 dark:text-sky-300 border-l border-gray-200 dark:border-gray-700">19L</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-medium text-cyan-700 dark:text-cyan-300">11L</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-medium text-gray-600 dark:text-gray-300 border-l border-gray-200 dark:border-gray-700">Cash</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-medium text-gray-600 dark:text-gray-300">OP</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-medium text-gray-600 dark:text-gray-300">Card</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-medium text-gray-600 dark:text-gray-300">Alta</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @foreach($randuri as $r)
                                    @php
                                        $totalRand = $r['sumaCash'] + $r['sumaOp'] + $r['sumaCard'] + $r['sumaAlta'];
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-3 py-2 whitespace-nowrap text-gray-900 dark:text-gray-100">
                                            {{ $this->formatData($r['zi']) }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $this->culoareSursa($r['sursa']) }}">
                                                {{ $this->etichetaSursa($r['sursa']) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums text-sky-700 dark:text-sky-300 border-l border-gray-100 dark:border-gray-800">
                                            {{ $r['nr19l'] ?: '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums text-cyan-700 dark:text-cyan-300">
                                            {{ $r['nr11l'] ?: '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums border-l border-gray-100 dark:border-gray-800">
                                            {{ $r['sumaCash'] > 0 ? number_format($r['sumaCash'], 2, ',', '.') : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums">
                                            {{ $r['sumaOp'] > 0 ? number_format($r['sumaOp'], 2, ',', '.') : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums">
                                            {{ $r['sumaCard'] > 0 ? number_format($r['sumaCard'], 2, ',', '.') : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums">
                                            {{ $r['sumaAlta'] > 0 ? number_format($r['sumaAlta'], 2, ',', '.') : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums font-medium text-emerald-700 dark:text-emerald-300 border-l border-gray-100 dark:border-gray-800">
                                            {{ number_format($totalRand, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-900 font-semibold">
                                <tr>
                                    <td colspan="2" class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">Totaluri</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-sky-800 dark:text-sky-200 border-l border-gray-200 dark:border-gray-700">
                                        {{ number_format($totalNr19l) }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-cyan-800 dark:text-cyan-200">
                                        {{ number_format($totalNr11l) }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-800 dark:text-gray-100 border-l border-gray-200 dark:border-gray-700">
                                        {{ number_format($totalCash, 2, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-800 dark:text-gray-100">
                                        {{ number_format($totalOp, 2, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-800 dark:text-gray-100">
                                        {{ number_format($totalCard, 2, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-800 dark:text-gray-100">
                                        {{ number_format($totalAlta, 2, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-emerald-800 dark:text-emerald-200 border-l border-gray-200 dark:border-gray-700">
                                        {{ number_format($totalGeneral, 2, ',', '.') }} lei
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                        Raportul agregheaza pe zi <strong>Comenzile clasice</strong>, <strong>Comenzile rapide</strong> si <strong>Problemele/Interventiile</strong> din intervalul selectat.
                        <strong>19L</strong> = produs id {{ \App\Livewire\Rapoarte\FinanciarBidoane::ID_BIDON_19L }} (APA PLATA 19L); <strong>11L</strong> = produs id {{ \App\Livewire\Rapoarte\FinanciarBidoane::ID_BIDON_11L }} (APA PLATA 11L).
                        Comenzile rapide nu au coloana de modalitate plata in noua schema → suma lor intra implicit pe coloana <em>Alta</em>; daca filtrul este pe o modalitate specifica (Cash/OP/Card), ele se exclud.
                        Problemele nu contin bidoane, doar suma de incasat.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
