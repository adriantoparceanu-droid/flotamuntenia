<?php

namespace App\Livewire;

use App\Models\Cheltuiala;
use App\Models\Client;
use App\Models\Comanda;
use App\Models\Dozator;
use App\Models\DozatorFiltre;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Poll;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Panou principal admin — dashboard analitic cu KPI live + grafice + tabele.
 *
 * Strategie polling (CLAUDE.md §3 pentru cPanel shared):
 *   - Wrapper-ul principal (KPI + secțiune atenție) — `#[Poll('10s')]`
 *   - Graficele — date calculate la mount, re-render manual cu buton refresh
 *     (graficele sunt in `wire:ignore` deci morfdom-ul Livewire nu le sterge)
 *   - Tabele (sub-componente Livewire embed-uite) — `#[Poll('30s')]` propriu
 */
#[Layout('layouts.app')]
#[Title('Panou principal')]
class Dashboard extends Component
{
    /**
     * Token de refresh — schimbat la click pe „Refresh grafice" pentru a forta
     * re-evaluarea blocurilor `@js()` (Alpine init() reciteste data noua).
     */
    public int $refreshChartsToken = 0;

    public function refreshCharts(): void
    {
        $this->refreshChartsToken++;
    }

    /**
     * Polling 10s pe wrapper-ul principal — afecteaza KPI cards si secțiunea
     * atenție. Sub-componentele tabelelor au polling propriu 30s.
     * Graficele in `wire:ignore` raman cache-uite pana la refresh manual.
     */
    #[Poll('10s')]
    public function render()
    {
        return view('livewire.dashboard', [
            'lineChartData' => $this->buildLineChartData(),
            'donutChartData' => $this->buildDonutChartData(),
            'barChartData' => $this->buildBarChartData(),
        ]);
    }

    // ===== KPI CARDS (8) =====

    #[Computed]
    public function comenziAzi(): int
    {
        return Comanda::whereDate('data_livrare', today())->vizibile()->count();
    }

    #[Computed]
    public function comenziNelivrateAzi(): int
    {
        return Comanda::whereDate('data_livrare', today())
            ->vizibile()
            ->where('livrat', false)
            ->count();
    }

    #[Computed]
    public function comenziInAsteptare(): int
    {
        return Comanda::where('status', Comanda::STATUS_IN_ASTEPTARE)->count();
    }

    /**
     * Total bidoane 19L livrate luna curenta (sum din `comenzi.nr_recipienti`
     * pentru comenzile livrate). Plus comenzi rapide + probleme — TBD daca
     * vrem sa includem (momentan doar comenzi).
     */
    #[Computed]
    public function bidoane19lLuna(): int
    {
        return (int) Comanda::whereYear('data_livrare', now()->year)
            ->whereMonth('data_livrare', now()->month)
            ->vizibile()
            ->where('livrat', true)
            ->sum('nr_recipienti');
    }

    #[Computed]
    public function bidoane11lLuna(): int
    {
        return (int) Comanda::whereYear('data_livrare', now()->year)
            ->whereMonth('data_livrare', now()->month)
            ->vizibile()
            ->where('livrat', true)
            ->sum('nr_pahare');
    }

    #[Computed]
    public function totalCheltuieliLuna(): float
    {
        return (float) Cheltuiala::whereYear('data', now()->year)
            ->whereMonth('data', now()->month)
            ->sum('total');
    }

    /**
     * Combina igienizari scadente (7 zile) + filtre scadente (30 zile).
     * Singur card pentru a reduce numarul de KPI vizuale.
     */
    #[Computed]
    public function mentenantaScadente(): int
    {
        $igienizari = Dozator::where('activ', true)
            ->whereNotNull('perioada_igenizare')
            ->whereDate('perioada_igenizare', '<=', now()->addDays(7)->toDateString())
            ->count();

        $filtre = DozatorFiltre::where('status', DozatorFiltre::STATUS_ACTIV)
            ->whereNotNull('data_urmatoare_mentenanta')
            ->whereDate('data_urmatoare_mentenanta', '<=', now()->addDays(30)->toDateString())
            ->count();

        return $igienizari + $filtre;
    }

    #[Computed]
    public function totalClientiActivi(): int
    {
        return Client::where('reziliat', false)->count();
    }

    // ===== SECȚIUNE „NECESITĂ ATENȚIE" =====

    #[Computed]
    public function alerteAtentie(): array
    {
        $alerte = [];

        $nealocate = Comanda::whereDate('data_livrare', today())
            ->vizibile()
            ->where(function ($q) {
                $q->whereNull('id_masina')->orWhere('id_masina', 0);
            })
            ->count();
        if ($nealocate > 0) {
            $alerte[] = [
                'icon' => 'truck',
                'text' => "{$nealocate} comand" . ($nealocate === 1 ? 'a' : 'i') . " nealocat" . ($nealocate === 1 ? 'a' : 'e') . " la masina pentru azi",
                'href' => route('lista-zilnica'),
                'culoare' => 'amber',
            ];
        }

        $vechiAprobare = Comanda::where('status', Comanda::STATUS_IN_ASTEPTARE)
            ->where('created_at', '<=', now()->subDay())
            ->count();
        if ($vechiAprobare > 0) {
            $alerte[] = [
                'icon' => 'clock',
                'text' => "{$vechiAprobare} comand" . ($vechiAprobare === 1 ? 'a' : 'i') . " in asteptare aprobare de peste 24h",
                'href' => route('comenzi.aprobare'),
                'culoare' => 'orange',
            ];
        }

        $filtreExpirate = DozatorFiltre::where('status', DozatorFiltre::STATUS_ACTIV)
            ->whereNotNull('data_urmatoare_mentenanta')
            ->whereDate('data_urmatoare_mentenanta', '<', today())
            ->count();
        if ($filtreExpirate > 0) {
            $alerte[] = [
                'icon' => 'exclamation-triangle',
                'text' => "{$filtreExpirate} dozator" . ($filtreExpirate === 1 ? '' : 'e') . " cu filtre EXPIRATE (deadline depasit)",
                'href' => route('dozatoare.index') . '?tip=filtre',
                'culoare' => 'red',
            ];
        }

        return $alerte;
    }

    // ===== DATE GRAFICE (calculate o data la fiecare render — dar graficele
    //       sunt in `wire:ignore`, deci JS le redeseneaza doar la refresh manual) =====

    /**
     * Line chart — comenzi livrate vs total ultimele 30 zile.
     */
    private function buildLineChartData(): array
    {
        $azi = today();
        $start = $azi->copy()->subDays(29); // 30 zile inclusiv azi

        // Group in PHP pentru portabilitate SQLite/MariaDB (evitam date() function)
        $comenzi = Comanda::whereBetween('data_livrare', [$start->toDateString(), $azi->toDateString()])
            ->vizibile()
            ->get(['data_livrare', 'livrat'])
            ->groupBy(fn ($c) => $c->data_livrare->toDateString());

        $labels = [];
        $totale = [];
        $livrate = [];

        for ($i = 0; $i < 30; $i++) {
            $zi = $start->copy()->addDays($i);
            $cheie = $zi->toDateString();
            $grupZi = $comenzi[$cheie] ?? collect();
            $labels[] = $zi->format('d.m');
            $totale[] = $grupZi->count();
            $livrate[] = $grupZi->where('livrat', true)->count();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total programate',
                    'data' => $totale,
                    'borderColor' => 'rgb(99, 102, 241)', // indigo-500
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Livrate',
                    'data' => $livrate,
                    'borderColor' => 'rgb(16, 185, 129)', // emerald-500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.3,
                    'fill' => false,
                ],
            ],
        ];
    }

    /**
     * Donut chart — distributie tipuri comenzi luna curenta.
     */
    private function buildDonutChartData(): array
    {
        $tipuri = Comanda::whereYear('data_livrare', now()->year)
            ->whereMonth('data_livrare', now()->month)
            ->vizibile()
            ->selectRaw('tip_comanda, count(*) as nr')
            ->groupBy('tip_comanda')
            ->pluck('nr', 'tip_comanda')
            ->toArray();

        $labels = ['Abonament', 'Consum suplimentar', 'Fara abonament'];
        $values = [
            (int) ($tipuri[Comanda::TIP_ABONAMENT] ?? 0),
            (int) ($tipuri[Comanda::TIP_CONSUM_SUPLIMENTAR] ?? 0),
            (int) ($tipuri[Comanda::TIP_FARA_ABONAMENT] ?? 0),
        ];

        return [
            'labels' => $labels,
            'datasets' => [[
                'data' => $values,
                'backgroundColor' => [
                    'rgb(99, 102, 241)',  // indigo
                    'rgb(245, 158, 11)',  // amber
                    'rgb(16, 185, 129)',  // emerald
                ],
                'borderWidth' => 2,
                'borderColor' => '#ffffff',
            ]],
        ];
    }

    /**
     * Bar chart — cheltuieli vs vanzari (incasari) ultimele 6 luni.
     * „Vanzari" = sum total din comenzi livrate per luna (calculat din linii).
     * Pentru perf, calculam din `comenzi_produse` join `comenzi`.
     */
    private function buildBarChartData(): array
    {
        $azi = today();
        $start = $azi->copy()->subMonths(5)->startOfMonth();
        $end = $azi->copy()->endOfMonth();

        // Cheltuieli per luna — sum direct + group in PHP (portabilitate SQLite/MariaDB)
        $cheltuieliPerLuna = Cheltuiala::whereBetween('data', [$start->toDateString(), $end->toDateString()])
            ->get(['data', 'total'])
            ->groupBy(fn ($c) => $c->data->format('Y-m'))
            ->map(fn ($grup) => $grup->sum(fn ($c) => (float) $c->total));

        // Incasari per luna — eager load produse pentru a calcula totalul comenzii
        $vanzariPerLuna = Comanda::with('produse')
            ->vizibile()
            ->where('livrat', true)
            ->whereBetween('data_livrare', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn ($c) => $c->data_livrare->format('Y-m'))
            ->map(fn ($grup) => $grup->sum(fn ($c) => $c->total()));

        $labels = [];
        $cheltSerie = [];
        $vanzSerie = [];

        for ($i = 0; $i < 6; $i++) {
            $luna = $start->copy()->addMonths($i);
            $cheie = $luna->format('Y-m');
            $labels[] = $luna->locale('ro')->translatedFormat('M Y');
            $cheltSerie[] = (float) ($cheltuieliPerLuna[$cheie] ?? 0);
            $vanzSerie[] = (float) ($vanzariPerLuna[$cheie] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cheltuieli',
                    'data' => $cheltSerie,
                    'backgroundColor' => 'rgba(244, 63, 94, 0.8)', // rose-500
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Incasari',
                    'data' => $vanzSerie,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)', // emerald-500
                    'borderRadius' => 4,
                ],
            ],
        ];
    }

    public function lunaCurentaText(): string
    {
        return Carbon::now()->locale('ro')->translatedFormat('F Y');
    }
}
