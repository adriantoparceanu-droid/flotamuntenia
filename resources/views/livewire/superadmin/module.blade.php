<div>
    {{-- Header pagina --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-1">
            <x-heroicon-o-puzzle-piece class="w-7 h-7 text-fuchsia-500" />
            <h1 class="text-2xl font-bold text-gray-900">Gestionare Module</h1>
        </div>
        <p class="text-gray-500 text-sm ml-10">
            Activează sau dezactivează modulele opționale ale platformei.
            Dezactivarea nu șterge date — este complet reversibilă.
        </p>
    </div>

    {{-- Flash mesaje --}}
    @if(session('mesaj'))
        <div class="mb-4 flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            <x-heroicon-m-check-circle class="w-5 h-5 flex-shrink-0" />
            {{ session('mesaj') }}
        </div>
    @endif
    @if(session('eroare'))
        <div class="mb-4 flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            <x-heroicon-m-x-circle class="w-5 h-5 flex-shrink-0" />
            {{ session('eroare') }}
        </div>
    @endif

    {{-- Banner info --}}
    <div class="mb-6 flex items-start gap-3 px-4 py-3 bg-fuchsia-50 border border-fuchsia-200 rounded-lg">
        <x-heroicon-o-information-circle class="w-5 h-5 text-fuchsia-500 flex-shrink-0 mt-0.5" />
        <p class="text-sm text-fuchsia-700">
            <strong>4 module core</strong> (Autentificare, Clienți, Comenzi, Dashboard) sunt
            mereu active și nu apar în această listă.
            Modulele de mai jos sunt <strong>opționale</strong> și pot fi activate sau dezactivate
            fără a afecta integritatea datelor.
        </p>
    </div>

    {{-- Grid module --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($module as $def)
            @php
                $activ = $def['activ'];
                $culoriCard = $activ
                    ? 'bg-white border-gray-200 shadow-sm'
                    : 'bg-gray-50 border-gray-200 opacity-75';
            @endphp

            <div class="rounded-xl border {{ $culoriCard }} p-5 flex flex-col gap-4 transition">

                {{-- Header card --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                            {{ $activ ? 'bg-' . $def['culoare'] . '-100' : 'bg-gray-100' }}">
                            <x-dynamic-component
                                :component="'heroicon-o-' . $def['icon']"
                                class="w-5 h-5 {{ $activ ? 'text-' . $def['culoare'] . '-600' : 'text-gray-400' }}"
                            />
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-gray-900 text-sm leading-tight truncate">
                                {{ $def['nume'] }}
                            </h3>
                            <span class="inline-flex items-center gap-1 mt-0.5 text-xs font-medium
                                {{ $activ ? 'text-green-600' : 'text-gray-400' }}">
                                @if($activ)
                                    <x-heroicon-m-check-circle class="w-3.5 h-3.5" /> Activ
                                @else
                                    <x-heroicon-m-x-circle class="w-3.5 h-3.5" /> Inactiv
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Descriere --}}
                <p class="text-xs text-gray-500 leading-relaxed flex-1">
                    {{ $def['descriere'] }}
                </p>

                {{-- Ce blochează --}}
                <div class="text-xs text-gray-400 border-t border-gray-100 pt-3">
                    <span class="font-medium text-gray-500">Dezactivat blochează:</span>
                    {{ $def['blocheaza'] }}
                </div>

                {{-- Avertizare dependență --}}
                @if($def['avertizare'])
                    <div class="flex items-start gap-1.5 text-xs text-amber-600 bg-amber-50 rounded-md px-2.5 py-2">
                        <x-heroicon-m-exclamation-triangle class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" />
                        {{ $def['avertizare'] }}
                    </div>
                @endif

                {{-- Buton toggle --}}
                @if($activ)
                    <button
                        wire:click="toggle('{{ $def['cheie'] }}')"
                        wire:confirm="Dezactivezi modulul „{{ $def['nume'] }}".\n\nMenul și rutele aferente vor returna pagina „Modul indisponibil".\nDatele rămân intacte și modulul poate fi reactivat oricând.\n\nContinui?"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium
                               text-gray-600 bg-white border border-gray-300 rounded-lg
                               hover:bg-red-50 hover:border-red-300 hover:text-red-600 transition">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Dezactivează
                    </button>
                @else
                    <button
                        wire:click="toggle('{{ $def['cheie'] }}')"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium
                               text-white bg-indigo-600 rounded-lg
                               hover:bg-indigo-700 transition">
                        <x-heroicon-m-check class="w-4 h-4" />
                        Activează
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>
