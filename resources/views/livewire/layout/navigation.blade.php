<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<aside x-data="{ open: false }"
       class="lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-gray-900 text-gray-200">

    {{-- Buton hamburger mobil --}}
    <div class="lg:hidden flex items-center justify-between px-4 py-3 bg-gray-900 text-gray-200">
        <a href="{{ route(auth()->user()->homeRoute()) }}" wire:navigate class="flex items-center gap-2 font-semibold">
            <x-heroicon-o-truck class="w-6 h-6 text-indigo-400" />
            FlotaMuntenia
        </a>
        <button @click="open = !open" class="p-2 rounded hover:bg-gray-800">
            <x-heroicon-o-bars-3 x-show="!open" class="h-6 w-6" />
            <x-heroicon-o-x-mark x-show="open" class="h-6 w-6" />
        </button>
    </div>

    {{-- Continut sidebar --}}
    <div :class="open ? 'block' : 'hidden'" class="lg:block lg:flex-1 lg:flex lg:flex-col">
        {{-- Header sidebar (logo + nume aplicatie) --}}
        <div class="hidden lg:flex items-center h-16 px-6 bg-gray-950 border-b border-gray-800">
            <a href="{{ route(auth()->user()->homeRoute()) }}" wire:navigate class="flex items-center gap-2 text-lg font-semibold text-white">
                <x-heroicon-o-truck class="w-6 h-6 text-indigo-400" />
                FlotaMuntenia
            </a>
        </div>

        {{-- Navigatie pe rol.
             Pentru admin/superadmin: secțiunile Operational/Dozatoare/Financiar/Setari/Platforma sunt collapsibile.
             State persistat in localStorage per user (cheie: sidebar-expanded-{userId}).
             La fresh login (no key), default: doar Operational expandat. --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto"
             @if(auth()->user()->isAdmin())
                x-data="sidebarSectiuni({
                    userId: {{ auth()->id() }},
                    implicit: { operational: true, dozatoare: false, financiar: false, setari: false, platforma: false }
                })"
                x-init="init()"
             @endif>

            @if(auth()->user()->isAdmin())
                <x-sidebar-link icon="home" :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Panou principal
                </x-sidebar-link>

                {{-- ===== OPERATIONAL ===== --}}
                @php $nrAprobariPending = \App\Models\Comanda::where('status', \App\Models\Comanda::STATUS_IN_ASTEPTARE)->count(); @endphp
                <button type="button" @click="toggle('operational')"
                        class="mt-4 w-full flex items-center justify-between px-3 py-1.5 text-xs uppercase tracking-wider text-gray-500 hover:text-gray-300 transition">
                    <span class="flex items-center gap-1.5">
                        Operational
                        @if($nrAprobariPending > 0)
                            <span class="inline-flex items-center justify-center px-1.5 min-w-[1.25rem] h-4 text-[10px] rounded-full bg-amber-500 text-white font-semibold">{{ $nrAprobariPending }}</span>
                        @endif
                    </span>
                    <x-heroicon-m-chevron-down class="w-4 h-4 transition-transform"
                        x-bind:class="expanded.operational ? '' : '-rotate-90'" />
                </button>
                <div x-show="expanded.operational" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="space-y-1">
                    <x-sidebar-link icon="users" :href="route('clienti.index')" :active="request()->routeIs('clienti.*')">Clienti</x-sidebar-link>
                    <x-sidebar-link icon="clipboard-document-list" :href="route('comenzi.index')" :active="request()->routeIs('comenzi.*') && ! request()->routeIs('comenzi.aprobare')">Comenzi</x-sidebar-link>
                    @moduleActiv('portal_client')
                        <x-sidebar-link icon="check-badge" :href="route('comenzi.aprobare')" :active="request()->routeIs('comenzi.aprobare')">
                            <span>Aprobare comenzi</span>
                            @if($nrAprobariPending > 0)
                                <span class="inline-flex items-center justify-center px-1.5 min-w-[1.25rem] h-5 text-[11px] rounded-full bg-amber-500 text-white font-semibold">{{ $nrAprobariPending }}</span>
                            @endif
                        </x-sidebar-link>
                    @endmoduleActiv
                    <x-sidebar-link icon="calendar-days" :href="route('lista-zilnica')" :active="request()->routeIs('lista-zilnica')">Lista zilnica</x-sidebar-link>
                    @moduleActiv('comenzi_rapide')
                        <x-sidebar-link icon="bolt" :href="route('comenzi-rapide.index')" :active="request()->routeIs('comenzi-rapide.*')">Comenzi rapide</x-sidebar-link>
                    @endmoduleActiv
                    @moduleActiv('probleme')
                        <x-sidebar-link icon="exclamation-triangle" :href="route('probleme.index')" :active="request()->routeIs('probleme.*')">Probleme</x-sidebar-link>
                    @endmoduleActiv
                </div>

                {{-- ===== DOZATOARE ===== --}}
                @php
                    $nrDozatoareScadente = \App\Models\Dozator::where('activ', true)
                        ->whereNotNull('perioada_igenizare')
                        ->where('perioada_igenizare', '<=', now()->addDays(30)->toDateString())
                        ->count();
                    $nrFiltreScadente = \App\Models\DozatorFiltre::where('status', \App\Models\DozatorFiltre::STATUS_ACTIV)
                        ->whereNotNull('data_urmatoare_mentenanta')
                        ->where('data_urmatoare_mentenanta', '<=', now()->addDays(30)->toDateString())
                        ->count();
                    $nrTotalScadente = $nrDozatoareScadente + $nrFiltreScadente;
                    $areItemeDozatoare = \App\Services\ModuleService::isActive(\App\Models\SetariPlatforma::MODUL_DOZATOARE)
                        || \App\Services\ModuleService::isActive(\App\Models\SetariPlatforma::MODUL_RECIPIENTI);
                @endphp
                @if($areItemeDozatoare)
                    <button type="button" @click="toggle('dozatoare')"
                            class="mt-4 w-full flex items-center justify-between px-3 py-1.5 text-xs uppercase tracking-wider text-gray-500 hover:text-gray-300 transition">
                        <span class="flex items-center gap-1.5">
                            Dozatoare
                            @if($nrTotalScadente > 0)
                                <span class="inline-flex items-center justify-center px-1.5 min-w-[1.25rem] h-4 text-[10px] rounded-full bg-amber-500 text-white font-semibold"
                                      title="Bidoane scadente: {{ $nrDozatoareScadente }} / Filtre scadente: {{ $nrFiltreScadente }}">{{ $nrTotalScadente }}</span>
                            @endif
                        </span>
                        <x-heroicon-m-chevron-down class="w-4 h-4 transition-transform"
                            x-bind:class="expanded.dozatoare ? '' : '-rotate-90'" />
                    </button>
                    <div x-show="expanded.dozatoare" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="space-y-1">
                        @moduleActiv('dozatoare')
                            <x-sidebar-link icon="wrench-screwdriver" :href="route('dozatoare.index')" :active="request()->routeIs('dozatoare.*')">
                                <span>Mentenanta dozatoare</span>
                                @if($nrTotalScadente > 0)
                                    <span class="inline-flex items-center justify-center px-1.5 min-w-[1.25rem] h-5 text-[11px] rounded-full bg-amber-500 text-white font-semibold"
                                          title="Bidoane scadente: {{ $nrDozatoareScadente }} / Filtre scadente: {{ $nrFiltreScadente }}">{{ $nrTotalScadente }}</span>
                                @endif
                            </x-sidebar-link>
                        @endmoduleActiv
                        {{-- Recipienti — modul neimplementat inca, ascuns pana la Faza 4 --}}
                    </div>
                @endif

                {{-- ===== FINANCIAR ===== --}}
                @php
                    $areItemeFinanciar = \App\Services\ModuleService::isActive(\App\Models\SetariPlatforma::MODUL_STOC)
                        || \App\Services\ModuleService::isActive(\App\Models\SetariPlatforma::MODUL_RAPOARTE);
                @endphp
                @if($areItemeFinanciar)
                    <button type="button" @click="toggle('financiar')"
                            class="mt-4 w-full flex items-center justify-between px-3 py-1.5 text-xs uppercase tracking-wider text-gray-500 hover:text-gray-300 transition">
                        <span>Financiar</span>
                        <x-heroicon-m-chevron-down class="w-4 h-4 transition-transform"
                            x-bind:class="expanded.financiar ? '' : '-rotate-90'" />
                    </button>
                    <div x-show="expanded.financiar" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="space-y-1">
                        @moduleActiv('stoc')
                            <x-sidebar-link icon="banknotes" :href="route('cheltuieli.index')" :active="request()->routeIs('cheltuieli.*')">Cheltuieli</x-sidebar-link>
                        @endmoduleActiv
                        @moduleActiv('rapoarte')
                            <x-sidebar-link icon="chart-pie" :href="route('rapoarte.stoc')" :active="request()->routeIs('rapoarte.*')">Rapoarte</x-sidebar-link>
                        @endmoduleActiv
                    </div>
                @endif

                {{-- ===== SETARI ===== --}}
                <button type="button" @click="toggle('setari')"
                        class="mt-4 w-full flex items-center justify-between px-3 py-1.5 text-xs uppercase tracking-wider text-gray-500 hover:text-gray-300 transition">
                    <span>Setari</span>
                    <x-heroicon-m-chevron-down class="w-4 h-4 transition-transform"
                        x-bind:class="expanded.setari ? '' : '-rotate-90'" />
                </button>
                <div x-show="expanded.setari" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="space-y-1">
                    <x-sidebar-link icon="truck" :href="route('setari.masini')" :active="request()->routeIs('setari.masini')">Masini</x-sidebar-link>
                    <x-sidebar-link icon="building-storefront" :href="route('setari.depozite')" :active="request()->routeIs('setari.depozite')">Depozite</x-sidebar-link>
                    <x-sidebar-link icon="squares-2x2" :href="route('setari.catalog')" :active="request()->routeIs('setari.catalog')">Catalog produse</x-sidebar-link>
                    <x-sidebar-link icon="receipt-percent" :href="route('setari.tva')" :active="request()->routeIs('setari.tva')">Cote TVA</x-sidebar-link>
                    <x-sidebar-link icon="user-group" :href="route('setari.utilizatori')" :active="request()->routeIs('setari.utilizatori')">Utilizatori</x-sidebar-link>
                    @moduleActiv('facturare')
                        <x-sidebar-link icon="document-text" :href="route('setari.facturare')" :active="request()->routeIs('setari.facturare')">Facturare electronica</x-sidebar-link>
                    @endmoduleActiv
                    @moduleActiv('contracte')
                        <x-sidebar-link icon="document-duplicate" :href="route('setari.contract-template')" :active="request()->routeIs('setari.contract-template')">Sablon contract</x-sidebar-link>
                    @endmoduleActiv
                    @moduleActiv('email')
                        <x-sidebar-link icon="envelope" :href="route('setari.template-email')" :active="request()->routeIs('setari.template-email')">Sabloane email</x-sidebar-link>
                        <x-sidebar-link icon="paper-airplane" :href="route('setari.smtp')" :active="request()->routeIs('setari.smtp')">Setari SMTP</x-sidebar-link>
                    @endmoduleActiv
                    @moduleActiv('cron')
                        <x-sidebar-link icon="clock" :href="route('setari.cron')" :active="request()->routeIs('setari.cron')">Cron jobs</x-sidebar-link>
                    @endmoduleActiv
                </div>

                {{-- ===== PLATFORMA (doar SuperAdmin) ===== --}}
                @if(auth()->user()->isSuperadmin())
                    <button type="button" @click="toggle('platforma')"
                            class="mt-4 w-full flex items-center justify-between px-3 py-1.5 text-xs uppercase tracking-wider text-fuchsia-400 hover:text-fuchsia-300 transition">
                        <span class="flex items-center gap-1.5">
                            <x-heroicon-m-cog-6-tooth class="w-3.5 h-3.5" />
                            Platforma
                        </span>
                        <x-heroicon-m-chevron-down class="w-4 h-4 transition-transform"
                            x-bind:class="expanded.platforma ? '' : '-rotate-90'" />
                    </button>
                    <div x-show="expanded.platforma" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="space-y-1">
                        <x-sidebar-link icon="puzzle-piece" :href="route('superadmin.module')" :active="request()->routeIs('superadmin.module')">
                            Gestionare module
                        </x-sidebar-link>
                    </div>
                @endif
            @endif

            @if(auth()->user()->isSofer())
                <x-sidebar-link icon="map" :href="route('sofer.traseu')" :active="request()->routeIs('sofer.*')">Traseul meu</x-sidebar-link>
                {{-- Recipienti — modul neimplementat inca, ascuns pana la Faza 4 --}}
            @endif

            {{-- Clientii (tip=3) folosesc layout dedicat portal.blade.php cu navigation propriu --}}

            @if(auth()->user()->isGestiune())
                <x-sidebar-link icon="clipboard-document-list" :href="route('gestiune.comenzi')" :active="request()->routeIs('gestiune.comenzi')">Comenzi</x-sidebar-link>
                <x-sidebar-link icon="document-text" href="#" :active="false">Foaie de parcurs</x-sidebar-link>
            @endif
        </nav>

        {{-- Footer sidebar: utilizator + logout --}}
        <div class="border-t border-gray-800 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 min-w-0">
                    <x-heroicon-o-user-circle class="w-8 h-8 text-gray-500 flex-shrink-0" />
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-gray-400 truncate">
                            @switch(auth()->user()->tip)
                                @case(\App\Models\User::TIP_ADMIN) Administrator @break
                                @case(\App\Models\User::TIP_SUPERADMIN) Superadmin @break
                                @case(\App\Models\User::TIP_CLIENT) Client @break
                                @case(\App\Models\User::TIP_SOFER) Sofer @break
                                @case(\App\Models\User::TIP_GESTIUNE) Gestiune @break
                            @endswitch
                        </div>
                    </div>
                </div>
                <button wire:click="logout"
                        title="Iesire"
                        class="ml-3 p-2 rounded text-gray-400 hover:bg-gray-800 hover:text-white transition">
                    <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5" />
                </button>
            </div>
        </div>
    </div>

    @script
    <script>
        // Wrapper Alpine pentru sidebar collapsible.
        // State persistat in localStorage per user (cheie: sidebar-expanded-{userId}).
        // La fresh login (no key), foloseste obiectul `implicit` ca default.
        // Dupa orice toggle, persistenta se face automat via $watch.
        window.sidebarSectiuni = function ({ userId, implicit }) {
            return {
                expanded: {},
                cheie: 'sidebar-expanded-' + userId,
                init() {
                    const saved = localStorage.getItem(this.cheie);
                    if (saved) {
                        try {
                            this.expanded = { ...implicit, ...JSON.parse(saved) };
                        } catch (e) {
                            this.expanded = { ...implicit };
                        }
                    } else {
                        this.expanded = { ...implicit };
                    }
                    this.$watch('expanded', (val) => {
                        localStorage.setItem(this.cheie, JSON.stringify(val));
                    });
                },
                toggle(sectiune) {
                    this.expanded[sectiune] = ! this.expanded[sectiune];
                },
            };
        };
    </script>
    @endscript
</aside>
