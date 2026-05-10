<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-bolt class="w-6 h-6 text-amber-500" />
            {{ $comandaId ? 'Editeaza comanda rapida #' . $comandaId : 'Comanda rapida noua' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form wire:submit.prevent="salveaza" class="space-y-6">

                {{-- Card 1: Date contact --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-user class="w-5 h-5 text-amber-500" />
                        Date contact (text liber, fara cont client)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Denumire client / punct *</label>
                            <input type="text" wire:model="denumire" maxlength="255"
                                   placeholder="ex: Birou Soseaua Pipera, Eveniment ABC"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('denumire') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Adresa</label>
                            <input type="text" wire:model="adresa" maxlength="500"
                                   placeholder="ex: Bd. Pipera 1, Sector 1, Bucuresti"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('adresa') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                            <input type="text" wire:model="telefon" maxlength="50"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Coordonate GPS (lat, lng)</label>
                            <input type="text" wire:model="gps" placeholder="ex: 44.4325, 26.1025"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            <p class="text-[11px] text-gray-500 mt-1">Copy/paste din Google Maps. Apare ca marker pe harta.</p>
                            @error('gps') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Card 2: Date livrare --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-calendar class="w-5 h-5 text-amber-500" />
                        Detalii livrare
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Data livrare *</label>
                            <input type="date" wire:model="dataLivrare"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('dataLivrare') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Masina (optional)</label>
                            <select wire:model="idMasina"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                <option value="">— neasignata —</option>
                                @foreach($masini as $m)
                                    <option value="{{ $m->id }}">{{ $m->denumire }} ({{ $m->nr_inmatriculare }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Depozit (sursa stoc)</label>
                            <select wire:model="idDepozit"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                <option value="">— neales —</option>
                                @foreach($depozite as $d)
                                    <option value="{{ $d->id }}">{{ $d->denumire }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Produse --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100">
                            <x-heroicon-o-cube class="w-5 h-5 text-amber-500" />
                            Produse
                        </h3>
                        <button type="button" wire:click="adaugaLinieGoala"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs rounded-md">
                            <x-heroicon-m-plus class="w-3.5 h-3.5" />
                            Adauga linie
                        </button>
                    </div>
                    @error('linii') <p class="text-xs text-red-600 mb-2">{{ $message }}</p> @enderror

                    <div class="space-y-2">
                        @foreach($linii as $idx => $linie)
                            <div wire:key="linie-rapida-{{ $idx }}" class="grid grid-cols-12 gap-2 items-start p-2 bg-gray-50 dark:bg-gray-900 rounded-md">
                                <div class="col-span-12 md:col-span-5">
                                    <label class="block text-[11px] text-gray-500">Produs din catalog</label>
                                    <select wire:model.live="linii.{{ $idx }}.id_produs"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                                        <option value="">— linie custom —</option>
                                        @foreach($produseCatalog as $p)
                                            <option value="{{ $p->id }}">{{ $p->denumire }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-12 md:col-span-4">
                                    <label class="block text-[11px] text-gray-500">Denumire</label>
                                    <input type="text" wire:model="linii.{{ $idx }}.denumire"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                                    @error("linii.{$idx}.denumire") <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-4 md:col-span-1">
                                    <label class="block text-[11px] text-gray-500">Cant.</label>
                                    <input type="number" min="1" wire:model="linii.{{ $idx }}.cantitate"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs tabular-nums" />
                                </div>
                                <div class="col-span-6 md:col-span-1">
                                    <label class="block text-[11px] text-gray-500">Pret</label>
                                    <input type="number" step="0.01" min="0" wire:model="linii.{{ $idx }}.pret"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs tabular-nums" />
                                </div>
                                <div class="col-span-2 md:col-span-1 flex items-end justify-end pb-1">
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
                            <span class="text-xs text-gray-500">Total comanda</span>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100 tabular-nums">
                                {{ number_format($totalCalculat, 2, ',', '.') }} lei
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Observatii --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-chat-bubble-left class="w-5 h-5 text-amber-500" />
                        Observatii
                    </h3>
                    <textarea wire:model="observatii" rows="3"
                              class="block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('comenzi-rapide.index') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Anuleaza
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-check class="w-4 h-4" />
                        {{ $comandaId ? 'Salveaza modificari' : 'Salveaza comanda' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
