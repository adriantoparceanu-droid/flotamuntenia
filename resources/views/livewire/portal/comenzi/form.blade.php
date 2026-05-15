<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Comanda noua</h1>
                <p class="text-sm text-gray-500 mt-1">Plaseaza o comanda — va fi aprobata de operator inainte de livrare.</p>
            </div>
            <a href="{{ route('portal.comenzi.index') }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                <x-heroicon-o-arrow-left class="w-4 h-4" />
                Inapoi
            </a>
        </div>

        @if(session('eroare'))
            <div class="mb-4 px-4 py-3 rounded-md bg-red-50 border border-red-200 text-red-800 text-sm">
                {{ session('eroare') }}
            </div>
        @endif

        @if($adrese->isEmpty())
            <div class="bg-white border border-gray-200 rounded-lg p-8 text-center">
                <x-heroicon-o-exclamation-triangle class="w-10 h-10 mx-auto mb-3 text-amber-500" />
                <p class="text-sm text-gray-700">
                    Nu exista nicio adresa de livrare configurata pentru contul tau.<br>
                    Contacteaza-ne pentru a adauga una inainte de a plasa comenzi.
                </p>
            </div>
        @else
            <form wire:submit="salveaza" class="space-y-5">

                {{-- Card 1: Adresa --}}
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-o-map-pin class="w-5 h-5 text-sky-600" />
                        <h2 class="font-medium text-gray-900">Adresa de livrare</h2>
                    </div>

                    <select wire:model.live="idAdresa"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">— Selecteaza adresa —</option>
                        @foreach($adrese as $a)
                            <option value="{{ $a->id }}">
                                {{ $a->eticheta }}
                            </option>
                        @endforeach
                    </select>
                    @error('idAdresa') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Card 2: Produse (apare doar dupa selectarea adresei) --}}
                @if(! empty($linii))
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-o-shopping-cart class="w-5 h-5 text-sky-600" />
                            <h2 class="font-medium text-gray-900">Produse</h2>
                        </div>

                        <div class="space-y-3">
                            @foreach($linii as $idx => $linie)
                                <div wire:key="linie-{{ $idx }}" class="flex items-center justify-between gap-3 py-2 border-b border-gray-100 last:border-0">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900">{{ $linie['denumire'] }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format((float) $linie['pret'], 2, ',', '.') }} lei / buc</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" wire:click="decrementeaza({{ $idx }})"
                                                class="w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">−</button>
                                        <input type="number" min="0" wire:model.live="linii.{{ $idx }}.cantitate"
                                               class="w-16 text-center rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                                        <button type="button" wire:click="incrementeaza({{ $idx }})"
                                                class="w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">+</button>
                                    </div>
                                    <div class="w-24 text-right text-sm font-medium text-gray-900">
                                        {{ number_format((int) $linie['cantitate'] * (float) $linie['pret'], 2, ',', '.') }} lei
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('linii') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror

                        <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200">
                            <span class="text-sm text-gray-600">Total estimativ</span>
                            <span class="text-lg font-semibold text-gray-900">
                                {{ number_format($this->totalCalculat(), 2, ',', '.') }} lei
                            </span>
                        </div>
                    </div>

                    {{-- Card 3: Detalii livrare --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-o-calendar-days class="w-5 h-5 text-sky-600" />
                            <h2 class="font-medium text-gray-900">Detalii livrare</h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Data dorita</label>
                                <input type="date" wire:model="dataLivrare" min="{{ now()->toDateString() }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                                @error('dataLivrare') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Interval (optional)</label>
                                <input type="text" wire:model="intervalLivrare" placeholder="ex: 09-12, dupa-amiaza"
                                       maxlength="50"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Modalitate plata</label>
                                <select wire:model="idModalitatePlata"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="1">Cash la livrare</option>
                                    <option value="3">Card</option>
                                    <option value="2">Ordin de plata</option>
                                    <option value="4">Alta</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tip comanda</label>
                                <select wire:model="tipComanda"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="fara abonament">Fara abonament</option>
                                    <option value="consum suplimentar">Consum suplimentar (peste abonament)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Observatii (optional)</label>
                            <textarea wire:model="observatii" rows="3" maxlength="2000"
                                      placeholder="Mentiuni speciale pentru sofer (cod interfon, ora preferata, etc.)"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"></textarea>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-gray-500">
                            Comanda va fi trimisa spre aprobare. Vei primi un email cand este aprobata sau respinsa.
                        </p>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-sky-600 text-white text-sm font-medium rounded-md hover:bg-sky-700 disabled:opacity-50 transition">
                            <span wire:loading.remove>
                                <x-heroicon-o-paper-airplane class="w-4 h-4 inline" />
                                Trimite spre aprobare
                            </span>
                            <span wire:loading>
                                Se trimite...
                            </span>
                        </button>
                    </div>
                @endif
            </form>
        @endif
    </div>
</div>
