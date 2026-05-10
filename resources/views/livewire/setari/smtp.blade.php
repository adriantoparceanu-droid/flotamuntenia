<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-paper-airplane class="w-6 h-6 text-indigo-600" />
            Setari SMTP
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if($mesaj)
                <div class="px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                    {{ $mesaj }}
                </div>
            @endif
            @if($eroare)
                <div class="px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm flex items-center gap-2">
                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-500 flex-shrink-0" />
                    {{ $eroare }}
                </div>
            @endif

            {{-- Status badge --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-indigo-500" />
                    <div>
                        <p class="text-sm text-gray-700">
                            Configureaza serverul SMTP folosit pentru trimiterea email-urilor catre clienti.
                            Parola e <strong>criptata in DB</strong> (nu e stocata in <code>.env</code>).
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Daca SMTP-ul nu e configurat sau dezactivat, email-urile sunt loggate in <code>storage/logs/email-pending.log</code> in loc sa fie trimise.
                        </p>
                    </div>
                </div>
                @if($cfgActiv && $cfgActiv->esteConfigurat())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <x-heroicon-s-check-circle class="w-4 h-4" />
                        Activ ({{ $cfgActiv->host }}:{{ $cfgActiv->port }})
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                        <x-heroicon-s-exclamation-triangle class="w-4 h-4" />
                        Neconfigurat
                    </span>
                @endif
            </div>

            {{-- Form --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-server class="w-5 h-5 text-indigo-600" />
                    Configurare server
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Host SMTP</label>
                        <input type="text" wire:model="host" placeholder="ex: smtp.gmail.com"
                               class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        @error('host') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Port</label>
                        <input type="number" wire:model="port" min="1" max="65535"
                               class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        @error('port') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-500 mt-1">Tipic: 587 (TLS), 465 (SSL), 25 (none)</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Encriptie</label>
                        <select wire:model="encryption"
                                class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="tls">TLS (recomandat)</option>
                            <option value="ssl">SSL</option>
                            <option value="none">Fara (insecure)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" wire:model="username" autocomplete="off"
                               class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Parola</label>
                        <input type="password" wire:model="password" autocomplete="new-password"
                               placeholder="{{ $editandId ? '(lasa gol pentru a pastra parola existenta)' : '' }}"
                               class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        @if($editandId)
                            <p class="text-xs text-gray-500 mt-1">Parola e criptata in DB; nu e pre-afisata aici din motive de securitate.</p>
                        @endif
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Adresa expeditor</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Email „de la"</label>
                            <input type="email" wire:model="fromEmail" placeholder="ex: contact@flotamuntenia.ro"
                                   class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            @error('fromEmail') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Nume expeditor</label>
                            <input type="text" wire:model="fromName"
                                   class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            @error('fromName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4 flex items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="activ"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span>Activ (foloseste aceasta configurare pentru toate email-urile)</span>
                    </label>
                    <button type="button" wire:click="salveaza"
                            class="inline-flex items-center gap-1.5 text-sm px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 font-medium">
                        <x-heroicon-o-check class="w-4 h-4" />
                        Salveaza setarile
                    </button>
                </div>
            </div>

            {{-- Email de test --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-paper-airplane class="w-5 h-5 text-indigo-600" />
                        Trimite email de test
                    </h3>
                </div>

                <p class="text-sm text-gray-600">
                    Verifica configurarea SMTP trimitand un email de test simplu. Foloseste aceasta optiune <strong>dupa salvare</strong>
                    pentru a confirma ca setarile sunt corecte si serverul accepta conexiunea.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Adresa destinatar</label>
                        <input type="email" wire:model="emailTest" placeholder="ex: tu@firma.ro"
                               class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        @error('emailTest') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="button" wire:click="trimiteEmailDeTest"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-1.5 text-sm px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 font-medium">
                        <span wire:loading.remove wire:target="trimiteEmailDeTest">
                            <x-heroicon-o-paper-airplane class="w-4 h-4 inline" />
                            Trimite test
                        </span>
                        <span wire:loading wire:target="trimiteEmailDeTest">Se trimite...</span>
                    </button>
                </div>

                @if($rezultatTest !== null)
                    <div @class([
                        'px-4 py-3 rounded text-sm flex items-start gap-2',
                        'bg-green-50 text-green-800 border border-green-200' => $rezultatTestOk,
                        'bg-red-50 text-red-800 border border-red-200' => ! $rezultatTestOk,
                    ])>
                        @if($rezultatTestOk)
                            <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
                        @else
                            <x-heroicon-s-x-circle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                        @endif
                        <span>{{ $rezultatTest }}</span>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
