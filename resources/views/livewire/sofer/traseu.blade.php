<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-map class="w-6 h-6 text-indigo-600" />
            Traseul meu
        </h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-3xl mx-auto px-3 sm:px-6 space-y-4">

            {{-- Toolbar --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-3">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <button type="button" wire:click="navigheazaZi(-1)"
                            class="inline-flex items-center justify-center w-9 h-9 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 rounded-md">
                        <x-heroicon-m-chevron-left class="w-5 h-5" />
                    </button>
                    <div class="flex-1 text-center">
                        <input type="date" wire:model.live="data"
                               class="block w-full sm:w-auto sm:inline-block text-center font-medium rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        <div class="text-[11px] text-gray-500 mt-0.5 capitalize">
                            {{ \Carbon\Carbon::parse($data)->locale('ro')->isoFormat('dddd, D MMMM YYYY') }}
                        </div>
                    </div>
                    <button type="button" wire:click="navigheazaZi(1)"
                            class="inline-flex items-center justify-center w-9 h-9 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 rounded-md">
                        <x-heroicon-m-chevron-right class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex justify-between text-center bg-gray-50 dark:bg-gray-900 rounded-md p-2 text-xs">
                    <div class="flex-1">
                        <div class="text-[10px] text-gray-500 uppercase">Comenzi</div>
                        <div class="text-base font-semibold tabular-nums">{{ $itemi->count() }}</div>
                    </div>
                    <div class="flex-1 border-l border-gray-200 dark:border-gray-700">
                        <div class="text-[10px] text-gray-500 uppercase">Livrate</div>
                        <div class="text-base font-semibold tabular-nums text-green-700">{{ $nrLivrate }}</div>
                    </div>
                    <div class="flex-1 border-l border-gray-200 dark:border-gray-700">
                        <div class="text-[10px] text-gray-500 uppercase">19L</div>
                        <div class="text-base font-semibold tabular-nums">{{ $nr19l }}</div>
                    </div>
                    <div class="flex-1 border-l border-gray-200 dark:border-gray-700">
                        <div class="text-[10px] text-gray-500 uppercase">11L</div>
                        <div class="text-base font-semibold tabular-nums">{{ $nr11l }}</div>
                    </div>
                    <div class="flex-1 border-l border-gray-200 dark:border-gray-700">
                        <div class="text-[10px] text-gray-500 uppercase">Total</div>
                        <div class="text-base font-semibold tabular-nums">{{ number_format($sumarLei, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Harta — doar comenzile NE-livrate ale soferului --}}
            @if($soferAreMasina && ! empty($apiKey))
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden relative">
                    {{-- Data carrier morfat de Livewire la fiecare update --}}
                    <div id="harta-sofer-data"
                         data-puncte="{{ json_encode($puncteHarta, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                         class="hidden"></div>

                    <div wire:ignore id="harta-traseu-sofer" class="w-full h-[20rem]"></div>

                    @if(empty($puncteHarta))
                        <div class="absolute inset-0 flex items-center justify-center bg-gray-50/95 dark:bg-gray-900/95 text-center px-4">
                            <div class="text-sm text-gray-500">
                                <x-heroicon-o-map class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                                @if($itemi->isEmpty())
                                    Nu ai comenzi pentru aceasta zi.
                                @elseif($itemi->where('livrat', false)->count() === 0)
                                    Toate comenzile sunt livrate.
                                @else
                                    Nicio comanda din ziua aceasta nu are GPS setat.
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @elseif($soferAreMasina && empty($apiKey))
                <div class="bg-amber-50 border border-amber-200 rounded-md p-3 text-xs text-amber-800 flex items-start gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 flex-shrink-0 mt-0.5" />
                    <div>Cheia Google Maps lipseste — harta nu poate fi afisata. Contacteaza administratorul.</div>
                </div>
            @endif

            {{-- Lista --}}
            @if(! $soferAreMasina)
                <div class="bg-amber-50 border border-amber-200 rounded-md p-4 text-sm text-amber-800 flex items-start gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <div>Nu ai o masina asignata. Contacteaza administratorul pentru a primi traseul.</div>
                </div>
            @elseif($itemi->isEmpty())
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-8 text-center">
                    <x-heroicon-o-clipboard-document-list class="w-14 h-14 mx-auto mb-2 text-gray-300" />
                    <p class="text-sm text-gray-500">Nu ai comenzi pentru aceasta zi.</p>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden divide-y dark:divide-gray-700">
                    @foreach($itemi as $i)
                        @php $expandat = ($cheieExpandata === $i['cheie']); @endphp

                        <div wire:key="item-{{ $i['cheie'] }}" id="item-{{ $i['cheie'] }}">
                            {{-- Rand compact --}}
                            <button type="button" wire:click="expand('{{ $i['tip'] }}', {{ $i['id'] }})"
                                    class="w-full flex items-center gap-3 px-3 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-900 active:bg-gray-100">
                                <span class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full w-8 h-8 inline-flex items-center justify-center text-xs font-bold tabular-nums flex-shrink-0">
                                    {{ $i['ordine_traseu'] ?: '·' }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1">
                                        @if($i['tip'] === 'comanda_rapida')
                                            <x-heroicon-m-bolt class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" title="Comanda rapida" />
                                        @endif
                                        <div class="text-sm font-medium {{ $i['livrat'] ? 'text-gray-500 line-through' : 'text-gray-900 dark:text-gray-100' }} truncate">
                                            {{ $i['titlu'] }}
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">{{ $i['subtitlu'] }}</div>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs">
                                    @if($i['livrat'])
                                        <span class="inline-flex items-center justify-center bg-green-100 text-green-700 rounded-full w-6 h-6" title="Livrata">
                                            <x-heroicon-s-check class="w-3.5 h-3.5" />
                                        </span>
                                    @endif
                                    @if($i['achitat'])
                                        <span class="inline-flex items-center justify-center bg-blue-100 text-blue-700 rounded-full w-6 h-6" title="Achitata">
                                            <x-heroicon-m-banknotes class="w-3.5 h-3.5" />
                                        </span>
                                    @endif
                                    <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400 transition-transform {{ $expandat ? 'rotate-90' : '' }}" />
                                </div>
                            </button>

                            {{-- Detalii expandate --}}
                            @if($expandat)
                                <div class="px-3 py-3 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700 space-y-3">
                                    {{-- Adresa --}}
                                    <div class="text-sm">
                                        <div class="text-[11px] text-gray-500 uppercase tracking-wide mb-0.5">Adresa</div>
                                        <div class="text-gray-900 dark:text-gray-100">{{ $i['adresa_completa'] ?: '—' }}</div>
                                        @if($i['interfon'])
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                <span class="inline-flex items-center gap-0.5">
                                                    <x-heroicon-m-phone class="w-3 h-3" />
                                                    Interfon: {{ $i['interfon'] }}
                                                </span>
                                            </div>
                                        @endif
                                        @if($i['lat'] !== null && $i['lng'] !== null)
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $i['lat'] }},{{ $i['lng'] }}"
                                               target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 mt-1">
                                                <x-heroicon-m-map-pin class="w-3 h-3" />
                                                Deschide in Google Maps
                                            </a>
                                        @endif
                                    </div>

                                    {{-- Contact --}}
                                    @if($i['nume_contact'] || $i['telefon'])
                                        <div class="text-sm">
                                            <div class="text-[11px] text-gray-500 uppercase tracking-wide mb-0.5">Contact</div>
                                            <div class="flex items-center gap-2">
                                                @if($i['nume_contact'])
                                                    <span class="text-gray-900 dark:text-gray-100">{{ $i['nume_contact'] }}</span>
                                                @endif
                                                @if($i['telefon'])
                                                    <a href="tel:{{ $i['telefon'] }}" class="inline-flex items-center gap-1 text-indigo-600">
                                                        <x-heroicon-m-phone class="w-3 h-3" />
                                                        {{ $i['telefon'] }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Produse --}}
                                    <div class="text-sm">
                                        <div class="text-[11px] text-gray-500 uppercase tracking-wide mb-0.5">De livrat</div>
                                        @if($i['produse']->isNotEmpty())
                                            <ul class="space-y-0.5 text-gray-900 dark:text-gray-100">
                                                @foreach($i['produse'] as $linie)
                                                    <li class="flex justify-between gap-2">
                                                        <span>{{ $linie->cantitate }}× {{ $linie->produs?->denumire ?? '—' }}</span>
                                                        <span class="tabular-nums text-gray-500">{{ number_format($linie->subtotal(), 2, ',', '.') }} lei</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="text-gray-500 italic">Fara produse pe comanda</div>
                                        @endif
                                        <div class="flex justify-between mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 font-semibold">
                                            <span>Total</span>
                                            <span class="tabular-nums">{{ number_format($i['total'], 2, ',', '.') }} lei</span>
                                        </div>
                                    </div>

                                    {{-- Plata --}}
                                    <div class="text-sm flex justify-between">
                                        <div class="text-[11px] text-gray-500 uppercase tracking-wide">Plata</div>
                                        <div class="font-medium">{{ $i['mod_plata'] }}</div>
                                    </div>

                                    {{-- Sold recipienti — doar la comenzi clasice; poate fi pozitiv (de recuperat) sau negativ (datorie firma) --}}
                                    @if($i['are_recipienti'] && ($i['sold']['19l'] !== 0 || $i['sold']['11l'] !== 0))
                                        @php
                                            // Tratam fiecare capacitate independent — pot fi simultan
                                            // pozitiv 19L si negativ 11L (sau invers).
                                            $are_pozitiv = $i['sold']['19l'] > 0 || $i['sold']['11l'] > 0;
                                            $are_negativ = $i['sold']['19l'] < 0 || $i['sold']['11l'] < 0;
                                        @endphp
                                        <div class="space-y-1">
                                            @if($are_pozitiv)
                                                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-md p-2 text-sm">
                                                    <div class="text-[11px] text-amber-700 dark:text-amber-300 font-semibold uppercase tracking-wide mb-0.5">
                                                        <x-heroicon-m-archive-box class="w-3.5 h-3.5 inline" />
                                                        De recuperat la aceasta adresa
                                                    </div>
                                                    <div class="text-amber-900 dark:text-amber-100">
                                                        @if($i['sold']['19l'] > 0) {{ $i['sold']['19l'] }} bidoane 19L @endif
                                                        @if($i['sold']['19l'] > 0 && $i['sold']['11l'] > 0) · @endif
                                                        @if($i['sold']['11l'] > 0) {{ $i['sold']['11l'] }} bidoane 11L @endif
                                                    </div>
                                                </div>
                                            @endif
                                            @if($are_negativ)
                                                <div class="bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-md p-2 text-sm">
                                                    <div class="text-[11px] text-sky-700 dark:text-sky-300 font-semibold uppercase tracking-wide mb-0.5">
                                                        <x-heroicon-m-arrow-uturn-left class="w-3.5 h-3.5 inline" />
                                                        Datorie firma catre client
                                                    </div>
                                                    <div class="text-sky-900 dark:text-sky-100">
                                                        @if($i['sold']['19l'] < 0) {{ abs($i['sold']['19l']) }} bidoane 19L @endif
                                                        @if($i['sold']['19l'] < 0 && $i['sold']['11l'] < 0) · @endif
                                                        @if($i['sold']['11l'] < 0) {{ abs($i['sold']['11l']) }} bidoane 11L @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Observatii --}}
                                    @if($i['observatii'])
                                        <div class="text-sm">
                                            <div class="text-[11px] text-gray-500 uppercase tracking-wide mb-0.5">Observatii</div>
                                            <div class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $i['observatii'] }}</div>
                                        </div>
                                    @endif

                                    {{-- Actiuni --}}
                                    @if($i['are_recipienti'])
                                        {{-- Comanda clasica: flux combinat. CTA mare verde "Confirma livrarea"
                                             cand nu e livrata; cand e livrata, butonul devine "Anuleaza livrarea". --}}
                                        @if($i['livrat'])
                                            <div class="grid grid-cols-3 gap-2 pt-2">
                                                <button type="button" wire:click="comutaLivrat('{{ $i['tip'] }}', {{ $i['id'] }})"
                                                        class="flex flex-col items-center justify-center py-3 rounded-md text-xs font-medium bg-green-600 text-white hover:bg-green-700">
                                                    <x-heroicon-s-check-circle class="w-5 h-5 mb-0.5" />
                                                    Livrata
                                                </button>
                                                <button type="button" wire:click="comutaAchitat('{{ $i['tip'] }}', {{ $i['id'] }})"
                                                        class="flex flex-col items-center justify-center py-3 rounded-md text-xs font-medium {{ $i['achitat'] ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                    <x-heroicon-m-banknotes class="w-5 h-5 mb-0.5" />
                                                    {{ $i['achitat'] ? 'Achitata' : 'Marcheaza plata' }}
                                                </button>
                                                <button type="button" wire:click="deschideRecipienti({{ $i['id'] }})"
                                                        class="flex flex-col items-center justify-center py-3 rounded-md text-xs font-medium bg-amber-100 text-amber-800 hover:bg-amber-200"
                                                        title="Adauga miscare suplimentara de recipienti">
                                                    <x-heroicon-m-archive-box class="w-5 h-5 mb-0.5" />
                                                    Recipienti
                                                </button>
                                            </div>
                                        @else
                                            <div class="space-y-2 pt-2">
                                                <button type="button" wire:click="confirmaLivrare({{ $i['id'] }})"
                                                        class="w-full flex items-center justify-center gap-2 py-3.5 rounded-md text-base font-semibold bg-green-600 text-white hover:bg-green-700 active:bg-green-800 shadow-sm">
                                                    <x-heroicon-s-check-circle class="w-6 h-6" />
                                                    Confirma livrarea
                                                </button>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <button type="button" wire:click="comutaAchitat('{{ $i['tip'] }}', {{ $i['id'] }})"
                                                            class="flex items-center justify-center gap-1.5 py-2.5 rounded-md text-xs font-medium {{ $i['achitat'] ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                        <x-heroicon-m-banknotes class="w-4 h-4" />
                                                        {{ $i['achitat'] ? 'Achitata' : 'Marcheaza plata' }}
                                                    </button>
                                                    <button type="button" wire:click="deschideRecipienti({{ $i['id'] }})"
                                                            class="flex items-center justify-center gap-1.5 py-2.5 rounded-md text-xs font-medium bg-amber-100 text-amber-800 hover:bg-amber-200"
                                                            title="Inregistreaza recipienti fara a marca livrata">
                                                        <x-heroicon-m-archive-box class="w-4 h-4" />
                                                        Doar recipienti
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        {{-- Comanda rapida: doar livrat + achitat (fara recipienti) --}}
                                        <div class="grid grid-cols-2 gap-2 pt-2">
                                            <button type="button" wire:click="comutaLivrat('{{ $i['tip'] }}', {{ $i['id'] }})"
                                                    class="flex flex-col items-center justify-center py-3 rounded-md text-xs font-medium {{ $i['livrat'] ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                <x-heroicon-s-check-circle class="w-5 h-5 mb-0.5" />
                                                {{ $i['livrat'] ? 'Livrata' : 'Marcheaza livrat' }}
                                            </button>
                                            <button type="button" wire:click="comutaAchitat('{{ $i['tip'] }}', {{ $i['id'] }})"
                                                    class="flex flex-col items-center justify-center py-3 rounded-md text-xs font-medium {{ $i['achitat'] ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                <x-heroicon-m-banknotes class="w-5 h-5 mb-0.5" />
                                                {{ $i['achitat'] ? 'Achitata' : 'Marcheaza plata' }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Recipienti / Confirmare livrare --}}
    <div x-data="{ deschis: @entangle('modalRecipienti') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideRecipienti()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-3 py-6">
        <div x-show="deschis" x-on:click="$wire.inchideRecipienti()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-md sm:mx-auto p-5">
            <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">
                @if($modConfirmareLivrare)
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-600" />
                    Confirma livrarea
                @else
                    <x-heroicon-o-archive-box class="w-5 h-5 text-amber-600" />
                    Actualizeaza recipienti
                @endif
            </h3>
            <p class="text-xs text-gray-500 mb-3">
                @if($modConfirmareLivrare)
                    Verifica cantitatile (precompletate din comanda) si confirma. Comanda se va marca livrata si va disparea de pe harta.
                @else
                    Inregistreaza miscarea de bidoane fara a modifica statusul comenzii.
                @endif
            </p>

            <form wire:submit.prevent="salveazaRecipienti">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">19L lasati</label>
                        <input type="number" min="0" wire:model="recLasati19l" inputmode="numeric"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm tabular-nums" />
                        @error('recLasati19l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">19L recuperati</label>
                        <input type="number" min="0" wire:model="recRecuperati19l" inputmode="numeric"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm tabular-nums" />
                        @error('recRecuperati19l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">11L lasati</label>
                        <input type="number" min="0" wire:model="recLasati11l" inputmode="numeric"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm tabular-nums" />
                        @error('recLasati11l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">11L recuperati</label>
                        <input type="number" min="0" wire:model="recRecuperati11l" inputmode="numeric"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm tabular-nums" />
                        @error('recRecuperati11l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Observatii (optional)</label>
                    <textarea wire:model="recObservatii" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="inchideRecipienti"
                            class="inline-flex items-center gap-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Anuleaza
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-4 py-2 {{ $modConfirmareLivrare ? 'bg-green-600 hover:bg-green-700' : 'bg-amber-600 hover:bg-amber-700' }} text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-check class="w-4 h-4" />
                        {{ $modConfirmareLivrare ? 'Confirma livrarea' : 'Salveaza' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast notificari --}}
    <div
        x-data="{
            toasts: [],
            adauga(t) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, ...t });
                setTimeout(() => this.sterge(id), t.undoAction ? 5000 : 3000);
            },
            sterge(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
            undo(t) {
                if (Array.isArray(t.undoArg)) {
                    $wire.call(t.undoAction, ...t.undoArg);
                } else {
                    $wire.call(t.undoAction, t.undoArg);
                }
                this.sterge(t.id);
            }
        }"
        x-on:toast.window="adauga($event.detail[0] || $event.detail)"
        class="fixed bottom-4 left-0 right-0 px-3 z-50 pointer-events-none flex flex-col items-center gap-2">
        <template x-for="t in toasts" :key="t.id">
            <div class="pointer-events-auto bg-gray-900 text-white text-sm rounded-lg shadow-lg px-4 py-2.5 flex items-center gap-3 min-w-[260px] max-w-md"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <span class="text-green-400">✓</span>
                <span class="flex-1" x-text="t.mesaj"></span>
                <template x-if="t.undoAction">
                    <button x-on:click="undo(t)" class="text-indigo-300 hover:text-indigo-100 font-medium">Anuleaza</button>
                </template>
            </div>
        </template>
    </div>

    {{-- Google Maps loader + render --}}
    @assets
        @if(! empty($apiKey))
            <script async defer
                    src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&callback=initTraseuSoferMap&loading=async"></script>
        @endif
    @endassets

    @script
        <script>
            (() => {
                // InfoWindow activ — un singur popup deschis simultan pe harta.
                // La click pe alt pin inchidem popup-ul precedent inainte sa il
                // deschidem pe cel nou. Persistat la nivel IIFE (nu re-creat la
                // fiecare renderHarta), ca sa supravietuiasca re-randarii Livewire.
                let infoActiv = null;

                // Stil "fade" — desaturat / pal, ca pinii colorati sa iasa puternic in contrast.
                // Pastrat sincronizat cu cel din lista-zilnica.blade.php.
                const STIL_HARTA_FADE = [
                    { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
                    { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#9ca3af' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }] },
                    { featureType: 'administrative', elementType: 'geometry', stylers: [{ color: '#e5e7eb' }] },
                    { featureType: 'administrative.country', elementType: 'labels.text.fill', stylers: [{ color: '#6b7280' }] },
                    { featureType: 'administrative.locality', elementType: 'labels.text.fill', stylers: [{ color: '#4b5563' }] },
                    { featureType: 'poi', stylers: [{ visibility: 'off' }] },
                    { featureType: 'poi.park', elementType: 'geometry', stylers: [{ color: '#eef2f5' }, { visibility: 'on' }] },
                    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
                    { featureType: 'road.arterial', elementType: 'geometry', stylers: [{ color: '#fafafa' }] },
                    { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#f3f4f6' }] },
                    { featureType: 'road.local', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
                    { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#9ca3af' }] },
                    { featureType: 'transit', stylers: [{ visibility: 'off' }] },
                    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#dbeafe' }] },
                    { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#93c5fd' }] },
                ];

                const citestePuncte = () => {
                    const el = document.getElementById('harta-sofer-data');
                    if (!el) return [];
                    try {
                        return JSON.parse(el.dataset.puncte || '[]');
                    } catch (e) {
                        return [];
                    }
                };

                const escHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                }[c]));

                // Scroll + expand item-ul corespunzator pinului din lista
                window.__focusItemSofer = (cheie) => {
                    const el = document.getElementById('item-' + cheie);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                };

                const renderHarta = () => {
                    const div = document.getElementById('harta-traseu-sofer');
                    if (!div || !window.google || !window.google.maps) return;

                    const puncte = citestePuncte();

                    // Invalidare cache cand div-ul a fost recreat (wire:navigate)
                    if (window.__hartaSofer) {
                        const divCached = window.__hartaSofer.getDiv();
                        if (divCached !== div || !document.body.contains(divCached)) {
                            if (window.__hartaSoferMarkeri) {
                                window.__hartaSoferMarkeri.forEach(m => m.setMap(null));
                            }
                            window.__hartaSoferMarkeri = [];
                            window.__hartaSofer = null;
                        }
                    }

                    // Curatam markerii la fiecare re-render (pinii livraților dispar)
                    if (window.__hartaSoferMarkeri) {
                        window.__hartaSoferMarkeri.forEach(m => m.setMap(null));
                    }
                    window.__hartaSoferMarkeri = [];
                    // Inchidem si popup-ul activ — apartinea unui marker care poate
                    // a fost sters (ex: comanda a fost marcata livrata in alt tab).
                    if (infoActiv) {
                        infoActiv.close();
                        infoActiv = null;
                    }

                    if (puncte.length === 0) {
                        return; // overlay HTML afiseaza mesajul corespunzator
                    }

                    if (!window.__hartaSofer) {
                        window.__hartaSofer = new google.maps.Map(div, {
                            zoom: 12,
                            center: { lat: puncte[0].lat, lng: puncte[0].lng },
                            mapTypeControl: false,
                            streetViewControl: false,
                            fullscreenControl: false,
                            styles: STIL_HARTA_FADE,
                        });
                    } else {
                        google.maps.event.trigger(window.__hartaSofer, 'resize');
                    }

                    const harta = window.__hartaSofer;
                    const bounds = new google.maps.LatLngBounds();

                    puncte.forEach((p) => {
                        const pos = { lat: p.lat, lng: p.lng };
                        bounds.extend(pos);

                        const ordineText = p.ordine > 0 ? String(p.ordine) : '';
                        const marker = new google.maps.Marker({
                            position: pos,
                            map: harta,
                            title: `${p.titlu} — ${p.subtitlu}`,
                            label: { text: ordineText || ' ', color: '#fff', fontSize: '11px', fontWeight: 'bold' },
                            icon: {
                                path: 'M12 2C7.58 2 4 5.58 4 10c0 5.5 8 12 8 12s8-6.5 8-12c0-4.42-3.58-8-8-8z',
                                fillColor: p.culoare,
                                fillOpacity: 1,
                                strokeColor: '#fff',
                                strokeWeight: 2,
                                scale: 1.6,
                                anchor: new google.maps.Point(12, 22),
                                labelOrigin: new google.maps.Point(12, 10),
                            },
                        });

                        const cantitati = [];
                        if (p.nr19l) cantitati.push(`${p.nr19l}× 19L`);
                        if (p.nr11l) cantitati.push(`${p.nr11l}× 11L`);
                        const tipBadge = p.rapida ? '⚡ ' : '';

                        const html = `<div style="font-size:12px;line-height:1.35;min-width:200px">
                            <div style="font-weight:600;color:#111">${tipBadge}${escHtml(p.titlu)}</div>
                            <div style="color:#666;margin-top:1px">${escHtml(p.subtitlu)}</div>
                            ${p.ordine > 0 ? `<div style="color:#888;margin-top:1px;font-size:11px">Stop #${p.ordine}</div>` : ''}
                            ${cantitati.length ? `<div style="color:#444;margin-top:4px;font-size:11px">${cantitati.join(' · ')}</div>` : ''}
                            <div style="margin-top:6px">
                                <button onclick="window.__focusItemSofer('${escHtml(p.cheie)}')"
                                        style="font-size:11px;padding:3px 8px;border:1px solid #4f46e5;background:#eef2ff;color:#4f46e5;border-radius:4px;cursor:pointer">
                                    Vezi in lista
                                </button>
                            </div>
                        </div>`;
                        const info = new google.maps.InfoWindow({ content: html });
                        marker.addListener('click', () => {
                            if (infoActiv && infoActiv !== info) {
                                infoActiv.close();
                            }
                            info.open(harta, marker);
                            infoActiv = info;
                        });
                        // Daca user-ul inchide popup-ul prin butonul X, scoatem
                        // referinta ca sa nu apelam .close() pe un popup deja inchis.
                        info.addListener('closeclick', () => {
                            if (infoActiv === info) infoActiv = null;
                        });

                        window.__hartaSoferMarkeri.push(marker);
                    });

                    if (puncte.length > 1) {
                        harta.fitBounds(bounds);
                    } else {
                        harta.setCenter({ lat: puncte[0].lat, lng: puncte[0].lng });
                        harta.setZoom(14);
                    }
                };

                window.initTraseuSoferMap = renderHarta;

                if (window.google && window.google.maps) {
                    renderHarta();
                }

                // Re-render la fiecare update Livewire (livrat -> dispare pin)
                if (!window.__traseuSoferHookRegistrat && typeof Livewire !== 'undefined') {
                    window.__traseuSoferHookRegistrat = true;
                    Livewire.hook('morph.updated', () => {
                        setTimeout(renderHarta, 0);
                    });
                }
            })();
        </script>
    @endscript
</div>
