<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-banknotes class="w-6 h-6 text-indigo-600" />
            Cheltuieli
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                @if (session('mesaj'))
                    <div class="mb-4 px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                        <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                        {{ session('mesaj') }}
                    </div>
                @endif
                @if (session('eroare'))
                    <div class="mb-4 px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm flex items-center gap-2">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-red-500 flex-shrink-0" />
                        {{ session('eroare') }}
                    </div>
                @endif

                {{-- Sume agregate pe filtrele active --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-md">
                        <p class="text-[11px] uppercase tracking-wider text-indigo-700 dark:text-indigo-300">Total perioada</p>
                        <p class="text-xl font-semibold text-indigo-900 dark:text-indigo-100 tabular-nums">
                            {{ number_format($sumaTotala, 2, ',', '.') }} lei
                        </p>
                    </div>
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-md">
                        <p class="text-[11px] uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Achitat</p>
                        <p class="text-xl font-semibold text-emerald-900 dark:text-emerald-100 tabular-nums">
                            {{ number_format($sumaAchitata, 2, ',', '.') }} lei
                        </p>
                    </div>
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-md">
                        <p class="text-[11px] uppercase tracking-wider text-amber-700 dark:text-amber-300">De achitat</p>
                        <p class="text-xl font-semibold text-amber-900 dark:text-amber-100 tabular-nums">
                            {{ number_format($sumaNeachitata, 2, ',', '.') }} lei
                        </p>
                    </div>
                </div>

                {{-- Bara actiuni --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                    <div class="relative w-full lg:w-96">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                        <input type="text" wire:model.live.debounce.300ms="cautare"
                               placeholder="Cauta dupa numar factura, furnizor sau ID..."
                               class="pl-9 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm w-full" />
                    </div>

                    <a href="{{ route('cheltuieli.noua') }}" wire:navigate
                       class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        Factura noua
                    </a>
                </div>

                {{-- Filtre --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-md">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Data de la</label>
                        <input type="date" wire:model.live="deLa"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Data pana la</label>
                        <input type="date" wire:model.live="panaLa"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Depozit</label>
                        <select wire:model.live="filtruDepozit"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                            <option value="">Toate</option>
                            @foreach($depozite as $d)
                                <option value="{{ $d->id }}">{{ $d->denumire }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Achitat</label>
                        <select wire:model.live="filtruAchitat"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                            <option value="toate">Toate</option>
                            <option value="achitat">Achitat</option>
                            <option value="neachitat">Neachitat</option>
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

                {{-- Tabel --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">#</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Nr. factura</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Furnizor</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Depozit</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Data</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Total</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Linii</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Achitat</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse($cheltuieli as $c)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs">#{{ $c->id }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $c->nr_factura }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $c->furnizor }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 text-xs">{{ $c->depozit?->denumire ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap text-xs">
                                        {{ $c->data?->format('d.m.Y') ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums font-semibold">
                                        {{ number_format((float) $c->total, 2, ',', '.') }} lei
                                    </td>
                                    <td class="px-3 py-2 text-center text-xs text-gray-500">
                                        {{ $c->produse_count }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <button type="button" wire:click="comutaAchitat({{ $c->id }})"
                                                title="{{ $c->achitat ? 'Marcheaza ca neachitat' : 'Marcheaza ca achitat' }}"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs {{ $c->achitat ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-amber-100 text-amber-700 hover:bg-amber-200' }}">
                                            @if($c->achitat)
                                                <x-heroicon-m-check-circle class="w-3 h-3" />
                                                Achitat
                                            @else
                                                <x-heroicon-m-x-circle class="w-3 h-3" />
                                                Neachitat
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-3">
                                            <a href="{{ route('cheltuieli.editare', $c->id) }}" wire:navigate
                                               title="Editeaza factura"
                                               aria-label="Editeaza factura"
                                               class="text-indigo-600 hover:text-indigo-800">
                                                <x-heroicon-o-pencil-square class="w-5 h-5" />
                                            </a>
                                            <button type="button" wire:click="deschideModalStergere({{ $c->id }})"
                                                    title="Sterge factura (revertaza miscarile de stoc)"
                                                    aria-label="Sterge factura"
                                                    class="text-red-600 hover:text-red-800">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-12 text-center">
                                        <x-heroicon-o-banknotes class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                        <p class="text-sm text-gray-500">Nu exista facturi de cheltuieli care sa corespunda filtrelor.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $cheltuieli->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal stergere --}}
    <div x-data="{ deschis: @entangle('modalStergere') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalStergere()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideModalStergere()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-md sm:mx-auto p-6">
            <div class="flex items-start gap-3">
                <div class="bg-red-100 dark:bg-red-900/30 rounded-md p-2">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Confirma stergerea</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Factura <span class="font-medium">{{ $denumireDeSters }}</span> va fi stearsa permanent impreuna cu liniile de produse.
                        Mişcarile de stoc IN generate vor fi reversate.
                    </p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" wire:click="inchideModalStergere"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                    <x-heroicon-m-x-mark class="w-4 h-4" />
                    Anuleaza
                </button>
                <button type="button" wire:click="confirmaStergere"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md">
                    <x-heroicon-m-trash class="w-4 h-4" />
                    Sterge
                </button>
            </div>
        </div>
    </div>
</div>
