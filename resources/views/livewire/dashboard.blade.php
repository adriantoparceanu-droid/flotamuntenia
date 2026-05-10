<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <x-heroicon-o-home class="w-6 h-6 text-indigo-600" />
                Panou principal
            </h2>
            <div class="text-xs text-gray-500 flex items-center gap-1.5" wire:loading.class="text-indigo-600">
                <x-heroicon-m-arrow-path class="w-3.5 h-3.5" wire:loading.class="animate-spin" />
                <span>Actualizat la {{ now()->format('H:i:s') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">

            {{-- Salutare --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                <p class="text-base text-gray-900 dark:text-gray-100">Bine ai venit, <strong>{{ auth()->user()->name }}</strong>.</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Cifrele KPI se actualizeaza la 10s, tabelele la 30s. Graficele se reincarca cu butonul „Refresh grafice".
                </p>
            </div>

            {{-- Navigare rapida (mutata sus pentru acces imediat la actiunile principale) --}}
            <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 sm:rounded-lg p-3">
                <p class="text-[11px] font-medium text-gray-500 uppercase tracking-wider mb-2">Navigare rapida</p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('lista-zilnica') }}" wire:navigate class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-xs text-gray-700 dark:text-gray-300 hover:border-indigo-300 transition">
                        <x-heroicon-o-calendar-days class="w-3.5 h-3.5 text-indigo-500" />
                        Lista zilnica
                    </a>
                    <a href="{{ route('comenzi.index') }}" wire:navigate class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-xs text-gray-700 dark:text-gray-300 hover:border-indigo-300 transition">
                        <x-heroicon-o-clipboard-document-list class="w-3.5 h-3.5 text-indigo-500" />
                        Comenzi
                    </a>
                    <a href="{{ route('clienti.index') }}" wire:navigate class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-xs text-gray-700 dark:text-gray-300 hover:border-indigo-300 transition">
                        <x-heroicon-o-users class="w-3.5 h-3.5 text-indigo-500" />
                        Clienti
                    </a>
                    <a href="{{ route('dozatoare.index') }}" wire:navigate class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-xs text-gray-700 dark:text-gray-300 hover:border-indigo-300 transition">
                        <x-heroicon-o-wrench-screwdriver class="w-3.5 h-3.5 text-indigo-500" />
                        Dozatoare
                    </a>
                    <a href="{{ route('cheltuieli.index') }}" wire:navigate class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-xs text-gray-700 dark:text-gray-300 hover:border-indigo-300 transition">
                        <x-heroicon-o-banknotes class="w-3.5 h-3.5 text-indigo-500" />
                        Cheltuieli
                    </a>
                    <a href="{{ route('rapoarte.stoc') }}" wire:navigate class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-xs text-gray-700 dark:text-gray-300 hover:border-indigo-300 transition">
                        <x-heroicon-o-chart-pie class="w-3.5 h-3.5 text-indigo-500" />
                        Rapoarte
                    </a>
                </div>
            </div>

            {{-- ===== NECESITA ATENTIE ===== --}}
            @if(! empty($this->alerteAtentie))
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 sm:rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-200 mb-2">Necesita atentie</h3>
                            <ul class="space-y-1.5">
                                @foreach($this->alerteAtentie as $alerta)
                                    <li>
                                        <a href="{{ $alerta['href'] }}" wire:navigate
                                           class="inline-flex items-center gap-2 text-sm text-amber-900 dark:text-amber-200 hover:text-amber-700 underline-offset-2 hover:underline">
                                            <x-dynamic-component :component="'heroicon-o-' . $alerta['icon']" class="w-4 h-4 flex-shrink-0" />
                                            {{ $alerta['text'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ===== KPI CARDS (8) ===== --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                {{-- Comenzi azi --}}
                <a href="{{ route('lista-zilnica') }}" wire:navigate
                   class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-3 hover:shadow-md hover:border-indigo-300 border border-transparent transition flex items-center gap-2.5">
                    <div class="p-1.5 bg-indigo-100 text-indigo-600 rounded-md flex-shrink-0">
                        <x-heroicon-o-clipboard-document-list class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xl font-semibold text-gray-900 dark:text-gray-100 leading-tight">{{ $this->comenziAzi }}</div>
                        <div class="text-[11px] text-gray-500 truncate">Comenzi azi</div>
                    </div>
                </a>

                {{-- Nelivrate azi --}}
                <a href="{{ route('lista-zilnica') }}" wire:navigate
                   class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-3 hover:shadow-md hover:border-amber-300 border border-transparent transition flex items-center gap-2.5">
                    <div class="p-1.5 bg-amber-100 text-amber-600 rounded-md flex-shrink-0">
                        <x-heroicon-o-truck class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xl font-semibold text-gray-900 dark:text-gray-100 leading-tight">{{ $this->comenziNelivrateAzi }}</div>
                        <div class="text-[11px] text-gray-500 truncate">Nelivrate azi</div>
                    </div>
                </a>

                {{-- In asteptare --}}
                <a href="{{ route('comenzi.aprobare') }}" wire:navigate
                   class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-3 hover:shadow-md hover:border-orange-300 border border-transparent transition flex items-center gap-2.5 relative">
                    <div class="p-1.5 bg-orange-100 text-orange-600 rounded-md flex-shrink-0">
                        <x-heroicon-o-check-badge class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xl font-semibold text-gray-900 dark:text-gray-100 leading-tight">{{ $this->comenziInAsteptare }}</div>
                        <div class="text-[11px] text-gray-500 truncate">In asteptare</div>
                    </div>
                    @if($this->comenziInAsteptare > 0)
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-2.5 h-2.5 bg-orange-500 rounded-full animate-pulse"></span>
                    @endif
                </a>

                {{-- Mentenanta scadente --}}
                <a href="{{ route('dozatoare.index') }}" wire:navigate
                   class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-3 hover:shadow-md hover:border-rose-300 border border-transparent transition flex items-center gap-2.5">
                    <div class="p-1.5 bg-rose-100 text-rose-600 rounded-md flex-shrink-0">
                        <x-heroicon-o-wrench-screwdriver class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xl font-semibold text-gray-900 dark:text-gray-100 leading-tight">{{ $this->mentenantaScadente }}</div>
                        <div class="text-[11px] text-gray-500 truncate">Mentenanta scadente</div>
                    </div>
                </a>

                {{-- Bidoane 19L luna --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-3 flex items-center gap-2.5">
                    <div class="p-1.5 bg-blue-100 text-blue-600 rounded-md flex-shrink-0">
                        <x-heroicon-o-cube class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xl font-semibold text-gray-900 dark:text-gray-100 leading-tight">{{ $this->bidoane19lLuna }}</div>
                        <div class="text-[11px] text-gray-500 truncate">Bidoane 19L luna</div>
                    </div>
                </div>

                {{-- Bidoane 11L luna --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-3 flex items-center gap-2.5">
                    <div class="p-1.5 bg-cyan-100 text-cyan-600 rounded-md flex-shrink-0">
                        <x-heroicon-o-cube class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xl font-semibold text-gray-900 dark:text-gray-100 leading-tight">{{ $this->bidoane11lLuna }}</div>
                        <div class="text-[11px] text-gray-500 truncate">Bidoane 11L luna</div>
                    </div>
                </div>

                {{-- Cheltuieli luna --}}
                <a href="{{ route('cheltuieli.index') }}" wire:navigate
                   class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-3 hover:shadow-md hover:border-rose-300 border border-transparent transition flex items-center gap-2.5">
                    <div class="p-1.5 bg-rose-100 text-rose-600 rounded-md flex-shrink-0">
                        <x-heroicon-o-banknotes class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xl font-semibold text-gray-900 dark:text-gray-100 leading-tight">{{ number_format($this->totalCheltuieliLuna, 0, ',', '.') }} <span class="text-xs font-normal text-gray-400">lei</span></div>
                        <div class="text-[11px] text-gray-500 truncate">Cheltuieli luna</div>
                    </div>
                </a>

                {{-- Clienti activi --}}
                <a href="{{ route('clienti.index') }}" wire:navigate
                   class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-3 hover:shadow-md hover:border-purple-300 border border-transparent transition flex items-center gap-2.5">
                    <div class="p-1.5 bg-purple-100 text-purple-600 rounded-md flex-shrink-0">
                        <x-heroicon-o-users class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xl font-semibold text-gray-900 dark:text-gray-100 leading-tight">{{ $this->totalClientiActivi }}</div>
                        <div class="text-[11px] text-gray-500 truncate">Clienti activi</div>
                    </div>
                </a>
            </div>

            {{-- ===== GRAFICE (3) ===== --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2 uppercase tracking-wider">
                        <x-heroicon-o-chart-bar class="w-4 h-4 text-indigo-500" />
                        Grafice analitice
                    </h3>
                    <button type="button" wire:click="refreshCharts"
                            class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900">
                        <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                        Refresh grafice
                    </button>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    {{-- Line chart: trend comenzi 30 zile --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Trend comenzi 30 zile</h4>
                        <div wire:ignore
                             wire:key="line-chart-{{ $refreshChartsToken }}"
                             x-data="dashboardChartWrapper({ tip: 'line', data: @js($lineChartData) })"
                             x-init="init()"
                             x-on:livewire:navigating.window="destroy()"
                             class="relative h-56">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    </div>

                    {{-- Donut chart: distributie tipuri luna --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Tipuri comenzi — {{ $this->lunaCurentaText() }}</h4>
                        <div wire:ignore
                             wire:key="donut-chart-{{ $refreshChartsToken }}"
                             x-data="dashboardChartWrapper({ tip: 'donut', data: @js($donutChartData) })"
                             x-init="init()"
                             x-on:livewire:navigating.window="destroy()"
                             class="relative h-56">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    </div>

                    {{-- Bar chart: cheltuieli vs incasari 6 luni --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Cheltuieli vs Incasari — 6 luni</h4>
                        <div wire:ignore
                             wire:key="bar-chart-{{ $refreshChartsToken }}"
                             x-data="dashboardChartWrapper({ tip: 'bar', data: @js($barChartData) })"
                             x-init="init()"
                             x-on:livewire:navigating.window="destroy()"
                             class="relative h-56">
                            <canvas x-ref="canvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== TABELE (4 — sub-componente Livewire poll 30s) ===== --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2 uppercase tracking-wider">
                    <x-heroicon-o-table-cells class="w-4 h-4 text-emerald-500" />
                    Date operationale
                </h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <livewire:dashboard.tabele.top-clienti lazy />
                    <livewire:dashboard.tabele.soferi lazy />
                    <livewire:dashboard.tabele.recipienti lazy />
                    <livewire:dashboard.tabele.dozatoare-scadente lazy />
                </div>
            </div>

        </div>
    </div>

    @script
    <script>
        // Wrapper Alpine pentru cele 3 tipuri de chart Chart.js.
        // `tip` = 'line' | 'donut' | 'bar' — alege wrapper-ul global din app.js.
        // `data` = obiect { labels, datasets } (pre-calculat in PHP).
        // Lifecycle: init() creeaza chart, destroy() la livewire:navigating
        // (pre-cleanup pe canvas existent e gestionat de wrapper-ele globale).
        window.dashboardChartWrapper = function ({ tip, data }) {
            return {
                chart: null,
                init() {
                    const fn = ({
                        line: window.dashboardLineChart,
                        donut: window.dashboardDonutChart,
                        bar: window.dashboardBarChart,
                    })[tip];
                    if (!fn) return;
                    this.chart = fn(this.$refs.canvas, data);
                },
                destroy() {
                    if (this.chart) {
                        this.chart.destroy();
                        this.chart = null;
                    }
                },
            };
        };
    </script>
    @endscript
</div>
