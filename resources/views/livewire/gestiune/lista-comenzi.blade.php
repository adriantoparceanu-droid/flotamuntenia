<div>
    @php
        $culoareTip = function (string $tipCod) {
            return match ($tipCod) {
                'abonament'          => 'bg-indigo-100 text-indigo-700',
                'consum suplimentar' => 'bg-amber-100 text-amber-700',
                'rapida'             => 'bg-amber-100 text-amber-600',
                'problema'           => 'bg-rose-100 text-rose-700',
                default              => 'bg-gray-100 text-gray-600',
            };
        };
    @endphp

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
                    {{-- Navigare data --}}
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

                    {{-- Filtru masina --}}
                    <select wire:model.live="filtruMasina"
                            class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm py-1.5">
                        <option value="">Toate masinile</option>
                        <option value="0">Doar nealocate</option>
                        @foreach($masini as $m)
                            <option value="{{ $m->id }}">{{ $m->denumire }}</option>
                        @endforeach
                    </select>

                    {{-- Filtru depozit --}}
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

            {{-- Tabel 1: Observatii --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <x-heroicon-m-chat-bubble-left-ellipsis class="w-4 h-4 text-amber-500" />
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Observatii comenzi
                    </h3>
                    @if($cuObservatii->isNotEmpty())
                        <span class="ml-auto text-xs text-gray-500">{{ $cuObservatii->count() }} {{ $cuObservatii->count() === 1 ? 'comanda' : 'comenzi' }} cu observatii</span>
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
                                    <th class="px-3 py-2 text-left w-24">Tip</th>
                                    <th class="px-3 py-2 text-left">Nume client / Destinatar</th>
                                    <th class="px-3 py-2 text-left">Adresa</th>
                                    <th class="px-3 py-2 text-left">Observatii</th>
                                    <th class="px-3 py-2 text-left w-36">Masina</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($cuObservatii as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-3 py-2 align-top">
                                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium {{ $culoareTip($item['tip_cod']) }}">
                                                {{ $item['tip'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 align-top font-medium text-gray-800 dark:text-gray-100">
                                            {{ $item['nume'] }}
                                        </td>
                                        <td class="px-3 py-2 align-top text-xs text-gray-500 max-w-xs">
                                            <div class="truncate" title="{{ $item['adresa'] }}">
                                                {{ $item['adresa'] ?: '—' }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300 max-w-sm">
                                            <div class="whitespace-pre-line text-sm">{{ $item['obs'] }}</div>
                                        </td>
                                        <td class="px-3 py-2 align-top text-xs text-gray-500">
                                            {{ $item['masina'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Tabel 2: Sumar produse --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <x-heroicon-m-cube class="w-4 h-4 text-indigo-500" />
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Sumar produse de livrat
                    </h3>
                    @if($sumarProduse->isNotEmpty())
                        <span class="ml-auto text-xs text-gray-500">
                            Total: {{ $sumarProduse->sum('cantitate') }} buc
                        </span>
                    @endif
                </div>

                @if($sumarProduse->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-gray-400 italic">
                        Nicio comanda cu produse pentru aceasta zi / filtru selectat.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 text-[11px] uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-2 text-left">Produs</th>
                                    <th class="px-4 py-2 text-right w-32">Cantitate totala</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($sumarProduse as $produs)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-gray-100">
                                            {{ $produs['denumire'] }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right tabular-nums font-bold text-indigo-700 dark:text-indigo-400 text-base">
                                            {{ $produs['cantitate'] }}
                                            <span class="text-xs font-normal text-gray-400">buc</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-900/50 border-t-2 border-gray-200 dark:border-gray-600">
                                <tr>
                                    <td class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">TOTAL</td>
                                    <td class="px-4 py-2 text-right tabular-nums font-bold text-gray-900 dark:text-gray-100 text-base">
                                        {{ $sumarProduse->sum('cantitate') }}
                                        <span class="text-xs font-normal text-gray-400">buc</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
