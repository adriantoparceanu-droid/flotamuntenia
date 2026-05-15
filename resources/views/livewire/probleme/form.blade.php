<div>
    @php use App\Models\Problema; @endphp

    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-rose-600" />
            {{ $problemaId ? 'Editeaza problema #' . $problemaId : 'Problema noua' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form wire:submit.prevent="salveaza" class="space-y-6">

                {{-- Card 1: Client + Adresa --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-user class="w-5 h-5 text-rose-600" />
                        Client si adresa
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Search client --}}
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Client *</label>
                            @if($clientSelectat)
                                <div class="mt-1 flex items-center justify-between gap-2 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $clientSelectat->denumire }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $clientSelectat->cod_client }}{{ $clientSelectat->cif ? ' · ' . $clientSelectat->cif : '' }}</div>
                                    </div>
                                    <button type="button" wire:click="$set('idClient', null); $set('cautareClient', '')"
                                            class="text-xs text-gray-500 hover:text-red-600 inline-flex items-center gap-0.5">
                                        <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                                        Schimba
                                    </button>
                                </div>
                            @else
                                <div class="relative mt-1">
                                    <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                                    <input type="text" wire:model.live.debounce.300ms="cautareClient"
                                           placeholder="Cauta dupa denumire, cod, CIF..."
                                           class="pl-9 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                </div>
                                @if($clientiCautare->isNotEmpty())
                                    <ul class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-auto">
                                        @foreach($clientiCautare as $cl)
                                            <li>
                                                <button type="button" wire:click="$set('idClient', {{ $cl->id }})"
                                                        class="w-full text-left px-3 py-2 hover:bg-rose-50 dark:hover:bg-rose-900/20 text-sm">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $cl->denumire }}</div>
                                                    <div class="text-xs text-gray-500">{{ $cl->cod_client }}{{ $cl->cif ? ' · ' . $cl->cif : '' }}</div>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif
                            @error('idClient') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        {{-- Adresa --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Adresa *</label>
                            <select wire:model.live="idAdresa" {{ $idClient ? '' : 'disabled' }}
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm disabled:opacity-50">
                                <option value="">@if(! $idClient)— alege intai clientul —@else— alege adresa —@endif</option>
                                @foreach($adrese as $a)
                                    <option value="{{ $a->id }}">
                                        {{ $a->eticheta }}
                                    </option>
                                @endforeach
                            </select>
                            @error('idAdresa') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Card 2: Detalii intervenție --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-wrench-screwdriver class="w-5 h-5 text-rose-600" />
                        Detalii interventie
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Descriere problema *</label>
                            <textarea wire:model="descriere" rows="3"
                                      placeholder="ex: Pompa dozator defecta, scurgere la racord, etc."
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                            @error('descriere') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Suma de incasat *</label>
                                <div class="relative mt-1">
                                    <input type="number" step="0.01" min="0" wire:model="suma"
                                           class="pr-10 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-500">lei</span>
                                </div>
                                @error('suma') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Modalitate plata *</label>
                                <select wire:model="idModalitatePlata"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                    <option value="{{ Problema::MODPLATA_CASH }}">Cash</option>
                                    <option value="{{ Problema::MODPLATA_OP }}">Ordin de plata</option>
                                    <option value="{{ Problema::MODPLATA_CARD }}">Card</option>
                                    <option value="{{ Problema::MODPLATA_ALTA }}">Alta</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Data interventie *</label>
                                <input type="date" wire:model="dataLivrare"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                @error('dataLivrare') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Interval orar (optional)</label>
                                <input type="text" wire:model="intervalLivrare" placeholder="ex: 09:00-12:00"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Masina (optional)</label>
                                <select wire:model="idMasina"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                    <option value="">— Nealocata —</option>
                                    @foreach($masini as $m)
                                        <option value="{{ $m->id }}">{{ $m->denumire }} · {{ $m->nr_inmatriculare }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Depozit (optional)</label>
                                <select wire:model="idDepozit"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                    <option value="">— Implicit —</option>
                                    @foreach($depozite as $d)
                                        <option value="{{ $d->id }}">{{ $d->denumire }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Contact override (optional) --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-phone class="w-5 h-5 text-rose-600" />
                        Contact la fata locului (optional)
                        <span class="ml-1 text-xs font-normal text-gray-500">— suprascrie contactul implicit al clientului</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Nume persoana</label>
                            <input type="text" wire:model="nume"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                            <input type="text" wire:model="telefon"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                    </div>
                </div>

                {{-- Butoane actiune --}}
                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('probleme.index') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md">
                        <x-heroicon-m-arrow-uturn-left class="w-4 h-4" />
                        Anuleaza
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-md shadow-sm">
                        <x-heroicon-m-check class="w-4 h-4" />
                        {{ $problemaId ? 'Salveaza modificarile' : 'Salveaza problema' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
