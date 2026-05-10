<div>
    @php
        use App\Models\Dozator;
        use App\Models\DozatorFiltre;
        $esteFiltre = $tipDozator === 'filtre';
    @endphp

    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-wrench-screwdriver class="w-6 h-6 text-indigo-600" />
            Mentenanta dozatoare
            @if($totalScadente > 0)
                <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-700 font-medium"
                      title="Bidoane scadente: {{ $totalScadenteBidoane }} / Filtre scadente: {{ $totalScadenteFiltre }}">
                    <x-heroicon-m-bell-alert class="w-3.5 h-3.5" />
                    {{ $totalScadente }} scadente
                </span>
            @endif
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

                {{-- Toggle Bidoane / Filtre --}}
                <div class="flex items-center gap-1 mb-4 p-1 bg-gray-100 dark:bg-gray-900 rounded-md w-fit">
                    <button type="button" wire:click="comutaTip('bidoane')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-sm font-medium transition
                                {{ ! $esteFiltre ? 'bg-white text-indigo-700 shadow-sm dark:bg-gray-700 dark:text-indigo-300' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100' }}">
                        <x-heroicon-o-cube class="w-4 h-4" />
                        Bidoane
                        @if($totalScadenteBidoane > 0)
                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-700">
                                {{ $totalScadenteBidoane }}
                            </span>
                        @endif
                    </button>
                    <button type="button" wire:click="comutaTip('filtre')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-sm font-medium transition
                                {{ $esteFiltre ? 'bg-white text-indigo-700 shadow-sm dark:bg-gray-700 dark:text-indigo-300' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100' }}">
                        <x-heroicon-o-funnel class="w-4 h-4" />
                        Filtre
                        @if($totalScadenteFiltre > 0)
                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-700">
                                {{ $totalScadenteFiltre }}
                            </span>
                        @endif
                    </button>
                </div>

                {{-- Bara actiuni --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                    <div class="relative w-full lg:w-96">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                        <input type="text" wire:model.live.debounce.300ms="cautare"
                               placeholder="Cauta dupa client, cod, CIF, serie, ID..."
                               class="pl-9 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm w-full" />
                    </div>

                    <button type="button" wire:click="nou"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        {{ $esteFiltre ? 'Dozator filtru nou' : 'Dozator nou' }}
                    </button>
                </div>

                {{-- Filtre --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-md">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Client</label>
                        <select wire:model.live="filtruClient"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                            <option value="">Toti</option>
                            @foreach($clienti as $cl)
                                <option value="{{ $cl->id }}">{{ $cl->denumire }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Masina</label>
                        <select wire:model.live="filtruMasina"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                            <option value="">Toate</option>
                            @foreach($masini as $m)
                                <option value="{{ $m->id }}">{{ $m->denumire }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">
                            {{ $esteFiltre ? 'Status mentenanta' : 'Status igienizare' }}
                        </label>
                        <select wire:model.live="filtruStatus"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                            <option value="toate">Toate</option>
                            <option value="la_zi">La zi (>30 zile)</option>
                            <option value="scadent_30">Scadent 30 zile</option>
                            <option value="scadent_15">Urgent (15 zile)</option>
                            <option value="expirat">Expirat</option>
                            <option value="fara_data">Fara data</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Stare</label>
                        <select wire:model.live="filtruActiv"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                            <option value="activ">{{ $esteFiltre ? 'Active' : 'Active' }}</option>
                            <option value="inactiv">{{ $esteFiltre ? 'Retrase' : 'Inactive' }}</option>
                            <option value="toate">Toate</option>
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
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Client / Adresa</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Tip</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Serie</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Tranzactie</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Instalat</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">
                                    {{ $esteFiltre ? 'Mentenanta' : 'Igienizare' }}
                                </th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Masina</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse($dozatoare as $d)
                                @php
                                    $rowActiv = $esteFiltre ? $d->esteActiv() : (bool) $d->activ;
                                    $rowStatus = $esteFiltre ? $d->statusMentenanta() : $d->statusIgienizare();
                                    $rowCuloare = $esteFiltre ? $d->culoareStatusMentenanta() : $d->culoareStatusIgienizare();
                                    $rowEticheta = $esteFiltre ? $d->etichetaStatusMentenanta() : $d->etichetaStatusIgienizare();
                                    $rowDataScadenta = $esteFiltre ? $d->data_urmatoare_mentenanta : $d->perioada_igenizare;
                                    $rowCountIstoric = $esteFiltre ? ($d->istoric_count ?? 0) : ($d->vizite_count ?? 0);
                                    $rowUltimulReminder = $esteFiltre ? $d->ultimaNotificare() : $d->ultimulReminder();
                                @endphp
                                <tr class="{{ ! $rowActiv ? 'opacity-60' : '' }}">
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs">#{{ $d->id }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                        <a href="{{ route('clienti.detalii', $d->id_client) }}" wire:navigate
                                           class="font-medium hover:text-indigo-600">
                                            {{ $d->client?->denumire ?? '—' }}
                                        </a>
                                        @if($d->adresa)
                                            <span class="text-xs text-gray-500 block">{{ $d->adresa->denumire }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 text-xs">
                                        {{ $d->produs?->denumire ?? '—' }}
                                        @if($esteFiltre && (float) $d->suma_garantie > 0)
                                            <span class="block text-[10px] text-gray-400">Garantie: {{ number_format((float) $d->suma_garantie, 2, ',', '.') }} lei</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs">
                                        {{ $d->serie ?: '—' }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $d->tranzactie === ($esteFiltre ? DozatorFiltre::TRANZACTIE_CUMPARAT : Dozator::TRANZACTIE_CUMPARAT) ? 'bg-violet-100 text-violet-700' : 'bg-sky-100 text-sky-700' }}">
                                            {{ $d->etichetaTranzactie() }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap text-xs">
                                        {{ $d->data_instalare?->format('d.m.Y') ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-col items-start gap-1">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs {{ $rowCuloare }}">
                                                @if($rowStatus === 'la_zi')
                                                    <x-heroicon-m-check-circle class="w-3 h-3" />
                                                @elseif($rowStatus === 'expirat')
                                                    <x-heroicon-m-x-circle class="w-3 h-3" />
                                                @else
                                                    <x-heroicon-m-clock class="w-3 h-3" />
                                                @endif
                                                {{ $rowEticheta }}
                                            </span>
                                            @if($rowDataScadenta)
                                                <span class="text-[11px] text-gray-500">
                                                    Scadent: {{ $rowDataScadenta->format('d.m.Y') }}
                                                </span>
                                            @endif
                                            @if($rowCountIstoric > 0)
                                                <span class="text-[11px] text-gray-400">
                                                    @if($esteFiltre)
                                                        {{ $rowCountIstoric }} {{ $rowCountIstoric === 1 ? 'interventie' : 'interventii' }} in istoric
                                                    @else
                                                        {{ $rowCountIstoric }} {{ $rowCountIstoric === 1 ? 'vizita' : 'vizite' }} in istoric
                                                    @endif
                                                </span>
                                            @endif
                                            @if($rowUltimulReminder)
                                                <span class="text-[11px] text-amber-600">
                                                    @if($esteFiltre)
                                                        Notificare ({{ $rowUltimulReminder->etichetaTip() }}) trimisa: {{ $rowUltimulReminder->data_trimitere->format('d.m.Y H:i') }}
                                                    @else
                                                        Reminder trimis: {{ $rowUltimulReminder->trimis_la->format('d.m.Y H:i') }}
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                        @if($d->masina)
                                            <span class="inline-flex items-center gap-1 text-xs">
                                                <span class="w-2 h-2 rounded-full" style="background:{{ $d->masina->culoare }}"></span>
                                                {{ $d->masina->denumire }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-3">
                                            @if($rowActiv && $d->necesitaReminder() && $d->client?->email)
                                                @if($esteFiltre)
                                                    @php
                                                        $tipAuto = $d->tipReminderAuto();
                                                        $etichetaTip = $tipAuto === '15_zile' ? '15 zile' : '30 zile';
                                                        $textReminder = ($d->ultimaNotificare() ? 'Re-trimite' : 'Trimite') . " notificare ({$etichetaTip}) catre " . $d->client->email;
                                                    @endphp
                                                @else
                                                    @php $textReminder = ($d->ultimulReminder() ? 'Re-trimite reminder' : 'Trimite reminder') . ' catre ' . $d->client->email; @endphp
                                                @endif
                                                <button type="button" wire:click="trimiteReminder({{ $d->id }})"
                                                        title="{{ $textReminder }}"
                                                        aria-label="{{ $textReminder }}"
                                                        class="text-amber-600 hover:text-amber-800">
                                                    <x-heroicon-o-bell-alert class="w-5 h-5" />
                                                </button>
                                            @endif
                                            @if($rowActiv)
                                                @if($esteFiltre)
                                                    <button type="button" wire:click="marcheazaInterventieAzi({{ $d->id }})"
                                                            wire:confirm="Marcheaza interventie efectuata azi pentru dozatorul filtru #{{ $d->id }}? Urmatoarea va fi {{ now()->addMonths(12)->format('d.m.Y') }}."
                                                            title="Marcheaza interventie efectuata azi (auto +12 luni)"
                                                            aria-label="Marcheaza interventie efectuata azi"
                                                            class="text-emerald-600 hover:text-emerald-800">
                                                        <x-heroicon-o-check-badge class="w-5 h-5" />
                                                    </button>
                                                @else
                                                    <button type="button" wire:click="marcheazaIgienizareAzi({{ $d->id }})"
                                                            wire:confirm="Marcheaza igienizare efectuata azi pentru dozator #{{ $d->id }}? Urmatoarea va fi {{ now()->addMonths(6)->format('d.m.Y') }}."
                                                            title="Marcheaza igienizare efectuata azi (auto +6 luni)"
                                                            aria-label="Marcheaza igienizare efectuata azi"
                                                            class="text-emerald-600 hover:text-emerald-800">
                                                        <x-heroicon-o-check-badge class="w-5 h-5" />
                                                    </button>
                                                @endif
                                            @endif
                                            @if($esteFiltre)
                                                <button type="button" wire:click="deschideModalIstoric({{ $d->id }})"
                                                        title="Istoric interventii + adauga manual"
                                                        aria-label="Istoric interventii"
                                                        class="text-gray-500 hover:text-indigo-600">
                                                    <x-heroicon-o-clipboard-document-list class="w-5 h-5" />
                                                </button>
                                            @else
                                                <button type="button" wire:click="deschideModalVizite({{ $d->id }})"
                                                        title="Istoric vizite + adauga manual"
                                                        aria-label="Vizite igienizare"
                                                        class="text-gray-500 hover:text-indigo-600">
                                                    <x-heroicon-o-clipboard-document-list class="w-5 h-5" />
                                                </button>
                                            @endif
                                            <button type="button" wire:click="editeaza({{ $d->id }})"
                                                    title="Editeaza dozator"
                                                    aria-label="Editeaza dozator"
                                                    class="text-indigo-600 hover:text-indigo-800">
                                                <x-heroicon-o-pencil-square class="w-5 h-5" />
                                            </button>
                                            <button type="button" wire:click="comutaActiv({{ $d->id }})"
                                                    title="{{ $rowActiv ? ($esteFiltre ? 'Marcheaza ca retras' : 'Marcheaza ca recuperat (dezactiveaza)') : 'Reactiveaza dozator' }}"
                                                    aria-label="{{ $rowActiv ? ($esteFiltre ? 'Retrage' : 'Recuperat') : 'Reactivare' }}"
                                                    class="{{ $rowActiv ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                                @if($rowActiv)
                                                    <x-heroicon-o-arrow-uturn-left class="w-5 h-5" />
                                                @else
                                                    <x-heroicon-o-arrow-uturn-right class="w-5 h-5" />
                                                @endif
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-12 text-center">
                                        <x-heroicon-o-wrench-screwdriver class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                        <p class="text-sm text-gray-500">
                                            @if($esteFiltre)
                                                Nu exista dozatoare cu filtre care sa corespunda filtrelor.
                                            @else
                                                Nu exista dozatoare care sa corespunda filtrelor.
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $dozatoare->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal CRUD dozator --}}
    <div x-data="{ deschis: @entangle('modalDozator') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalDozator()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideModalDozator()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-2xl sm:mx-auto p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <x-heroicon-o-wrench-screwdriver class="w-5 h-5 text-indigo-600" />
                @if($esteFiltre)
                    {{ $dozatorId ? 'Editeaza dozator filtru #' . $dozatorId : 'Dozator filtru nou' }}
                @else
                    {{ $dozatorId ? 'Editeaza dozator #' . $dozatorId : 'Dozator nou' }}
                @endif
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                {{-- Client --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Client *</label>
                    <select wire:model.live="idClient"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                        <option value="">— Selecteaza —</option>
                        @foreach($clienti as $cl)
                            <option value="{{ $cl->id }}">{{ $cl->denumire }} {{ $cl->cod_client ? '— ' . $cl->cod_client : '' }}</option>
                        @endforeach
                    </select>
                    @error('idClient') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                {{-- Adresa (cascade pe client) --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Adresa de livrare *</label>
                    <select wire:model="idAdresa"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"
                            @disabled(! $idClient)>
                        <option value="">@if($idClient) — Selecteaza adresa — @else Selecteaza intai clientul @endif</option>
                        @foreach($adreseClient as $a)
                            <option value="{{ $a->id }}">{{ $a->denumire }} ({{ $a->oras }})</option>
                        @endforeach
                    </select>
                    @error('idAdresa') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                {{-- Tip dozator (din catalog) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ $esteFiltre ? 'Tip dozator filtru *' : 'Tip dozator *' }}
                    </label>
                    <select wire:model="idProdus"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                        <option value="">— Selecteaza —</option>
                        @foreach($produse as $p)
                            <option value="{{ $p->id }}">{{ $p->denumire }}</option>
                        @endforeach
                    </select>
                    @error('idProdus') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                {{-- Serie --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Numar serie</label>
                    <input type="text" wire:model="serie"
                           placeholder="Ex: SN-12345"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                </div>

                {{-- Tranzactie --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Tranzactie *</label>
                    <select wire:model="tranzactie"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                        <option value="custodie">Custodie (mişcare CUSTODIE)</option>
                        <option value="cumparat">Cumparat (mişcare OUT)</option>
                    </select>
                </div>

                {{-- Depozit sursa --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Depozit sursa</label>
                    <select wire:model="idDepozit"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                        <option value="">— Fara —</option>
                        @foreach($depozite as $dp)
                            <option value="{{ $dp->id }}">{{ $dp->denumire }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Data instalare --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Data instalare *</label>
                    <input type="date" wire:model.live="dataInstalare"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                    @error('dataInstalare') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                {{-- Scadenta urmatoare (Bidoane vs Filtre) --}}
                @if($esteFiltre)
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Urmatoarea mentenanta *</label>
                        <input type="date" wire:model="dataUrmatoareMentenanta"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        <p class="text-[11px] text-gray-400 mt-0.5">Auto-prefill = data instalare + 12 luni</p>
                        @error('dataUrmatoareMentenanta') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Urmatoarea igienizare</label>
                        <input type="date" wire:model="perioadaIgenizare"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        <p class="text-[11px] text-gray-400 mt-0.5">Auto-prefill = data instalare + 6 luni</p>
                    </div>
                @endif

                {{-- Masina --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Masina responsabila</label>
                    <select wire:model="idMasina"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                        <option value="">— Fara —</option>
                        @foreach($masini as $m)
                            <option value="{{ $m->id }}">{{ $m->denumire }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filtre: garantie | Bidoane: programat livrare --}}
                @if($esteFiltre)
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Garantie (lei)</label>
                        <input type="number" step="0.01" min="0" wire:model="sumaGarantie"
                               placeholder="0.00"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        <p class="text-[11px] text-gray-400 mt-0.5">Garantia perceputa la instalare</p>
                        @error('sumaGarantie') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                @else
                    <div class="flex items-center gap-2 mt-4">
                        <input type="checkbox" wire:model="comanda" id="comanda-flag"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <label for="comanda-flag" class="text-xs text-gray-700 dark:text-gray-300">
                            Programat pentru livrare/ridicare la urmatoarea cursa
                        </label>
                    </div>
                @endif

                {{-- Observatii --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Observatii</label>
                    <textarea wire:model="observatii" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" wire:click="inchideModalDozator"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                    <x-heroicon-m-x-mark class="w-4 h-4" />
                    Anuleaza
                </button>
                <button type="button" wire:click="salveaza"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                    <x-heroicon-m-check class="w-4 h-4" />
                    Salveaza
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Vizite (Bidoane) --}}
    <div x-data="{ deschis: @entangle('modalVizite') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalVizite()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideModalVizite()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-2xl sm:mx-auto p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-indigo-600" />
                Vizite igienizare {{ $viziteForDozatorId ? '— dozator #' . $viziteForDozatorId : '' }}
            </h3>

            <div class="bg-gray-50 dark:bg-gray-900 rounded p-3 mb-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Data vizita *</label>
                        <input type="date" wire:model="vizDataVizita"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                        @error('vizDataVizita') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Urmatoarea</label>
                        <input type="date" wire:model="vizDataUrmatoare"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                        @error('vizDataUrmatoare') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Pret (lei)</label>
                        <input type="number" step="0.01" wire:model="vizPret" placeholder="0.00"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                    </div>
                    <div class="flex items-end">
                        <button type="button" wire:click="adaugaVizita"
                                class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs rounded">
                            <x-heroicon-m-plus class="w-3.5 h-3.5" />
                            Adauga vizita
                        </button>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Observatii</label>
                    <input type="text" wire:model="vizObservatii"
                           placeholder="Optional"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                </div>
            </div>

            <div class="overflow-x-auto max-h-96">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0">
                        <tr>
                            <th class="px-2 py-1.5 text-left text-gray-600">Data</th>
                            <th class="px-2 py-1.5 text-left text-gray-600">Urmatoarea</th>
                            <th class="px-2 py-1.5 text-right text-gray-600">Pret (lei)</th>
                            <th class="px-2 py-1.5 text-left text-gray-600">Observatii</th>
                            <th class="px-2 py-1.5 text-center text-gray-600">Achitat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($vizitepDozator as $v)
                            <tr>
                                <td class="px-2 py-1.5 whitespace-nowrap">{{ $v->data_vizita?->format('d.m.Y') ?? '—' }}</td>
                                <td class="px-2 py-1.5 whitespace-nowrap text-gray-500">{{ $v->data_urmatoare?->format('d.m.Y') ?? '—' }}</td>
                                <td class="px-2 py-1.5 text-right tabular-nums">{{ number_format($v->pret, 2, ',', '.') }}</td>
                                <td class="px-2 py-1.5 text-gray-500">{{ $v->observatii ?? '—' }}</td>
                                <td class="px-2 py-1.5 text-center">
                                    @if($v->achitat)
                                        <x-heroicon-m-check-circle class="w-4 h-4 text-emerald-500 inline" />
                                    @else
                                        <x-heroicon-m-x-circle class="w-4 h-4 text-gray-300 inline" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-2 py-6 text-center text-gray-400">Nu exista vizite inregistrate.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" wire:click="inchideModalVizite"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                    <x-heroicon-m-x-mark class="w-4 h-4" />
                    Inchide
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Istoric (Filtre: interventii) --}}
    <div x-data="{ deschis: @entangle('modalIstoric') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalIstoric()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideModalIstoric()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-2xl sm:mx-auto p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-indigo-600" />
                Istoric interventii {{ $istoricForFiltruId ? '— dozator filtru #' . $istoricForFiltruId : '' }}
            </h3>

            <div class="bg-gray-50 dark:bg-gray-900 rounded p-3 mb-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Data interventie *</label>
                        <input type="date" wire:model="intDataInterventie"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                        @error('intDataInterventie') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Urmatoarea</label>
                        <input type="date" wire:model="intDataUrmatoare"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                        @error('intDataUrmatoare') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Pret (lei)</label>
                        <input type="number" step="0.01" wire:model="intPret" placeholder="0.00"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                    </div>
                    <div class="flex items-end">
                        <button type="button" wire:click="adaugaInterventie"
                                class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs rounded">
                            <x-heroicon-m-plus class="w-3.5 h-3.5" />
                            Adauga interventie
                        </button>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Observatii</label>
                    <input type="text" wire:model="intObservatii"
                           placeholder="Optional"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                </div>
            </div>

            <div class="overflow-x-auto max-h-96">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0">
                        <tr>
                            <th class="px-2 py-1.5 text-left text-gray-600">Data interventie</th>
                            <th class="px-2 py-1.5 text-left text-gray-600">Urmatoarea</th>
                            <th class="px-2 py-1.5 text-right text-gray-600">Pret (lei)</th>
                            <th class="px-2 py-1.5 text-left text-gray-600">Observatii</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($istoricFiltru as $iv)
                            <tr>
                                <td class="px-2 py-1.5 whitespace-nowrap">{{ $iv->data_interventie?->format('d.m.Y') ?? '—' }}</td>
                                <td class="px-2 py-1.5 whitespace-nowrap text-gray-500">{{ $iv->data_urmatoare?->format('d.m.Y') ?? '—' }}</td>
                                <td class="px-2 py-1.5 text-right tabular-nums">{{ number_format($iv->pret, 2, ',', '.') }}</td>
                                <td class="px-2 py-1.5 text-gray-500">{{ $iv->observatii ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-2 py-6 text-center text-gray-400">Nu exista interventii inregistrate.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" wire:click="inchideModalIstoric"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                    <x-heroicon-m-x-mark class="w-4 h-4" />
                    Inchide
                </button>
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
                        Dozator <span class="font-medium">{{ $denumireDeSters }}</span> va fi sters permanent impreuna cu istoricul.
                        Mişcarea de stoc generata va fi reversata.
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
