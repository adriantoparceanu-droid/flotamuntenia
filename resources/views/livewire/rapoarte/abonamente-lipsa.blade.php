<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-exclamation-circle class="w-6 h-6 text-indigo-600" />
            Raport abonamente lipsa
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4 no-print">
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-amber-700 dark:text-amber-300">Adrese cu lipsuri</p>
                    <p class="text-xl font-semibold text-amber-900 dark:text-amber-100 tabular-nums">
                        {{ $totalAdrese }}
                    </p>
                </div>
                <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-md">
                    <p class="text-[11px] uppercase tracking-wider text-red-700 dark:text-red-300">Total luni lipsa</p>
                    <p class="text-xl font-semibold text-red-900 dark:text-red-100 tabular-nums">
                        {{ $totalLipsuri }}
                    </p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                {{-- Filtru luna --}}
                <div class="flex flex-col md:flex-row md:items-end gap-4 mb-4 no-print">
                    <div class="md:w-64">
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Verifica pana la luna</label>
                        <input type="month" wire:model.live="lunaSelectata"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        <p class="text-[11px] text-gray-400 mt-0.5">Default: luna curenta. Lipsurile se calculeaza de la prima luna abonament pana la luna selectata (inclusiv).</p>
                    </div>
                    <button type="button" wire:click="reseteazaFiltre"
                            class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600">
                        <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                        Reseteaza
                    </button>
                </div>

                @if(count($randuri) === 0)
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-check-badge class="w-12 h-12 mx-auto mb-2 text-emerald-300" />
                        <p class="text-sm">Toate adresele cu abonament au comenzi pe lunile verificate. Nicio lipsa detectata.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Client / Adresa</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Tip abonament</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300 hidden md:table-cell">Prima luna</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Nr. lipsuri</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Luni lipsa</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300 no-print">Actiuni</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @foreach($randuri as $r)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                        <td class="px-3 py-2">
                                            <a href="{{ route('clienti.detalii', $r['client']->id) }}" wire:navigate
                                               class="text-gray-900 dark:text-gray-100 font-medium hover:text-indigo-600">
                                                {{ $r['client']?->denumire ?? '—' }}
                                            </a>
                                            <span class="block text-xs text-gray-500">{{ $r['adresa']->denumire }}</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $this->culoareTipAbonament($r['tipAbonament']) }}">
                                                {{ $this->etichetaTipAbonament($r['tipAbonament']) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-gray-500 hidden md:table-cell">
                                            {{ $this->formatLuna($r['primaLuna']) }}
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                                {{ $r['numarLipsuri'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($r['lipsuri'] as $luna)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800">
                                                        {{ $this->formatLuna($luna) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap no-print">
                                            @php $primaLipsa = $r['lipsuri'][0]; @endphp
                                            <a href="{{ route('comenzi.noua', ['id_adresa' => $r['adresa']->id, 'tip' => 'abonament', 'luna' => str_replace('/', '-', $primaLipsa)]) }}"
                                               wire:navigate
                                               title="Creeaza comanda abonament pentru {{ $this->formatLuna($primaLipsa) }}"
                                               class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs rounded">
                                                <x-heroicon-m-plus class="w-3 h-3" />
                                                Creeaza ({{ $this->formatLuna($primaLipsa) }})
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                        <strong>Lipsa</strong> = lipseste comanda cu <code>tip_comanda='abonament'</code> si <code>luna_livrata='YYYY/MM'</code> pentru acea adresa.
                        Iterare luna cu luna de la prima <code>luna_livrata</code> din istoric pana la luna selectata.
                        <strong>Bidoane</strong> (sky) = livrare fizica 19L/11L; <strong>Filtre</strong> (amber) = doar facturare lunara.
                        Click pe „Creeaza" deschide formularul de comanda noua precompletat cu adresa, tip abonament si luna primei restante.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
