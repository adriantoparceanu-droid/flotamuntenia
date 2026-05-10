<?php

namespace App\Livewire\Rapoarte;

use App\Models\CheltuialaProdus;
use App\Models\ComandaProdus;
use App\Models\ComandaRapidaProdus;
use App\Models\CostCategory;
use App\Models\CostProduct;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Faza 5.3 — Raport cheltuieli vs vânzări (vezi DOCUMENTATION.md §9 punct 17).
 *
 * Comparativ pe perioadă: pentru fiecare produs cu activitate, cantitățile +
 * sumele de IN (achiziții) și OUT (vânzări livrate efectiv) + profitul absolut
 * și procentual. Drill-down pe lună la fiecare rând (Alpine x-show local).
 *
 * Surse de date (decizie validată cu user):
 *   - IN: cheltuieli_produse JOIN cheltuieli (data în interval)
 *   - OUT: comenzi_produse JOIN comenzi (data_livrare în interval, livrat=1)
 *         + comenzi_rapide_produse JOIN comenzi_rapide (data_livrare în interval, livrat=1)
 *
 * NU folosim Stoc — acolo nu avem prețul persistat. Tabelele-sursă au cantitate
 * + pret per linie, deci sumele rezultă direct.
 */
#[Layout('layouts.app')]
class CheltuieliVanzari extends Component
{
    #[Url(as: 'q')]
    public string $cautare = '';

    #[Url(as: 'categorie')]
    public ?int $filtruCategorie = null;

    #[Url(as: 'de_la')]
    public string $deLa = '';

    #[Url(as: 'pana_la')]
    public string $panaLa = '';

    public function mount(): void
    {
        if ($this->deLa === '') {
            $this->deLa = now()->startOfMonth()->toDateString();
        }
        if ($this->panaLa === '') {
            $this->panaLa = now()->endOfMonth()->toDateString();
        }
    }

    public function reseteazaFiltre(): void
    {
        $this->cautare = '';
        $this->filtruCategorie = null;
        $this->deLa = now()->startOfMonth()->toDateString();
        $this->panaLa = now()->endOfMonth()->toDateString();
    }

    /**
     * SQL portabil pentru gruparea pe luna `YYYY-MM`. SQLite foloseste
     * `strftime('%Y-%m', col)`; MySQL/MariaDB foloseste `DATE_FORMAT(col, '%Y-%m')`.
     */
    private function expresieLuna(string $coloana): string
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return "strftime('%Y-%m', {$coloana})";
        }
        return "DATE_FORMAT({$coloana}, '%Y-%m')";
    }

    /**
     * Formateaza luna `2026-05` -> `Mai 2026` in romana.
     */
    public function formatLuna(string $yyyymm): string
    {
        try {
            $c = Carbon::createFromFormat('Y-m-d', $yyyymm . '-01');
        } catch (\Throwable $e) {
            return $yyyymm;
        }

        $luniRo = [
            1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
            5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
            9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie',
        ];

        return $luniRo[(int) $c->format('n')] . ' ' . $c->format('Y');
    }

    public function render()
    {
        $exprLunaCheltuieli = $this->expresieLuna('cheltuieli.data');
        $exprLunaComenzi = $this->expresieLuna('comenzi.data_livrare');
        $exprLunaRapide = $this->expresieLuna('comenzi_rapide.data_livrare');

        // ===== IN: achiziții =====
        $intrari = CheltuialaProdus::query()
            ->join('cheltuieli', 'cheltuieli.id', '=', 'cheltuieli_produse.id_cheltuiala')
            ->whereBetween('cheltuieli.data', [$this->deLa, $this->panaLa])
            ->select([
                'cheltuieli_produse.id_produs as id_produs',
                DB::raw($exprLunaCheltuieli . ' as luna'),
                DB::raw('SUM(cheltuieli_produse.cantitate) as cant'),
                DB::raw('SUM(cheltuieli_produse.cantitate * cheltuieli_produse.pret) as suma'),
            ])
            ->groupBy('cheltuieli_produse.id_produs', 'luna')
            ->get();

        // ===== OUT: comenzi livrate =====
        $iesiriComenzi = ComandaProdus::query()
            ->join('comenzi', 'comenzi.id', '=', 'comenzi_produse.id_comanda')
            ->where('comenzi.livrat', true)
            ->whereBetween('comenzi.data_livrare', [$this->deLa, $this->panaLa])
            ->select([
                'comenzi_produse.id_produs as id_produs',
                DB::raw($exprLunaComenzi . ' as luna'),
                DB::raw('SUM(comenzi_produse.cantitate) as cant'),
                DB::raw('SUM(comenzi_produse.cantitate * comenzi_produse.pret) as suma'),
            ])
            ->groupBy('comenzi_produse.id_produs', 'luna')
            ->get();

        // ===== OUT: comenzi rapide livrate =====
        $iesiriRapide = ComandaRapidaProdus::query()
            ->join('comenzi_rapide', 'comenzi_rapide.id', '=', 'comenzi_rapide_produse.id_comanda_rapida')
            ->where('comenzi_rapide.livrat', true)
            ->whereBetween('comenzi_rapide.data_livrare', [$this->deLa, $this->panaLa])
            ->select([
                'comenzi_rapide_produse.id_produs as id_produs',
                DB::raw($exprLunaRapide . ' as luna'),
                DB::raw('SUM(comenzi_rapide_produse.cantitate) as cant'),
                DB::raw('SUM(comenzi_rapide_produse.cantitate * comenzi_rapide_produse.pret) as suma'),
            ])
            ->groupBy('comenzi_rapide_produse.id_produs', 'luna')
            ->get();

        // ===== Unire matrice [id_produs => [luna => ['in' => [cant, suma], 'out' => [cant, suma]]]] =====
        $matrice = [];

        $adaugaLinie = function (array &$matrice, int $idProdus, string $luna, string $tip, float $cant, float $suma) {
            $matrice[$idProdus] ??= [];
            $matrice[$idProdus][$luna] ??= [
                'in' => ['cant' => 0, 'suma' => 0.0],
                'out' => ['cant' => 0, 'suma' => 0.0],
            ];
            $matrice[$idProdus][$luna][$tip]['cant'] += (int) $cant;
            $matrice[$idProdus][$luna][$tip]['suma'] += (float) $suma;
        };

        foreach ($intrari as $r) {
            $adaugaLinie($matrice, (int) $r->id_produs, (string) $r->luna, 'in', (float) $r->cant, (float) $r->suma);
        }
        foreach ($iesiriComenzi as $r) {
            $adaugaLinie($matrice, (int) $r->id_produs, (string) $r->luna, 'out', (float) $r->cant, (float) $r->suma);
        }
        foreach ($iesiriRapide as $r) {
            $adaugaLinie($matrice, (int) $r->id_produs, (string) $r->luna, 'out', (float) $r->cant, (float) $r->suma);
        }

        $idsProduse = array_keys($matrice);

        // ===== Filtru produse (cautare + categorie) =====
        $produseQ = CostProduct::query()
            ->with('categorie')
            ->whereIn('id', $idsProduse)
            ->orderBy('denumire');

        if ($this->cautare !== '') {
            $produseQ->where('denumire', 'like', '%' . $this->cautare . '%');
        }
        if ($this->filtruCategorie) {
            $produseQ->where('id_category', $this->filtruCategorie);
        }

        $produse = $produseQ->get();

        // ===== Construim randurile finale + totaluri =====
        $randuri = [];
        $totalIn = 0.0;
        $totalOut = 0.0;

        foreach ($produse as $p) {
            $luniRand = $matrice[$p->id] ?? [];
            ksort($luniRand); // chronologic asc

            $cantInProdus = $sumaInProdus = 0;
            $cantOutProdus = $sumaOutProdus = 0;

            $subRanduri = [];
            foreach ($luniRand as $luna => $vals) {
                $cantIn = (int) $vals['in']['cant'];
                $sumaIn = (float) $vals['in']['suma'];
                $cantOut = (int) $vals['out']['cant'];
                $sumaOut = (float) $vals['out']['suma'];

                $cantInProdus += $cantIn;
                $sumaInProdus += $sumaIn;
                $cantOutProdus += $cantOut;
                $sumaOutProdus += $sumaOut;

                $subRanduri[] = [
                    'luna' => $luna,
                    'lunaEticheta' => $this->formatLuna($luna),
                    'cantIn' => $cantIn,
                    'sumaIn' => $sumaIn,
                    'cantOut' => $cantOut,
                    'sumaOut' => $sumaOut,
                    'profit' => $sumaOut - $sumaIn,
                ];
            }

            $profit = $sumaOutProdus - $sumaInProdus;
            // Profit % raportat la suma achiziții (cea mai stabilă bază);
            // dacă admin n-are achiziții (sumaIn=0) dar are vânzări, e profit infinit —
            // afișăm null (view-ul afișează „—")
            $profitPct = $sumaInProdus > 0 ? ($profit / $sumaInProdus) * 100 : null;

            $randuri[] = [
                'produs' => $p,
                'cantIn' => $cantInProdus,
                'sumaIn' => $sumaInProdus,
                'cantOut' => $cantOutProdus,
                'sumaOut' => $sumaOutProdus,
                'difCant' => $cantInProdus - $cantOutProdus,
                'profit' => $profit,
                'profitPct' => $profitPct,
                'subRanduri' => $subRanduri,
            ];

            $totalIn += $sumaInProdus;
            $totalOut += $sumaOutProdus;
        }

        $profitTotal = $totalOut - $totalIn;

        return view('livewire.rapoarte.cheltuieli-vanzari', [
            'categorii' => CostCategory::orderBy('denumire')->get(),
            'randuri' => $randuri,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'profitTotal' => $profitTotal,
        ]);
    }
}
