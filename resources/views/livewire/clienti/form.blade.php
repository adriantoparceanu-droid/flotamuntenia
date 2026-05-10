<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-users class="w-6 h-6 text-indigo-600" />
            {{ $client ? 'Editare client' : 'Adauga client' }}
            @if($client)
                <span class="text-sm text-gray-500 font-normal">— {{ $client->denumire }}</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form wire:submit.prevent="salveaza" class="space-y-6">

                {{-- Card: Tip + Cod client --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tip client</label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" wire:model.live="tip" value="1"
                                           class="text-indigo-600 focus:ring-indigo-500" />
                                    <span class="ml-2 inline-flex items-center gap-1">
                                        <x-heroicon-m-building-office-2 class="w-4 h-4" />
                                        Persoana juridica
                                    </span>
                                </label>
                                <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" wire:model.live="tip" value="2"
                                           class="text-indigo-600 focus:ring-indigo-500" />
                                    <span class="ml-2 inline-flex items-center gap-1">
                                        <x-heroicon-m-user class="w-4 h-4" />
                                        Persoana fizica
                                    </span>
                                </label>
                            </div>
                            @error('tip') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Cod client
                                <span class="text-gray-400 font-normal text-xs">(auto-generat daca e gol)</span>
                            </label>
                            <input type="text" wire:model="cod_client" maxlength="50"
                                   placeholder="ex: C-000001"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono" />
                            @error('cod_client') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Card: Date principale (denumire + identificare) --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                        <x-heroicon-o-identification class="w-5 h-5" />
                        Date de identificare
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ (int)$tip === 1 ? 'Denumire firma' : 'Nume complet' }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="denumire" maxlength="255"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('denumire') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ (int)$tip === 1 ? 'CIF' : 'CNP' }}
                                @if((int)$tip === 1) <span class="text-red-500">*</span> @endif
                            </label>
                            <div class="flex items-stretch gap-2 mt-1">
                                <input type="text" wire:model="cif" maxlength="20"
                                       class="flex-1 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono" />
                                {{-- Faza 6.6 — Buton ANAF doar pentru PJ --}}
                                @if((int)$tip === 1)
                                    <button type="button" wire:click="completeazaDinAnaf"
                                            wire:loading.attr="disabled" wire:target="completeazaDinAnaf"
                                            class="inline-flex items-center gap-1.5 px-3 rounded-md border border-indigo-300 text-indigo-700 hover:bg-indigo-50 disabled:opacity-50 text-sm whitespace-nowrap"
                                            title="Aduce datele firmei din baza ANAF (cache 24h)">
                                        <span wire:loading.remove wire:target="completeazaDinAnaf" class="inline-flex items-center gap-1.5">
                                            <x-heroicon-o-cloud-arrow-down class="w-4 h-4" />
                                            ANAF
                                        </span>
                                        <span wire:loading wire:target="completeazaDinAnaf" class="inline-flex items-center gap-1.5">
                                            <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" />
                                            Caut...
                                        </span>
                                    </button>
                                @endif
                            </div>
                            @error('cif') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        @if((int)$tip === 1)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nr. Reg. Com.</label>
                                <input type="text" wire:model="reg_com" maxlength="50"
                                       placeholder="ex: J40/1234/2020"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono" />
                                @error('reg_com') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card: Adresa sediu --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                        <x-heroicon-o-map-pin class="w-5 h-5" />
                        {{ (int)$tip === 1 ? 'Adresa sediu' : 'Adresa domiciliu' }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Oras</label>
                            <input type="text" wire:model="oras" maxlength="100"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Strada</label>
                            <input type="text" wire:model="strada" maxlength="255"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nr.</label>
                            <input type="text" wire:model="nr" maxlength="20"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bloc</label>
                            <input type="text" wire:model="bloc" maxlength="20"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Scara</label>
                            <input type="text" wire:model="scara" maxlength="10"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Etaj</label>
                            <input type="text" wire:model="etaj" maxlength="10"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Apartament</label>
                            <input type="text" wire:model="apartament" maxlength="20"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sector</label>
                            <input type="text" wire:model="sector" maxlength="20"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Interfon</label>
                            <input type="text" wire:model="interfon" maxlength="20"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                    </div>
                </div>

                {{-- Card: Contact --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                        <x-heroicon-o-envelope class="w-5 h-5" />
                        Date de contact
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" wire:model="email" maxlength="255"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                            <input type="text" wire:model="telefon" maxlength="20"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('telefon') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Card: Observatii --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                        Observatii interne
                    </label>
                    <textarea wire:model="observatii" rows="3"
                              class="block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                    @error('observatii') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                {{-- Bara actiuni --}}
                <div class="flex justify-end gap-2">
                    <a href="{{ $client ? route('clienti.detalii', $client) : route('clienti.index') }}"
                       wire:navigate
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Anuleaza
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-check class="w-4 h-4" />
                        Salveaza
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
