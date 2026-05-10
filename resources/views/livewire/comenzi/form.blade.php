<div>
    @php use App\Models\Comanda; @endphp

    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-indigo-600" />
            {{ $comandaId ? 'Editeaza comanda #' . $comandaId : 'Comanda noua' }}
            @if($aprobaLaSalvare)
                <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-emerald-100 text-emerald-700 font-medium">
                    <x-heroicon-m-check-badge class="w-4 h-4" />
                    Aprobare in curs
                </span>
            @endif
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('mesaj'))
                <div class="px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                    {{ session('mesaj') }}
                </div>
            @endif
            @if (session('eroare'))
                <div class="px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm flex items-center gap-2">
                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-500 flex-shrink-0" />
                    {{ session('eroare') }}
                </div>
            @endif

            @if($eraInAsteptare && ! $aprobaLaSalvare)
                <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-start gap-2">
                    <x-heroicon-o-clock class="w-5 h-5 flex-shrink-0 text-amber-600" />
                    <div>
                        Comanda este <span class="font-semibold">in asteptare</span> (plasata din portal client).
                        Salvarea de aici NU o aproba — doar retine modificarile cu status-ul intact.
                        Pentru aprobare, deschide-o din <a href="{{ route('comenzi.aprobare') }}" wire:navigate class="underline font-medium">Aprobare comenzi</a>.
                    </div>
                </div>
            @elseif($aprobaLaSalvare)
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-start gap-2">
                    <x-heroicon-o-check-badge class="w-5 h-5 flex-shrink-0 text-emerald-600" />
                    <div>
                        Editezi o comanda portal pentru aprobare. La salvare: status-ul devine activ,
                        se genereaza miscarile de stoc OUT si se trimite email confirmare clientului.
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="salveaza" class="space-y-6">

                {{-- Card 1: Client + Adresa --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-user class="w-5 h-5 text-indigo-600" />
                        Client si adresa de livrare
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Search client --}}
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Client *</label>
                            @if($clientSelectat)
                                <div class="mt-1 flex items-center justify-between gap-2 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $clientSelectat->denumire }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $clientSelectat->cod_client }} {{ $clientSelectat->cif ? ' · ' . $clientSelectat->cif : '' }}</div>
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
                                                        class="w-full text-left px-3 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 text-sm">
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
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Adresa de livrare *</label>
                            <select wire:model.live="idAdresa" {{ $idClient ? '' : 'disabled' }}
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm disabled:opacity-50">
                                <option value="">@if(! $idClient)— alege intai clientul —@else— alege adresa —@endif</option>
                                @foreach($adrese as $a)
                                    <option value="{{ $a->id }}">
                                        {{ $a->denumire }}{{ $a->oras ? ' · ' . $a->oras : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('idAdresa') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            @if($idAdresa && $clientSelectat)
                                <p class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-1 flex items-center gap-1">
                                    <x-heroicon-m-bolt class="w-3 h-3" />
                                    Configurarea adresei a fost preluata. Verifica si modifica daca e nevoie.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card 2: Tip + plata + data --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-calendar class="w-5 h-5 text-indigo-600" />
                        Detalii livrare
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Tip comanda *</label>
                            <select wire:model.live="tipComanda"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                <option value="{{ Comanda::TIP_FARA_ABONAMENT }}">Fara abonament</option>
                                <option value="{{ Comanda::TIP_ABONAMENT }}">Abonament (lunar)</option>
                                <option value="{{ Comanda::TIP_CONSUM_SUPLIMENTAR }}">Consum suplimentar</option>
                            </select>
                            @error('tipComanda') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Modalitate plata *</label>
                            <select wire:model="idModalitatePlata"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                <option value="{{ Comanda::MODPLATA_CASH }}">Cash</option>
                                <option value="{{ Comanda::MODPLATA_OP }}">Ordin de plata</option>
                                <option value="{{ Comanda::MODPLATA_CARD }}">Card</option>
                                <option value="{{ Comanda::MODPLATA_ALTA }}">Alta</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Data livrare *</label>
                            <input type="date" wire:model.live="dataLivrare"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('dataLivrare') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Interval orar (optional)</label>
                            <input type="text" wire:model="intervalLivrare" placeholder="ex: 09:00-12:00"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>

                        @if($tipComanda === Comanda::TIP_ABONAMENT)
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Luna livrata * (YYYY/MM)
                                </label>
                                <input type="text" wire:model="lunaLivrata" placeholder="ex: 2026/05" maxlength="7"
                                       class="mt-1 block w-full md:w-48 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                <p class="text-[11px] text-gray-500 mt-1">
                                    Luna pe care o acopera aceasta comanda de abonament. Auto-completata din data livrarii.
                                </p>
                                @error('lunaLivrata') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card 3: Produse --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100">
                            <x-heroicon-o-cube class="w-5 h-5 text-indigo-600" />
                            Produse
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
                                <div class="col-span-12 md:col-span-5">
                                    <label class="block text-[11px] text-gray-500">Produs din catalog</label>
                                    <select wire:model.live="linii.{{ $idx }}.id_produs"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                                        <option value="">— linie custom —</option>
                                        @foreach($produseCatalog as $p)
                                            <option value="{{ $p->id }}">{{ $p->denumire }}</option>
                                        @endforeach
                                    </select>
                                    @error("linii.{$idx}.id_produs") <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-12 md:col-span-4">
                                    <label class="block text-[11px] text-gray-500">Denumire (vizibil pe document)</label>
                                    <input type="text" wire:model="linii.{{ $idx }}.denumire"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                                    @error("linii.{$idx}.denumire") <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-4 md:col-span-1">
                                    <label class="block text-[11px] text-gray-500">Cant.</label>
                                    <input type="number" min="1" wire:model.live.debounce.300ms="linii.{{ $idx }}.cantitate"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs tabular-nums" />
                                    @error("linii.{$idx}.cantitate") <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-6 md:col-span-1">
                                    <label class="block text-[11px] text-gray-500">Pret (lei)</label>
                                    <input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="linii.{{ $idx }}.pret"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs tabular-nums" />
                                    @error("linii.{$idx}.pret") <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
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

                {{-- Card 4: Asignare --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-truck class="w-5 h-5 text-indigo-600" />
                        Asignare operationala
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Masina</label>
                            <select wire:model="idMasina"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                <option value="">— nealocata —</option>
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
                            <p class="text-[11px] text-gray-500 mt-1">
                                Daca lipseste, miscarile de stoc OUT nu vor fi generate la salvare.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Card 5: Contact + Observatii --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        <x-heroicon-o-chat-bubble-left class="w-5 h-5 text-indigo-600" />
                        Contact si observatii
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Persoana de contact</label>
                            <input type="text" wire:model="nume" maxlength="255"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                            <input type="text" wire:model="telefon" maxlength="50"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Observatii</label>
                            <textarea wire:model="observatii" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end gap-2">
                    <a href="{{ $aprobaLaSalvare ? route('comenzi.aprobare') : route('comenzi.index') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Anuleaza
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 {{ $aprobaLaSalvare ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white text-sm font-medium rounded-md">
                        @if($aprobaLaSalvare)
                            <x-heroicon-m-check-badge class="w-4 h-4" />
                            Salveaza & aproba
                        @else
                            <x-heroicon-m-check class="w-4 h-4" />
                            {{ $comandaId ? 'Salveaza modificari' : 'Salveaza comanda' }}
                        @endif
                    </button>
                </div>
            </form>

            {{-- Card status factura electronica (Faza 6.1) --}}
            @if($comandaId && $comandaPersistata)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">
                        <x-heroicon-o-document-text class="w-5 h-5 text-indigo-600" />
                        Facturare electronica
                    </h3>

                    @if($comandaPersistata->invoice_generated)
                        {{-- Factura emisa: afisez detalii + link PDF --}}
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                    <x-heroicon-s-check-badge class="w-3.5 h-3.5" />
                                    Factura emisa
                                </span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    <strong class="font-mono">{{ $comandaPersistata->factura_serie }}-{{ $comandaPersistata->factura_numar }}</strong>
                                    @if($comandaPersistata->factura_furnizor)
                                        <span class="text-xs text-gray-500"> via {{ $comandaPersistata->factura_furnizor }}</span>
                                    @endif
                                </span>
                            </div>
                            @if($comandaPersistata->factura_link)
                                <a href="{{ $comandaPersistata->factura_link }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-md">
                                    <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
                                    Deschide PDF
                                </a>
                            @endif
                        </div>
                    @elseif(! $furnizorActivConfigurat)
                        {{-- Niciun furnizor activ: afiseaza mesaj informativ --}}
                        <div class="flex items-start gap-2 px-3 py-2 rounded bg-amber-50 text-amber-800 border border-amber-200 text-sm">
                            <x-heroicon-s-exclamation-triangle class="w-4 h-4 mt-0.5 flex-shrink-0" />
                            <span>
                                Nu este configurat niciun furnizor de facturare activ.
                                <a href="{{ route('setari.facturare') }}" wire:navigate class="underline font-medium">Configureaza in Setari → Facturare electronica</a>.
                            </span>
                        </div>
                    @else
                        {{-- Buton emitere factura --}}
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Factura va fi emisa prin furnizorul activ folosind clientul, adresa si liniile de produs ale comenzii.
                            </p>
                            <button type="button" wire:click="emiteFactura"
                                    wire:confirm="Sigur emiti factura pentru aceasta comanda?"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-md whitespace-nowrap">
                                <x-heroicon-m-bolt class="w-4 h-4" />
                                Emite factura
                            </button>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</div>
