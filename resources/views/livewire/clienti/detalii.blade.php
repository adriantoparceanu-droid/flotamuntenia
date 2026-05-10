@php
    use App\Models\Produs;
    use App\Models\Dozator as DozatorModel;
    use App\Models\DozatorFiltre as DozatorFiltreModel;
@endphp
<div>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('clienti.index') }}" wire:navigate
               class="text-gray-400 hover:text-gray-600" title="Inapoi la lista">
                <x-heroicon-o-arrow-left class="w-5 h-5" />
            </a>
            <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <x-heroicon-o-users class="w-6 h-6 text-indigo-600" />
                {{ $client->denumire }}
            </h2>
            @if($client->isPJ())
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">
                    <x-heroicon-m-building-office-2 class="w-3 h-3" /> PJ
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700">
                    <x-heroicon-m-user class="w-3 h-3" /> PF
                </span>
            @endif
            @if($client->reziliat)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700">
                    <x-heroicon-s-x-circle class="w-3.5 h-3.5" /> Reziliat
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">
                    <x-heroicon-s-check-circle class="w-3.5 h-3.5" /> Activ
                </span>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('mesaj'))
                <div class="px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                    {{ session('mesaj') }}
                </div>
            @endif

            {{-- Bara actiuni client --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="text-sm text-gray-500">
                    Cod: <span class="font-mono">{{ $client->cod_client }}</span>
                    @if($client->data_adaugare)
                        · Inregistrat la {{ $client->data_adaugare->format('d M Y') }}
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('clienti.editare', $client) }}" wire:navigate
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-md whitespace-nowrap">
                        <x-heroicon-m-pencil-square class="w-4 h-4 flex-shrink-0" />
                        Editeaza datele
                    </a>
                    @if($client->reziliat)
                        <button wire:click="reactiveaza"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-md whitespace-nowrap">
                            <x-heroicon-m-arrow-path class="w-4 h-4 flex-shrink-0" />
                            Reactiveaza
                        </button>
                    @else
                        <button wire:click="deschideModalReziliere"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm rounded-md whitespace-nowrap">
                            <x-heroicon-m-x-circle class="w-4 h-4 flex-shrink-0" />
                            Reziliaza
                        </button>
                    @endif
                </div>
            </div>

            {{-- Tab-uri --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 dark:border-gray-700 px-4 overflow-x-auto">
                    <nav class="-mb-px flex gap-x-5 min-w-max">
                        @php
                            $tabClasses = fn($t) => 'inline-flex items-center gap-1.5 py-3 border-b-2 text-sm font-medium whitespace-nowrap '
                                . ($tab === $t ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700');
                            // Badge mic care marcheaza un tab inca neimplementat (faza viitoare).
                            $badgeFaza = '<span class="ml-1 px-1.5 py-0.5 rounded bg-gray-100 text-gray-400 text-[10px] font-mono">F%d</span>';
                        @endphp
                        <button wire:click="comutaTab('general')" class="{{ $tabClasses('general') }}">
                            <x-heroicon-o-identification class="w-4 h-4" />
                            General
                        </button>
                        <button wire:click="comutaTab('adrese')" class="{{ $tabClasses('adrese') }}">
                            <x-heroicon-o-map-pin class="w-4 h-4" />
                            Adrese
                            <span class="ml-1 px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-700 text-xs">{{ $numarAdrese }}</span>
                        </button>
                        <button wire:click="comutaTab('comenzi')" class="{{ $tabClasses('comenzi') }}">
                            <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                            Comenzi
                            {!! sprintf($badgeFaza, 2) !!}
                        </button>
                        <button wire:click="comutaTab('probleme')" class="{{ $tabClasses('probleme') }}">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                            Probleme
                            <span class="ml-1 px-1.5 py-0.5 rounded-full bg-rose-100 text-rose-700 text-xs">{{ $numarProbleme }}</span>
                        </button>
                        <button wire:click="comutaTab('dozatoare')" class="{{ $tabClasses('dozatoare') }}">
                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4" />
                            Dozatoare
                            <span class="ml-1 px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs"
                                  title="Bidoane: {{ $numarDozatoare }} / Filtre: {{ $numarFiltre }}">
                                {{ $numarDozatoare + $numarFiltre }}
                            </span>
                        </button>
                        <button wire:click="comutaTab('recipienti')" class="{{ $tabClasses('recipienti') }}">
                            <x-heroicon-o-archive-box class="w-4 h-4" />
                            Recipienti
                            {!! sprintf($badgeFaza, 4) !!}
                        </button>
                        <button wire:click="comutaTab('contract')" class="{{ $tabClasses('contract') }}">
                            <x-heroicon-o-document-duplicate class="w-4 h-4" />
                            Contract
                        </button>
                        <button wire:click="comutaTab('documente')" class="{{ $tabClasses('documente') }}">
                            <x-heroicon-o-folder-open class="w-4 h-4" />
                            Documente
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    {{-- Tab General --}}
                    @if($tab === 'general')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <x-heroicon-o-identification class="w-5 h-5" /> Date de identificare
                                </h3>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">Tip</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">{{ $client->isPJ() ? 'Persoana juridica' : 'Persoana fizica' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">Cod client</dt>
                                        <dd class="text-gray-900 dark:text-gray-100 font-mono">{{ $client->cod_client }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">{{ $client->isPJ() ? 'CIF' : 'CNP' }}</dt>
                                        <dd class="text-gray-900 dark:text-gray-100 font-mono">{{ $client->cif ?: '—' }}</dd>
                                    </div>
                                    @if($client->isPJ())
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-gray-500">Reg. Com.</dt>
                                            <dd class="text-gray-900 dark:text-gray-100 font-mono">{{ $client->reg_com ?: '—' }}</dd>
                                        </div>
                                    @endif
                                </dl>

                                <h3 class="mt-6 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <x-heroicon-o-envelope class="w-5 h-5" /> Contact
                                </h3>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">Email</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            @if($client->email)
                                                <a href="mailto:{{ $client->email }}" class="hover:text-indigo-600">{{ $client->email }}</a>
                                            @else — @endif
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">Telefon</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            @if($client->telefon)
                                                <a href="tel:{{ $client->telefon }}" class="hover:text-indigo-600">{{ $client->telefon }}</a>
                                            @else — @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-5 h-5" />
                                    {{ $client->isPJ() ? 'Sediu social' : 'Domiciliu' }}
                                </h3>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">Oras</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">{{ $client->oras ?: '—' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">Strada</dt>
                                        <dd class="text-gray-900 dark:text-gray-100 text-right">{{ $client->strada ?: '—' }}</dd>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div><dt class="text-gray-500 text-xs">Nr.</dt><dd>{{ $client->nr ?: '—' }}</dd></div>
                                        <div><dt class="text-gray-500 text-xs">Bloc</dt><dd>{{ $client->bloc ?: '—' }}</dd></div>
                                        <div><dt class="text-gray-500 text-xs">Scara</dt><dd>{{ $client->scara ?: '—' }}</dd></div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div><dt class="text-gray-500 text-xs">Etaj</dt><dd>{{ $client->etaj ?: '—' }}</dd></div>
                                        <div><dt class="text-gray-500 text-xs">Apt.</dt><dd>{{ $client->apartament ?: '—' }}</dd></div>
                                        <div><dt class="text-gray-500 text-xs">Sector</dt><dd>{{ $client->sector ?: '—' }}</dd></div>
                                    </div>
                                    @if($client->interfon)
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-gray-500">Interfon</dt>
                                            <dd class="text-gray-900 dark:text-gray-100">{{ $client->interfon }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        </div>

                        @if($client->observatii || $client->observatii_reziliere)
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 space-y-4">
                                @if($client->observatii)
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-2">
                                            <x-heroicon-o-pencil-square class="w-5 h-5" /> Observatii interne
                                        </h3>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $client->observatii }}</p>
                                    </div>
                                @endif
                                @if($client->reziliat && $client->observatii_reziliere)
                                    <div class="border-l-4 border-red-300 bg-red-50 px-4 py-2 rounded">
                                        <h3 class="text-sm font-semibold text-red-700 mb-1 flex items-center gap-2">
                                            <x-heroicon-o-x-circle class="w-5 h-5" /> Motiv reziliere
                                        </h3>
                                        <p class="text-sm text-red-700 whitespace-pre-line">{{ $client->observatii_reziliere }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif

                    {{-- Tab Adrese --}}
                    @if($tab === 'adrese')
                        <div class="flex justify-end mb-4">
                            <button wire:click="adresaNoua"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                                <x-heroicon-m-plus class="w-4 h-4" />
                                Adauga adresa
                            </button>
                        </div>

                        @if($adrese->isEmpty())
                            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-map-pin class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                <p>Niciun punct de livrare inregistrat.</p>
                                <p class="text-xs mt-1">Adauga prima adresa pentru a putea genera comenzi.</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($adrese as $a)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 {{ $a->activ ? '' : 'opacity-60' }}">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $a->denumire }}</h4>
                                                    @if($a->activ)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">
                                                            <x-heroicon-s-check-circle class="w-3 h-3" /> Activa
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-700">
                                                            <x-heroicon-s-x-circle class="w-3 h-3" /> Inactiva
                                                        </span>
                                                    @endif
                                                    @if($a->areCoordonateGps())
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-cyan-100 text-cyan-700" title="{{ $a->lat }}, {{ $a->lng }}">
                                                            <x-heroicon-m-map-pin class="w-3 h-3" /> GPS
                                                        </span>
                                                    @endif
                                                    @php $soldA = $solduriRecipienti[$a->id] ?? ['19l' => 0, '11l' => 0]; @endphp
                                                    @if($soldA['19l'] !== 0 || $soldA['11l'] !== 0)
                                                        @php
                                                            // Daca toate soldurile non-zero sunt negative -> chip albastru (datorie firma).
                                                            // Daca toate sunt pozitive sau mix -> chip amber (de recuperat sau mixt).
                                                            $toateNegative = ($soldA['19l'] <= 0 && $soldA['11l'] <= 0)
                                                                && ($soldA['19l'] < 0 || $soldA['11l'] < 0);
                                                            $stilSold = $toateNegative
                                                                ? 'bg-sky-100 text-sky-700 hover:bg-sky-200'
                                                                : 'bg-amber-100 text-amber-700 hover:bg-amber-200';
                                                        @endphp
                                                        <button type="button"
                                                                wire:click="filtreazaJurnalAdresa({{ $a->id }}); comutaTab('recipienti')"
                                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs {{ $stilSold }}"
                                                                title="Vezi jurnalul de recipienti pentru aceasta adresa">
                                                            <x-heroicon-m-archive-box class="w-3 h-3" />
                                                            Sold:
                                                            @if($soldA['19l'] !== 0) {{ $soldA['19l'] > 0 ? '+' : '' }}{{ $soldA['19l'] }}×19L @endif
                                                            @if($soldA['19l'] !== 0 && $soldA['11l'] !== 0) · @endif
                                                            @if($soldA['11l'] !== 0) {{ $soldA['11l'] > 0 ? '+' : '' }}{{ $soldA['11l'] }}×11L @endif
                                                        </button>
                                                    @endif
                                                </div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $a->adresaCompleta() ?: '—' }}</p>
                                                @if($a->interfon)
                                                    <p class="text-xs text-gray-500 mt-1">Interfon: {{ $a->interfon }}</p>
                                                @endif
                                            </div>
                                            <div class="flex flex-col gap-1 flex-shrink-0">
                                                <button wire:click="editeazaAdresa({{ $a->id }})"
                                                        class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm">
                                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                                    Editeaza
                                                </button>
                                                <button wire:click="comutaActivAdresa({{ $a->id }})"
                                                        class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 text-sm">
                                                    @if($a->activ)
                                                        <x-heroicon-m-eye-slash class="w-4 h-4" /> Dezactiveaza
                                                    @else
                                                        <x-heroicon-m-eye class="w-4 h-4" /> Activeaza
                                                    @endif
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Sectiune produs / configurare livrare --}}
                                        <div class="mt-3 pt-3 border-t border-dashed border-gray-200 dark:border-gray-700">
                                            @if($a->produs)
                                                @php $p = $a->produs; @endphp
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="flex-1 min-w-0 text-sm">
                                                        <div class="flex items-center gap-2 mb-1">
                                                            @php
                                                                $tipColors = [
                                                                    Produs::TIP_ABONAMENT => 'bg-indigo-100 text-indigo-700',
                                                                    Produs::TIP_PER_BUCATA => 'bg-amber-100 text-amber-700',
                                                                    Produs::TIP_FILTRE => 'bg-cyan-100 text-cyan-700',
                                                                    Produs::TIP_APARATE => 'bg-purple-100 text-purple-700',
                                                                ];
                                                                $tipIcons = [
                                                                    Produs::TIP_ABONAMENT => 'arrow-path',
                                                                    Produs::TIP_PER_BUCATA => 'shopping-bag',
                                                                    Produs::TIP_FILTRE => 'funnel',
                                                                    Produs::TIP_APARATE => 'cube',
                                                                ];
                                                            @endphp
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium {{ $tipColors[$p->abonament] ?? 'bg-gray-100 text-gray-700' }}">
                                                                <x-dynamic-component :component="'heroicon-m-' . ($tipIcons[$p->abonament] ?? 'tag')" class="w-3 h-3" />
                                                                {{ $p->etichetaTip() }}
                                                            </span>
                                                            @if($p->isAbonament() && $p->sumar())
                                                                <span class="text-gray-600 dark:text-gray-300">{{ $p->sumar() }}</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-xs text-gray-500 space-y-0.5">
                                                            @if($p->masina)
                                                                <div class="inline-flex items-center gap-1">
                                                                    <x-heroicon-m-truck class="w-3.5 h-3.5" />
                                                                    {{ $p->masina->denumire }}
                                                                    <span class="font-mono">({{ $p->masina->nr_inmatriculare }})</span>
                                                                </div>
                                                            @endif
                                                            @if($p->depozit)
                                                                <div class="inline-flex items-center gap-1">
                                                                    <x-heroicon-m-building-storefront class="w-3.5 h-3.5" />
                                                                    {{ $p->depozit->denumire }}
                                                                </div>
                                                            @endif
                                                            @if(in_array($p->abonament, [Produs::TIP_ABONAMENT, Produs::TIP_PER_BUCATA]) && ($p->pret > 0 || $p->pret_11l > 0))
                                                                <div class="inline-flex items-center gap-2">
                                                                    @if($p->pret > 0) <span>19L: {{ number_format($p->pret, 2) }} lei</span> @endif
                                                                    @if($p->pret_11l > 0) <span>11L: {{ number_format($p->pret_11l, 2) }} lei</span> @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <button wire:click="configureazaAbonament({{ $a->id }})"
                                                            class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm flex-shrink-0">
                                                        <x-heroicon-m-cog-6-tooth class="w-4 h-4" />
                                                        Editeaza configurare
                                                    </button>
                                                </div>
                                            @else
                                                <div class="flex items-center justify-between gap-4">
                                                    <div class="flex items-center gap-2 text-sm text-amber-700">
                                                        <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                                                        Fara configurare livrare
                                                    </div>
                                                    <button wire:click="configureazaAbonament({{ $a->id }})"
                                                            class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-md">
                                                        <x-heroicon-m-cog-6-tooth class="w-3.5 h-3.5" />
                                                        Configureaza livrare
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif

                    {{-- Tab Probleme — Faza 3.2 (real) --}}
                    @if($tab === 'probleme')
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-rose-600" />
                                    Probleme / Interventii
                                </h3>
                                <a href="{{ route('probleme.noua', ['id_client' => $client->id]) }}" wire:navigate
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-md">
                                    <x-heroicon-m-plus class="w-4 h-4" />
                                    Adauga problema
                                </a>
                            </div>

                            @if($probleme->isEmpty())
                                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                    <p class="text-sm">Niciun raport de problema pentru acest client.</p>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                        <thead class="bg-gray-50 dark:bg-gray-900 text-[11px] uppercase tracking-wide text-gray-500">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Data</th>
                                                <th class="px-3 py-2 text-left">Adresa</th>
                                                <th class="px-3 py-2 text-left">Descriere</th>
                                                <th class="px-3 py-2 text-right">Suma</th>
                                                <th class="px-3 py-2 text-left">Masina</th>
                                                <th class="px-3 py-2 text-center">Stare</th>
                                                <th class="px-3 py-2 text-right">Actiuni</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                            @foreach($probleme as $p)
                                                <tr class="{{ $p->livrat ? 'bg-emerald-50/30' : '' }}">
                                                    <td class="px-3 py-2 whitespace-nowrap text-gray-900 dark:text-gray-100">
                                                        {{ $p->data_livrare?->format('d.m.Y') }}
                                                        @if($p->interval_livrare)
                                                            <span class="text-[10px] text-gray-500 block">{{ $p->interval_livrare }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300">{{ $p->adresa?->denumire ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300 max-w-md">
                                                        <div class="line-clamp-2" title="{{ $p->descriere }}">{{ $p->descriere }}</div>
                                                    </td>
                                                    <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap">
                                                        {{ number_format($p->total(), 2, ',', '.') }} <span class="text-[10px] text-gray-500">lei</span>
                                                        <span class="text-[10px] text-gray-500 block">{{ $p->etichetaModPlata() }}</span>
                                                    </td>
                                                    <td class="px-3 py-2 text-xs">
                                                        @if($p->masina)
                                                            <span class="inline-flex items-center gap-1">
                                                                <span class="w-2 h-2 rounded-full" style="background:{{ $p->masina->culoare }}"></span>
                                                                {{ $p->masina->denumire }}
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400">neasignata</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-center">
                                                        <div class="flex flex-col items-center gap-0.5">
                                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] {{ $p->livrat ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                                @if($p->livrat)
                                                                    <x-heroicon-s-check-circle class="w-3 h-3" /> Rezolvata
                                                                @else
                                                                    <x-heroicon-m-wrench-screwdriver class="w-3 h-3" /> Nerezolvata
                                                                @endif
                                                            </span>
                                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] {{ $p->achitat ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                                @if($p->achitat)
                                                                    <x-heroicon-m-banknotes class="w-3 h-3" /> Achitata
                                                                @else
                                                                    <x-heroicon-m-banknotes class="w-3 h-3" /> Neachitata
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-2 text-right whitespace-nowrap">
                                                        <a href="{{ route('probleme.editare', $p) }}" wire:navigate
                                                           class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800">
                                                            <x-heroicon-m-pencil-square class="w-3.5 h-3.5" />
                                                            Editeaza
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Tab Dozatoare (Faza 4.1 + 4.3) - doua sectiuni --}}
                    @if($tab === 'dozatoare')
                        <div class="space-y-8">
                            {{-- Sectiunea Bidoane --}}
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-cube class="w-5 h-5 text-indigo-600" />
                                        <h3 class="font-medium text-gray-800 dark:text-gray-200">Dozatoare cu bidoane</h3>
                                        <span class="text-xs text-gray-500">{{ $dozatoare->count() }} inregistrari</span>
                                    </div>
                                    <a href="{{ route('dozatoare.index', ['id_client' => $client->id, 'tip' => 'bidoane', 'new' => 1]) }}" wire:navigate
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded">
                                        <x-heroicon-m-plus class="w-3.5 h-3.5" />
                                        Adauga dozator
                                    </a>
                                </div>

                                @if($dozatoare->isEmpty())
                                    <div class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 rounded">
                                        <x-heroicon-o-cube class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                                        <p class="text-sm">Niciun dozator cu bidoane inregistrat la acest client.</p>
                                    </div>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                            <thead class="bg-gray-50 dark:bg-gray-900">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Tip</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Adresa</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Serie</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Tranzactie</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Instalat</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Igienizare</th>
                                                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach($dozatoare as $d)
                                                    <tr class="{{ ! $d->activ ? 'opacity-60' : '' }}">
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 text-xs">{{ $d->produs?->denumire ?? '—' }}</td>
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 text-xs">{{ $d->adresa?->denumire ?? '—' }}</td>
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $d->serie ?: '—' }}</td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $d->tranzactie === DozatorModel::TRANZACTIE_CUMPARAT ? 'bg-violet-100 text-violet-700' : 'bg-sky-100 text-sky-700' }}">
                                                                {{ $d->etichetaTranzactie() }}
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 text-xs whitespace-nowrap">{{ $d->data_instalare?->format('d.m.Y') ?? '—' }}</td>
                                                        <td class="px-3 py-2">
                                                            <div class="flex flex-col items-start gap-0.5">
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs {{ $d->culoareStatusIgienizare() }}">
                                                                    {{ $d->etichetaStatusIgienizare() }}
                                                                </span>
                                                                @if($d->perioada_igenizare)
                                                                    <span class="text-[11px] text-gray-500">{{ $d->perioada_igenizare->format('d.m.Y') }}</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-2 text-right whitespace-nowrap">
                                                            <a href="{{ route('dozatoare.index', ['tip' => 'bidoane', 'edit' => $d->id]) }}" wire:navigate
                                                               class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800">
                                                                <x-heroicon-m-pencil-square class="w-3.5 h-3.5" />
                                                                Editeaza
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>

                            {{-- Sectiunea Filtre (Faza 4.3) --}}
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-funnel class="w-5 h-5 text-indigo-600" />
                                        <h3 class="font-medium text-gray-800 dark:text-gray-200">Dozatoare cu filtre</h3>
                                        <span class="text-xs text-gray-500">{{ $dozatoareFiltre->count() }} inregistrari</span>
                                    </div>
                                    <a href="{{ route('dozatoare.index', ['id_client' => $client->id, 'tip' => 'filtre', 'new' => 1]) }}" wire:navigate
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded">
                                        <x-heroicon-m-plus class="w-3.5 h-3.5" />
                                        Adauga dozator filtru
                                    </a>
                                </div>

                                @if($dozatoareFiltre->isEmpty())
                                    <div class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 rounded">
                                        <x-heroicon-o-funnel class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                                        <p class="text-sm">Niciun dozator cu filtre inregistrat la acest client.</p>
                                    </div>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                            <thead class="bg-gray-50 dark:bg-gray-900">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Tip</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Adresa</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Serie</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Tranzactie</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Instalat</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Mentenanta</th>
                                                    <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach($dozatoareFiltre as $df)
                                                    <tr class="{{ ! $df->esteActiv() ? 'opacity-60' : '' }}">
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 text-xs">
                                                            {{ $df->produs?->denumire ?? '—' }}
                                                            @if((float) $df->suma_garantie > 0)
                                                                <span class="block text-[10px] text-gray-400">Garantie: {{ number_format((float) $df->suma_garantie, 2, ',', '.') }} lei</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 text-xs">{{ $df->adresa?->denumire ?? '—' }}</td>
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $df->serie ?: '—' }}</td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs {{ $df->tranzactie === DozatorFiltreModel::TRANZACTIE_CUMPARAT ? 'bg-violet-100 text-violet-700' : 'bg-sky-100 text-sky-700' }}">
                                                                {{ $df->etichetaTranzactie() }}
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 text-xs whitespace-nowrap">{{ $df->data_instalare?->format('d.m.Y') ?? '—' }}</td>
                                                        <td class="px-3 py-2">
                                                            <div class="flex flex-col items-start gap-0.5">
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs {{ $df->culoareStatusMentenanta() }}">
                                                                    {{ $df->etichetaStatusMentenanta() }}
                                                                </span>
                                                                @if($df->data_urmatoare_mentenanta)
                                                                    <span class="text-[11px] text-gray-500">{{ $df->data_urmatoare_mentenanta->format('d.m.Y') }}</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-2 text-right whitespace-nowrap">
                                                            <a href="{{ route('dozatoare.index', ['tip' => 'filtre', 'edit' => $df->id]) }}" wire:navigate
                                                               class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800">
                                                                <x-heroicon-m-pencil-square class="w-3.5 h-3.5" />
                                                                Editeaza
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Tab Recipienti — Faza 4.2 (jurnal + admin) --}}
                    @if($tab === 'recipienti')
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <x-heroicon-o-archive-box class="w-5 h-5 text-amber-600" />
                                    Recipienti (bidoane)
                                </h3>
                                @if($recFiltruAdresa)
                                    <button wire:click="filtreazaJurnalAdresa(null)"
                                            class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700">
                                        <x-heroicon-m-x-mark class="w-3.5 h-3.5" />
                                        Anuleaza filtru adresa
                                    </button>
                                @endif
                            </div>

                            {{-- Sold curent agregat per client + per adresa --}}
                            @if($adrese->isEmpty())
                                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-map-pin class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                    <p class="text-sm">Adauga o adresa de livrare ca sa poti urmari recipientii.</p>
                                </div>
                            @else
                                @php
                                    // Helper de stil pentru carduri sold: negativ -> albastru (datorie firma),
                                    // pozitiv -> amber (de recuperat la client), zero -> gri.
                                    $stilSoldCard = function (int $v) {
                                        if ($v > 0) return ['bg' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800', 'lbl' => 'text-amber-700 dark:text-amber-300', 'val' => 'text-amber-900 dark:text-amber-100', 'sub' => 'text-amber-700/80', 'desc' => 'bidoane de recuperat la client'];
                                        if ($v < 0) return ['bg' => 'bg-sky-50 dark:bg-sky-900/20 border-sky-200 dark:border-sky-800', 'lbl' => 'text-sky-700 dark:text-sky-300', 'val' => 'text-sky-900 dark:text-sky-100', 'sub' => 'text-sky-700/80', 'desc' => 'bidoane datorie firma catre client'];
                                        return ['bg' => 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-700', 'lbl' => 'text-gray-600 dark:text-gray-400', 'val' => 'text-gray-700 dark:text-gray-200', 'sub' => 'text-gray-500', 'desc' => 'sold echilibrat'];
                                    };
                                    $st19 = $stilSoldCard($totalSold19l);
                                    $st11 = $stilSoldCard($totalSold11l);
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="border rounded-lg p-3 {{ $st19['bg'] }}">
                                        <div class="text-[11px] uppercase tracking-wide font-semibold {{ $st19['lbl'] }}">Total sold 19L</div>
                                        <div class="text-2xl font-bold tabular-nums {{ $st19['val'] }}">{{ $totalSold19l > 0 ? '+' : '' }}{{ $totalSold19l }}</div>
                                        <div class="text-xs {{ $st19['sub'] }}">{{ $st19['desc'] }}</div>
                                    </div>
                                    <div class="border rounded-lg p-3 {{ $st11['bg'] }}">
                                        <div class="text-[11px] uppercase tracking-wide font-semibold {{ $st11['lbl'] }}">Total sold 11L</div>
                                        <div class="text-2xl font-bold tabular-nums {{ $st11['val'] }}">{{ $totalSold11l > 0 ? '+' : '' }}{{ $totalSold11l }}</div>
                                        <div class="text-xs {{ $st11['sub'] }}">{{ $st11['desc'] }}</div>
                                    </div>
                                </div>

                                {{-- Card per adresa cu sold + buton corectie manuala --}}
                                <div class="space-y-2">
                                    @foreach($adrese as $a)
                                        @php $soldA = $solduriRecipienti[$a->id] ?? ['19l' => 0, '11l' => 0]; @endphp
                                        <div class="flex items-center justify-between gap-3 border border-gray-200 dark:border-gray-700 rounded-lg p-3 {{ $recFiltruAdresa === $a->id ? 'ring-2 ring-amber-400' : '' }}">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">{{ $a->denumire }}</div>
                                                <div class="text-xs text-gray-500 truncate">{{ $a->adresaCompleta() ?: '—' }}</div>
                                            </div>
                                            <div class="flex items-center gap-3 text-sm tabular-nums flex-shrink-0">
                                                @php
                                                    $stilCif = function (int $v) {
                                                        if ($v > 0) return 'text-amber-700';
                                                        if ($v < 0) return 'text-sky-700';
                                                        return 'text-gray-400';
                                                    };
                                                @endphp
                                                <div class="text-center">
                                                    <div class="text-[10px] uppercase tracking-wide text-gray-500">19L</div>
                                                    <div class="font-bold {{ $stilCif($soldA['19l']) }}">{{ $soldA['19l'] > 0 ? '+' : '' }}{{ $soldA['19l'] }}</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-[10px] uppercase tracking-wide text-gray-500">11L</div>
                                                    <div class="font-bold {{ $stilCif($soldA['11l']) }}">{{ $soldA['11l'] > 0 ? '+' : '' }}{{ $soldA['11l'] }}</div>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-1 flex-shrink-0">
                                                <button wire:click="filtreazaJurnalAdresa({{ $recFiltruAdresa === $a->id ? 'null' : $a->id }})"
                                                        class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700"
                                                        title="Filtreaza jurnalul">
                                                    <x-heroicon-m-funnel class="w-3.5 h-3.5" />
                                                    {{ $recFiltruAdresa === $a->id ? 'Sterge filtru' : 'Filtreaza' }}
                                                </button>
                                                <button wire:click="deschideRecipientiAdmin({{ $a->id }})"
                                                        class="inline-flex items-center gap-1 text-xs text-amber-700 hover:text-amber-900"
                                                        title="Inregistreaza miscare manuala">
                                                    <x-heroicon-m-plus class="w-3.5 h-3.5" />
                                                    Corectie manuala
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Jurnal miscari --}}
                                <div class="mt-4">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                                        <x-heroicon-o-list-bullet class="w-4 h-4" />
                                        Jurnal miscari
                                        @if($recFiltruAdresa)
                                            <span class="text-xs font-normal text-gray-500">
                                                (filtrat: {{ $adrese->firstWhere('id', $recFiltruAdresa)?->denumire ?? '?' }})
                                            </span>
                                        @endif
                                    </h4>
                                    @if($jurnalRecipienti->isEmpty())
                                        <div class="text-center py-8 text-gray-500 dark:text-gray-400 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                                            <x-heroicon-o-archive-box class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                                            <p class="text-sm">Nicio miscare inregistrata{{ $recFiltruAdresa ? ' pentru aceasta adresa' : '' }}.</p>
                                        </div>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                                <thead class="bg-gray-50 dark:bg-gray-900 text-[11px] uppercase tracking-wide text-gray-500">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left">Data</th>
                                                        <th class="px-3 py-2 text-left">Adresa</th>
                                                        <th class="px-3 py-2 text-center">19L lasati / recuperati</th>
                                                        <th class="px-3 py-2 text-center">11L lasati / recuperati</th>
                                                        <th class="px-3 py-2 text-left">Sursa</th>
                                                        <th class="px-3 py-2 text-left">Operator</th>
                                                        <th class="px-3 py-2 text-left">Observatii</th>
                                                        <th class="px-3 py-2"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach($jurnalRecipienti as $m)
                                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                                            <td class="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300 tabular-nums">
                                                                {{ $m->data?->format('d.m.Y') }}
                                                            </td>
                                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                                {{ $m->adresa?->denumire ?? '—' }}
                                                            </td>
                                                            <td class="px-3 py-2 text-center tabular-nums">
                                                                <span class="text-green-700">+{{ $m->lasati }}</span>
                                                                <span class="text-gray-400"> / </span>
                                                                <span class="text-rose-700">−{{ $m->recuperati }}</span>
                                                            </td>
                                                            <td class="px-3 py-2 text-center tabular-nums">
                                                                <span class="text-green-700">+{{ $m->lasati_11l }}</span>
                                                                <span class="text-gray-400"> / </span>
                                                                <span class="text-rose-700">−{{ $m->recuperati_11l }}</span>
                                                            </td>
                                                            <td class="px-3 py-2 text-xs">
                                                                @if($m->id_comanda)
                                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">
                                                                        <x-heroicon-m-clipboard-document-list class="w-3 h-3" />
                                                                        Comanda #{{ $m->id_comanda }}
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">
                                                                        <x-heroicon-m-pencil-square class="w-3 h-3" />
                                                                        Manual
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-xs text-gray-600">
                                                                {{ $m->utilizator?->name ?? '—' }}
                                                            </td>
                                                            <td class="px-3 py-2 text-xs text-gray-500 max-w-xs truncate" title="{{ $m->observatii }}">
                                                                {{ $m->observatii ?: '—' }}
                                                            </td>
                                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                                @if($m->id_comanda === null)
                                                                    <button wire:click="stergeMiscareAdmin({{ $m->id }})"
                                                                            wire:confirm="Stergi aceasta miscare manuala? Soldul se recalculeaza automat."
                                                                            class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800">
                                                                        <x-heroicon-m-trash class="w-3.5 h-3.5" />
                                                                        Sterge
                                                                    </button>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @if($jurnalRecipienti->count() === 100)
                                            <p class="text-xs text-gray-500 mt-2 italic">Afisate ultimele 100 miscari. Foloseste filtrul pe adresa pentru detalii.</p>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Tab Contract (Faza 6.2) --}}
                    @if($tab === 'contract')
                        <div class="space-y-4">
                            @if($contractMesaj)
                                <div class="px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                                    {{ $contractMesaj }}
                                </div>
                            @endif
                            @if($contractEroare)
                                <div class="px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm flex items-center gap-2">
                                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-500 flex-shrink-0" />
                                    {{ $contractEroare }}
                                </div>
                            @endif
                            @error('contractHtml')
                                <div class="px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm">{{ $message }}</div>
                            @enderror

                            <div class="bg-indigo-50 border border-indigo-200 rounded-md p-4 text-sm flex items-start gap-3">
                                <x-heroicon-o-information-circle class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" />
                                <div class="text-gray-700">
                                    <p>Acesta e contractul individual al clientului <strong>{{ $client->denumire }}</strong>. Continutul a fost generat din template-ul global cu placeholderele substituite si poate fi editat ad-hoc fara a afecta template-ul. Pentru a re-aplica template-ul curent (suprascriind editarile), foloseste „Regenereaza din template".</p>
                                    <p class="mt-1 text-xs text-gray-600">Template-ul global se editeaza la <a href="{{ route('setari.contract-template') }}" class="text-indigo-600 underline">/setari/contract-template</a>.</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <x-heroicon-o-pencil-square class="w-5 h-5 text-indigo-600" />
                                    Editor contract
                                </h3>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <button type="button" wire:click="regenereazaContract"
                                            wire:confirm="Sigur vrei sa regenerezi contractul din template-ul global? Toate editarile manuale facute pe acest contract vor fi pierdute."
                                            class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded border border-amber-300 text-amber-700 hover:bg-amber-50">
                                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                                        Regenereaza din template
                                    </button>
                                    <a href="{{ route('clienti.contract-pdf', $client) }}" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded border border-emerald-300 text-emerald-700 hover:bg-emerald-50">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                        Descarca PDF
                                    </a>
                                    <button type="button" wire:click="salveazaContract"
                                            class="inline-flex items-center gap-1.5 text-sm px-4 py-1.5 rounded bg-indigo-600 text-white hover:bg-indigo-700 font-medium">
                                        <x-heroicon-o-check class="w-4 h-4" />
                                        Salveaza
                                    </button>
                                </div>
                            </div>

                            <div wire:ignore
                                 wire:key="contract-editor-{{ $client->id }}-{{ $contractId ?? 'nou' }}"
                                 x-data="contractClientEditor({
                                     initial: @js($contractHtml),
                                     setHtml: (v) => $wire.set('contractHtml', v, false),
                                 })"
                                 x-init="init()"
                                 x-on:livewire:navigating.window="destroy()"
                                 class="border border-gray-200 rounded">
                                <textarea x-ref="editor" id="contract-client-editor-{{ $client->id }}"></textarea>
                            </div>
                        </div>
                    @endif

                    {{-- Faza 6.7 — Tab Documente (Livewire embed) --}}
                    @if($tab === 'documente')
                        <livewire:clienti.documente :client="$client" :key="'documente-' . $client->id" />
                    @endif

                    {{-- Tab-uri placeholder (cele neimplementate inca) --}}
                    @php
                        $placeholders = [
                            'comenzi' => ['Comenzi', 'clipboard-document-list', 'Faza 2', 'Va putea fi vazut dupa implementarea modulului de comenzi.'],
                        ];
                    @endphp
                    @if(array_key_exists($tab, $placeholders))
                        @php $p = $placeholders[$tab]; @endphp
                        <div class="text-center py-16 text-gray-500 dark:text-gray-400">
                            <x-dynamic-component :component="'heroicon-o-' . $p[1]" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                            <p class="font-medium text-gray-700 dark:text-gray-300">{{ $p[0] }} — disponibil dupa {{ $p[2] }}</p>
                            <p class="text-sm mt-1">{{ $p[3] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal reziliere --}}
    <div x-data="{ deschis: @entangle('modalReziliere') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalReziliere()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideModalReziliere()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-lg sm:mx-auto">
            <form wire:submit.prevent="confirmaReziliere" class="p-6">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-red-100 text-red-600 rounded-full">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Confirmi rezilierea?
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Clientul <strong>{{ $client->denumire }}</strong> va fi marcat ca reziliat.
                            Datele raman in baza si pot fi reactivate ulterior.
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Motiv reziliere <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea wire:model="motivReziliere" rows="3"
                              placeholder="ex: Client retras, contract incheiat, etc."
                              class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                </div>

                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" wire:click="inchideModalReziliere"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Anuleaza
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-check class="w-4 h-4" />
                        Confirma rezilierea
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal adresa --}}
    <div x-data="{ deschis: @entangle('modalAdresa') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalAdresa()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideModalAdresa()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-2xl sm:mx-auto">
            <form wire:submit.prevent="salveazaAdresa" class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <x-heroicon-o-map-pin class="w-5 h-5 text-indigo-600" />
                    {{ $adresaId ? 'Editare adresa' : 'Adauga adresa' }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Denumire <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="adresaDenumire" maxlength="255"
                               placeholder="ex: Sediu, Magazin Cluj, Punct de lucru"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        @error('adresaDenumire') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Oras</label>
                            <input type="text" wire:model="adresaOras" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Strada</label>
                            <input type="text" wire:model="adresaStrada" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Nr.</label>
                            <input type="text" wire:model="adresaNr" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Bloc</label>
                            <input type="text" wire:model="adresaBloc" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Scara</label>
                            <input type="text" wire:model="adresaScara" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Etaj</label>
                            <input type="text" wire:model="adresaEtaj" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Apt.</label>
                            <input type="text" wire:model="adresaApartament" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Sector</label>
                            <input type="text" wire:model="adresaSector" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Interfon</label>
                            <input type="text" wire:model="adresaInterfon" class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                            <x-heroicon-m-map-pin class="w-4 h-4" />
                            Coordonate GPS
                        </label>
                        <input type="text" wire:model="adresaGps"
                               placeholder="ex: 44.44649385233505, 26.08902786970661"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono" />
                        @error('adresaGps') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 mt-1">
                            Copy/paste direct din Google Maps (click dreapta pe locatie → click pe coordonate). Format: <span class="font-mono">lat, lng</span>. Pot fi adaugate ulterior.
                        </p>
                    </div>

                    <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="adresaActiv"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="ml-2">Adresa activa (livrabila)</span>
                    </label>
                </div>

                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" wire:click="inchideModalAdresa"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Anuleaza
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-check class="w-4 h-4" />
                        Salveaza
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal configurare livrare (produs) --}}
    <div x-data="{ deschis: @entangle('modalAbonament') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalAbonament()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideModalAbonament()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-2xl sm:mx-auto">
            <form wire:submit.prevent="salveazaAbonament" class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-indigo-600" />
                    Configurare livrare
                </h3>

                {{-- Selector tip (dropdown unic) --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tip configurare</label>
                    <select wire:model.live="abTip"
                            class="block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                        <option value="{{ Produs::TIP_ABONAMENT }}">Abonament lunar — livrare lunara, cantitate fixa inclusa</option>
                        <option value="{{ Produs::TIP_PER_BUCATA }}">Per bucata — livrari la cerere, fara abonament</option>
                        <option value="{{ Produs::TIP_FILTRE }}">Filtre — dozator cu filtre (Faza 4)</option>
                        <option value="{{ Produs::TIP_APARATE }}">Aparate — dozator in custodie (Faza 4)</option>
                    </select>
                    @error('abTip') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                {{-- Campuri specifice tipului 1 (abonament lunar) --}}
                @if((int)$abTip === Produs::TIP_ABONAMENT)
                    <div class="space-y-4 p-4 bg-indigo-50 dark:bg-indigo-900/10 rounded-md border border-indigo-100">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Denumire abonament</label>
                            <input type="text" maxlength="255" wire:model="abDenumireAbonament"
                                   placeholder="ex: Pachet Standard 5x19L"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('abDenumireAbonament') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Pret abonament (lei / luna)</label>
                            <input type="number" step="0.01" min="0" wire:model="abPret"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            <p class="text-[11px] text-gray-500 mt-1">Pret fix lunar pentru cantitatea inclusa mai jos.</p>
                            @error('abPret') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Cantitate bidoane inclusa in abonament</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div wire:key="ab-nr-19l">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="ab-nr-19l">Bidoane 19L</label>
                                    <input type="number" min="0"
                                           id="ab-nr-19l" name="ab-nr-19l"
                                           autocomplete="off"
                                           wire:model="abNrBidoane"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                    @error('abNrBidoane') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div wire:key="ab-nr-11l">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="ab-nr-11l">Bidoane 11L</label>
                                    <input type="number" min="0"
                                           id="ab-nr-11l" name="ab-nr-11l"
                                           autocomplete="off"
                                           wire:model="abNrBidoane11l"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                    @error('abNrBidoane11l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-1">Lasa 0 daca abonamentul nu include acea capacitate. Cel putin una trebuie completata.</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Pret consum suplimentar (peste cantitatea inclusa)</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div wire:key="ab-supl-19l">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="ab-pret-supl-19l">Pret / bidon 19L (lei)</label>
                                    <input type="number" step="0.01" min="0"
                                           id="ab-pret-supl-19l" name="ab-pret-supl-19l"
                                           autocomplete="off"
                                           wire:model="abPretSuplimentar19l"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                    @error('abPretSuplimentar19l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                                <div wire:key="ab-supl-11l">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="ab-pret-supl-11l">Pret / bidon 11L (lei)</label>
                                    <input type="number" step="0.01" min="0"
                                           id="ab-pret-supl-11l" name="ab-pret-supl-11l"
                                           autocomplete="off"
                                           wire:model="abPretSuplimentar11l"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                    @error('abPretSuplimentar11l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                                <x-heroicon-m-calendar-days class="w-3.5 h-3.5" />
                                Data primei livrari
                            </label>
                            <input type="date" wire:model="abZiLivrare"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('abZiLivrare') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            @if($abZiLivrare)
                                @php
                                    try {
                                        $luna = \Carbon\Carbon::parse($abZiLivrare)->locale('ro')->isoFormat('MMMM YYYY');
                                    } catch (\Throwable $e) {
                                        $luna = null;
                                    }
                                @endphp
                                @if($luna)
                                    <p class="text-[11px] text-gray-600 dark:text-gray-400 mt-1 bg-white dark:bg-gray-800 p-2 rounded">
                                        Aceasta data reprezinta prima luna de abonament:
                                        <span class="font-medium">{{ ucfirst($luna) }}</span>.
                                        Urmatoarea luna de abonament va fi calculata automat.
                                    </p>
                                @endif
                            @endif
                        </div>
                    </div>
                @elseif((int)$abTip === Produs::TIP_PER_BUCATA)
                    <div class="grid grid-cols-2 gap-3 p-4 bg-amber-50 dark:bg-amber-900/10 rounded-md border border-amber-100">
                        <div wire:key="pb-pret-19l">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="pb-pret-19l">Pret 19L (lei)</label>
                            <input type="number" step="0.01" min="0"
                                   id="pb-pret-19l" name="pb-pret-19l"
                                   autocomplete="off"
                                   wire:model="abPret"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('abPret') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div wire:key="pb-pret-11l">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300" for="pb-pret-11l">Pret 11L (lei)</label>
                            <input type="number" step="0.01" min="0"
                                   id="pb-pret-11l" name="pb-pret-11l"
                                   autocomplete="off"
                                   wire:model="abPret11l"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('abPret11l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @elseif((int)$abTip === Produs::TIP_FILTRE)
                    <div class="p-3 bg-cyan-50 dark:bg-cyan-900/10 rounded-md border border-cyan-100 text-sm text-cyan-800 dark:text-cyan-200 flex items-start gap-2">
                        <x-heroicon-o-information-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        Datele specifice dozatorului cu filtre (serie, data instalarii, schimburi filtre) se vor configura in modulul "Dozatoare cu filtre" (Faza 4).
                    </div>
                @elseif((int)$abTip === Produs::TIP_APARATE)
                    <div class="p-3 bg-purple-50 dark:bg-purple-900/10 rounded-md border border-purple-100 text-sm text-purple-800 dark:text-purple-200 flex items-start gap-2">
                        <x-heroicon-o-information-circle class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        Aparatul (dozator in custodie) si igienizarile se vor configura in modulul "Dozatoare cu bidoane" (Faza 4).
                    </div>
                @endif

                {{-- Default-uri operationale (mereu vizibile) --}}
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                            <x-heroicon-m-truck class="w-3.5 h-3.5" />
                            Masina default
                        </label>
                        <select wire:model="abIdMasina"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="">— niciuna —</option>
                            @foreach($masiniDisponibile as $m)
                                <option value="{{ $m->id }}">{{ $m->denumire }} ({{ $m->nr_inmatriculare }})</option>
                            @endforeach
                        </select>
                        @error('abIdMasina') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                            <x-heroicon-m-building-storefront class="w-3.5 h-3.5" />
                            Depozit default
                        </label>
                        <select wire:model="abIdDepozit"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="">— niciunul —</option>
                            @foreach($depoziteDisponibile as $d)
                                <option value="{{ $d->id }}">{{ $d->denumire }}</option>
                            @endforeach
                        </select>
                        @error('abIdDepozit') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Observatii specifice acestei adrese</label>
                    <textarea wire:model="abObservatii" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                    @error('abObservatii') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" wire:click="inchideModalAbonament"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Anuleaza
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-check class="w-4 h-4" />
                        Salveaza configurare
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal corectie manuala recipienti (admin) --}}
    <div x-data="{ deschis: @entangle('modalRecipientiAdmin') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideRecipientiAdmin()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideRecipientiAdmin()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-md sm:mx-auto p-5">
            <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">
                <x-heroicon-o-archive-box class="w-5 h-5 text-amber-600" />
                Corectie manuala recipienti
            </h3>
            <p class="text-xs text-gray-500 mb-3">
                Adresa: <strong>{{ $recAdresaDenumire }}</strong>. Folosit pentru a corecta soldul fara o comanda asociata (ex: ridicare bidoane reformate).
            </p>

            <form wire:submit.prevent="salveazaRecipientiAdmin">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">19L lasati</label>
                        <input type="number" min="0" wire:model="recAdminLasati19l"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm tabular-nums" />
                        @error('recAdminLasati19l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">19L recuperati</label>
                        <input type="number" min="0" wire:model="recAdminRecuperati19l"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm tabular-nums" />
                        @error('recAdminRecuperati19l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">11L lasati</label>
                        <input type="number" min="0" wire:model="recAdminLasati11l"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm tabular-nums" />
                        @error('recAdminLasati11l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">11L recuperati</label>
                        <input type="number" min="0" wire:model="recAdminRecuperati11l"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm tabular-nums" />
                        @error('recAdminRecuperati11l') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Data miscarii</label>
                    <input type="date" wire:model="recAdminData"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                    @error('recAdminData') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Motiv corectie <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="recAdminObservatii" rows="2" required
                              placeholder="ex: Inventar real diferit de DB; ridicare bidoane reformate; corectie eroare livrare anterioara"
                              class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                    @error('recAdminObservatii') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="inchideRecipientiAdmin"
                            class="inline-flex items-center gap-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Anuleaza
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-check class="w-4 h-4" />
                        Inregistreaza miscare
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Faza 6.2 — Wrapper Alpine pentru editorul TinyMCE pe tab-ul Contract.
         Definit ca window.contractClientEditor pentru a fi disponibil indiferent
         de momentul evaluarii @script (Livewire 3 ruleaza scriptul o data
         per componenta — pe init si la wire:navigate, dar nu la fiecare re-render).  --}}
    @script
    <script>
        window.contractClientEditor = function ({ initial, setHtml }) {
            return {
                editor: null,
                async init() {
                    window.destroyContractEditor(this.$refs.editor);
                    const eds = await window.initContractEditor(this.$refs.editor, {
                        initialContent: initial,
                        onChange: (html) => setHtml(html),
                    });
                    this.editor = eds && eds[0];
                },
                destroy() {
                    if (this.editor) this.editor.remove();
                    this.editor = null;
                },
            };
        };
    </script>
    @endscript
</div>
