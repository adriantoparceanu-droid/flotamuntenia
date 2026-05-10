<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-document-text class="w-6 h-6 text-indigo-600" />
            Facturare electronica
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

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-sm text-gray-600 dark:text-gray-300">
                <p class="flex gap-2">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-indigo-500 flex-shrink-0" />
                    <span>
                        Configureaza unul sau mai multi furnizori. <strong>Doar un singur furnizor poate fi activ</strong> la un moment dat — cel marcat <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 text-xs">Activ</span> e folosit cand emiti facturi din pagina comenzii. Credentialele sunt criptate inainte de a fi salvate in baza de date.
                    </span>
                </p>
            </div>

            @foreach($furnizori as $cod)
                @php
                    $existent = $setariDb->get($cod);
                    $estActiv = $existent?->activ ?? false;
                    $estConfigurat = $existent?->esteConfigurat() ?? false;
                    $estStub = $cod === \App\Models\FacturareSetari::FURNIZOR_SMARTBILL;
                @endphp
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                    <div class="flex items-start justify-between mb-4 gap-4">
                        <div class="flex items-center gap-3">
                            @if($cod === \App\Models\FacturareSetari::FURNIZOR_OBLIO)
                                <div class="w-10 h-10 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">O</div>
                            @else
                                <div class="w-10 h-10 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center font-bold">SB</div>
                            @endif
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $this->etichetaFurnizor($cod) }}
                                </h3>
                                <p class="text-xs text-gray-500">
                                    @if($cod === \App\Models\FacturareSetari::FURNIZOR_OBLIO)
                                        OAuth2 + POST /api/docs/invoice
                                    @else
                                        Basic Auth + POST /api/invoice (in dezvoltare)
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            @if($estActiv)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                    <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                    Activ
                                </span>
                            @elseif($estConfigurat)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-700">
                                    <x-heroicon-s-check-badge class="w-3.5 h-3.5" />
                                    Configurat
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">
                                    <x-heroicon-s-x-circle class="w-3.5 h-3.5" />
                                    Neconfigurat
                                </span>
                            @endif
                            @if($estStub)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                    <x-heroicon-s-wrench-screwdriver class="w-3.5 h-3.5" />
                                    In dezvoltare
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Mesaj test conexiune (afisat doar pentru furnizorul curent) --}}
                    @if($ultimulTestFurnizor === $cod && $ultimulTestMesaj !== null)
                        <div class="mb-4 px-3 py-2 rounded text-xs flex items-start gap-2
                            {{ $ultimulTestRezultat ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                            @if($ultimulTestRezultat)
                                <x-heroicon-s-check-circle class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" />
                            @else
                                <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                            @endif
                            <span>{{ $ultimulTestMesaj }}</span>
                        </div>
                    @endif

                    {{-- Formularul cu campurile specifice furnizorului --}}
                    @if($cod === \App\Models\FacturareSetari::FURNIZOR_OBLIO)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Email cont Oblio (client_id) *</label>
                                <input type="email" wire:model="formulare.{{ $cod }}.client_id" maxlength="255"
                                       placeholder="exemplu@firma.ro"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                @error("formulare.{$cod}.client_id") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">API Token (client_secret) *</label>
                                <input type="password" wire:model="formulare.{{ $cod }}.client_secret" maxlength="255"
                                       placeholder="generat din Settings > Account Data"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono" />
                                @error("formulare.{$cod}.client_secret") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">CIF emitent (companie) *</label>
                                <input type="text" wire:model="formulare.{{ $cod }}.cif" maxlength="20"
                                       placeholder="ex: RO46043131"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono uppercase" />
                                @error("formulare.{$cod}.cif") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Serie factura *</label>
                                <input type="text" wire:model="formulare.{{ $cod }}.seriesName" maxlength="20"
                                       placeholder="ex: WF"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono uppercase" />
                                @error("formulare.{$cod}.seriesName") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Limba</label>
                                <select wire:model="formulare.{{ $cod }}.language"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                    <option value="RO">Romana</option>
                                    <option value="EN">Engleza</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Moneda</label>
                                <input type="text" wire:model="formulare.{{ $cod }}.currency" maxlength="3"
                                       placeholder="RON"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono uppercase" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Termen plata
                                    <span class="text-gray-500">(zile dupa data livrare)</span>
                                </label>
                                <input type="number" wire:model="formulare.{{ $cod }}.dueDateOffsetDays" min="0" max="365"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            </div>
                        </div>
                    @else
                        {{-- SmartBill --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Username (email cont SmartBill) *</label>
                                <input type="email" wire:model="formulare.{{ $cod }}.username" maxlength="255"
                                       placeholder="exemplu@firma.ro"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                @error("formulare.{$cod}.username") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">API Token *</label>
                                <input type="password" wire:model="formulare.{{ $cod }}.token" maxlength="255"
                                       placeholder="generat din contul SmartBill"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono" />
                                @error("formulare.{$cod}.token") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">CIF emitent (companyVatCode) *</label>
                                <input type="text" wire:model="formulare.{{ $cod }}.companyVatCode" maxlength="20"
                                       placeholder="ex: RO46043131"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono uppercase" />
                                @error("formulare.{$cod}.companyVatCode") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Serie factura *</label>
                                <input type="text" wire:model="formulare.{{ $cod }}.seriesName" maxlength="20"
                                       placeholder="ex: WF"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono uppercase" />
                                @error("formulare.{$cod}.seriesName") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Moneda</label>
                                <input type="text" wire:model="formulare.{{ $cod }}.currency" maxlength="3"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono uppercase" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Termen plata <span class="text-gray-500">(zile dupa livrare)</span>
                                </label>
                                <input type="number" wire:model="formulare.{{ $cod }}.dueDateOffsetDays" min="0" max="365"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" wire:model="formulare.{{ $cod }}.isDraft"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span class="ml-2">Emite facturi ca <strong>draft</strong> (nefiscalizate) — util pentru testare</span>
                                </label>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex flex-wrap gap-2 justify-end">
                        <button type="button" wire:click="testeazaConexiune('{{ $cod }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 text-sm rounded-md">
                            <x-heroicon-m-bolt class="w-4 h-4" />
                            Test conexiune
                        </button>
                        <button type="button" wire:click="salveaza('{{ $cod }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                            <x-heroicon-m-check class="w-4 h-4" />
                            Salveaza
                        </button>
                        @if($estActiv)
                            <button type="button" wire:click="dezactiveaza('{{ $cod }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-md">
                                <x-heroicon-m-x-mark class="w-4 h-4" />
                                Dezactiveaza
                            </button>
                        @else
                            <button type="button" wire:click="activeaza('{{ $cod }}')"
                                    @if(! $estConfigurat) disabled title="Salveaza intai setarile complete" @endif
                                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-md
                                           {{ ! $estConfigurat ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <x-heroicon-m-power class="w-4 h-4" />
                                Activeaza
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
