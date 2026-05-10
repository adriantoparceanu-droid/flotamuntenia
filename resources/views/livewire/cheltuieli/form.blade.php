<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-banknotes class="w-6 h-6 text-indigo-600" />
            {{ $cheltuialaId ? 'Editeaza factura #' . $cheltuialaId : 'Factura noua de cheltuieli' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('eroare'))
                <div class="px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm flex items-center gap-2">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-red-500 flex-shrink-0" />
                    {{ session('eroare') }}
                </div>
            @endif

            <form wire:submit.prevent="salveaza" class="space-y-6">

                {{-- Card 1: Header factura --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-document-text class="w-5 h-5 text-indigo-600" />
                        Date factura
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Numar factura *</label>
                            <input type="text" wire:model="nrFactura" placeholder="Ex: FF12345"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('nrFactura') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Data factura *</label>
                            <input type="date" wire:model="data"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('data') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Furnizor *</label>
                            <input type="text" wire:model="furnizor" list="furnizori-sugerati"
                                   placeholder="Ex: SC Aqua Production SRL"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            <datalist id="furnizori-sugerati">
                                @foreach($furnizoriSugerati as $f)
                                    <option value="{{ $f }}"></option>
                                @endforeach
                            </datalist>
                            <p class="text-[11px] text-gray-400 mt-0.5">Sugestii din facturi anterioare. Poti scrie un furnizor nou.</p>
                            @error('furnizor') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Depozit destinatie *</label>
                            <select wire:model="idDepozit"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                <option value="">— Selecteaza —</option>
                                @foreach($depozite as $d)
                                    <option value="{{ $d->id }}">{{ $d->denumire }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-400 mt-0.5">Mişcarile de stoc IN se vor inregistra pe acest depozit.</p>
                            @error('idDepozit') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2 flex items-center gap-2">
                            <input type="checkbox" wire:model="achitat" id="achitat-flag"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <label for="achitat-flag" class="text-sm text-gray-700 dark:text-gray-300">
                                Factura achitata
                            </label>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Observatii</label>
                            <textarea wire:model="observatii" rows="2"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Linii produse --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100">
                            <x-heroicon-o-cube class="w-5 h-5 text-indigo-600" />
                            Produse achizitionate
                        </h3>
                        <button type="button" wire:click="adaugaLinieGoala"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs rounded-md">
                            <x-heroicon-m-plus class="w-3.5 h-3.5" />
                            Adauga linie
                        </button>
                    </div>

                    @error('linii') <p class="text-xs text-red-600 mb-2">{{ $message }}</p> @enderror

                    <div class="space-y-2">
                        @foreach($linii as $idx => $linie)
                            <div wire:key="linie-{{ $idx }}" class="grid grid-cols-12 gap-2 items-start p-2 bg-gray-50 dark:bg-gray-900 rounded-md">
                                <div class="col-span-12 md:col-span-7">
                                    <label class="block text-[11px] text-gray-500">Produs din catalog *</label>
                                    <select wire:model.live="linii.{{ $idx }}.id_produs"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                                        <option value="">— Selecteaza —</option>
                                        @foreach($produse as $p)
                                            <option value="{{ $p->id }}">{{ $p->denumire }}</option>
                                        @endforeach
                                    </select>
                                    @error("linii.{$idx}.id_produs") <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-4 md:col-span-1">
                                    <label class="block text-[11px] text-gray-500">Cant.</label>
                                    <input type="number" min="1" wire:model.live.debounce.300ms="linii.{{ $idx }}.cantitate"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs tabular-nums" />
                                    @error("linii.{$idx}.cantitate") <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-4 md:col-span-2">
                                    <label class="block text-[11px] text-gray-500">Pret unitar (lei)</label>
                                    <input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="linii.{{ $idx }}.pret"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs tabular-nums" />
                                    @error("linii.{$idx}.pret") <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-3 md:col-span-1">
                                    <label class="block text-[11px] text-gray-500">Subtotal</label>
                                    <div class="mt-1 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 tabular-nums text-right font-medium">
                                        {{ number_format((int) ($linie['cantitate'] ?? 0) * (float) ($linie['pret'] ?? 0), 2, ',', '.') }}
                                    </div>
                                </div>
                                <div class="col-span-1 md:col-span-1 flex items-end justify-end pb-1">
                                    <button type="button" wire:click="eliminaLinie({{ $idx }})"
                                            class="text-red-600 hover:text-red-800 inline-flex items-center justify-center w-7 h-7 rounded-md hover:bg-red-50">
                                        <x-heroicon-m-trash class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex justify-end">
                        <div class="text-right">
                            <span class="text-xs text-gray-500">Total factura (auto-calculat)</span>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100 tabular-nums">
                                {{ number_format($totalCalculat, 2, ',', '.') }} lei
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actiuni --}}
                <div class="flex items-center justify-between">
                    <a href="{{ route('cheltuieli.index') }}" wire:navigate
                       class="inline-flex items-center gap-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                        <x-heroicon-m-arrow-left class="w-4 h-4" />
                        Inapoi la lista
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-check class="w-4 h-4" />
                        {{ $cheltuialaId ? 'Salveaza modificari' : 'Salveaza factura' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
