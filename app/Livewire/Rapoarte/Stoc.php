<?php

namespace App\Livewire\Rapoarte;

use App\Models\CostCategory;
use App\Models\CostProduct;
use App\Models\Deposit;
use App\Models\Stoc as StocModel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Faza 5.2 — Raport stoc curent (vezi DOCUMENTATION.md §9 punct 16).
 *
 * Pagina read-only care prezinta soldul curent al fiecarui produs cu mişcari
 * pe fiecare depozit selectat. Pentru fiecare combinatie afisam 3 valori:
 *   - Sold     = IN − OUT − CUSTODIE  (ce e fizic in depozit)
 *   - Custodie = SUM(CUSTODIE)         (ce e la clienti, urmaribil)
 *   - Total    = Sold + Custodie       (ce e inca al firmei)
 *
 * Pattern: un singur query GROUP BY (`StocModel::agregatPerDepozit`) + pivotare
 * in PHP ca sa evitam N×M selecturi.
 *
 * ID-uri fixate folosite pentru card-urile sumar (regula §1.2):
 *   45 = APA PLATA 19L, 46 = APA PLATA 11L,
 *   47 = DOZATOR PODEA, 52 = DOZATOR CUSTODIE, 55 = DOZATOR CU FILTRE - Custodie
 */
#[Layout('layouts.app')]
class Stoc extends Component
{
    #[Url(as: 'q')]
    public string $cautare = '';

    #[Url(as: 'categorie')]
    public ?int $filtruCategorie = null;

    /**
     * Lista de id_depozit selectate. Default in mount() = toate active.
     * Persistate in URL ca CSV (Livewire #[Url] suporta arrays nativ ca query).
     */
    #[Url(as: 'depozite')]
    public array $depoziteSelectate = [];

    public function mount(): void
    {
        if (empty($this->depoziteSelectate)) {
            $this->depoziteSelectate = Deposit::where('activ', true)->pluck('id')->map(fn ($i) => (int) $i)->all();
        }
    }

    public function reseteazaFiltre(): void
    {
        $this->cautare = '';
        $this->filtruCategorie = null;
        $this->depoziteSelectate = Deposit::where('activ', true)->pluck('id')->map(fn ($i) => (int) $i)->all();
    }

    /**
     * Toggle un depozit din selectie. Evitam un select multiselect Tailwind +
     * Alpine custom — folosim checkboxe simple care apeleaza aceasta metoda.
     */
    public function comutaDepozit(int $idDepozit): void
    {
        if (in_array($idDepozit, $this->depoziteSelectate, true)) {
            $this->depoziteSelectate = array_values(array_filter(
                $this->depoziteSelectate,
                fn ($i) => (int) $i !== $idDepozit
            ));
        } else {
            $this->depoziteSelectate[] = $idDepozit;
        }
    }

    public function render()
    {
        $depoziteToate = Deposit::orderBy('denumire')->get();
        $depoziteVizibile = $depoziteToate->whereIn('id', $this->depoziteSelectate)->values();

        // Matrice agregata: [id_produs][id_depozit][tip => total]
        $matrice = StocModel::agregatPerDepozit($this->depoziteSelectate);

        // Lista produse cu mişcari (cheile primului nivel din matrice).
        // Aplicam filtrele de search/categorie pe acest set.
        $idsCuMiscari = array_keys($matrice);

        $produseQ = CostProduct::query()
            ->with('categorie')
            ->whereIn('id', $idsCuMiscari)
            ->orderBy('denumire');

        if ($this->cautare !== '') {
            $produseQ->where('denumire', 'like', '%' . $this->cautare . '%');
        }
        if ($this->filtruCategorie) {
            $produseQ->where('id_category', $this->filtruCategorie);
        }

        $produse = $produseQ->get();

        // Construim randurile finale gata pentru view: per produs, lista de
        // celule pe depozitele vizibile + totaluri pe rand.
        $randuri = [];
        $totaluriColoane = []; // [id_depozit => ['sold' => X, 'custodie' => Y, 'total' => Z]]
        foreach ($depoziteVizibile as $d) {
            $totaluriColoane[$d->id] = ['sold' => 0, 'custodie' => 0, 'total' => 0];
        }
        $totalGeneralFirma = 0;

        foreach ($produse as $p) {
            $celule = [];
            $totalProdus = 0;
            $totalProdusSold = 0;
            $totalProdusCustodie = 0;

            foreach ($depoziteVizibile as $d) {
                $valori = $matrice[$p->id][$d->id] ?? [
                    StocModel::TIP_IN => 0,
                    StocModel::TIP_OUT => 0,
                    StocModel::TIP_CUSTODIE => 0,
                ];
                $sold = $valori[StocModel::TIP_IN] - $valori[StocModel::TIP_OUT] - $valori[StocModel::TIP_CUSTODIE];
                $custodie = $valori[StocModel::TIP_CUSTODIE];
                $total = $sold + $custodie;

                $celule[$d->id] = [
                    'sold' => $sold,
                    'custodie' => $custodie,
                    'total' => $total,
                ];

                $totaluriColoane[$d->id]['sold'] += $sold;
                $totaluriColoane[$d->id]['custodie'] += $custodie;
                $totaluriColoane[$d->id]['total'] += $total;

                $totalProdusSold += $sold;
                $totalProdusCustodie += $custodie;
                $totalProdus += $total;
            }

            $randuri[] = [
                'produs' => $p,
                'celule' => $celule,
                'totalProdusSold' => $totalProdusSold,
                'totalProdusCustodie' => $totalProdusCustodie,
                'totalProdus' => $totalProdus,
            ];

            $totalGeneralFirma += $totalProdus;
        }

        // Card-uri sumar pe ID-urile fixate (apa + dozatoare). Daca filtrele
        // exclud aceste produse, card-urile arata 0 — comportament corect.
        $rezuma = function (int $idProdus) use ($matrice) {
            if (! isset($matrice[$idProdus])) {
                return ['sold' => 0, 'custodie' => 0, 'total' => 0];
            }
            $sold = $custodie = 0;
            foreach ($matrice[$idProdus] as $vals) {
                $sold += $vals[StocModel::TIP_IN] - $vals[StocModel::TIP_OUT] - $vals[StocModel::TIP_CUSTODIE];
                $custodie += $vals[StocModel::TIP_CUSTODIE];
            }
            return ['sold' => $sold, 'custodie' => $custodie, 'total' => $sold + $custodie];
        };

        $sumar = [
            'apa19' => $rezuma(45),
            'apa11' => $rezuma(46),
            'dozatorPodea' => $rezuma(47),
            'dozatorCustodie' => $rezuma(52),
            'dozatorFiltru' => $rezuma(55),
        ];

        return view('livewire.rapoarte.stoc', [
            'depoziteToate' => $depoziteToate,
            'depoziteVizibile' => $depoziteVizibile,
            'categorii' => CostCategory::orderBy('denumire')->get(),
            'randuri' => $randuri,
            'totaluriColoane' => $totaluriColoane,
            'totalGeneralFirma' => $totalGeneralFirma,
            'sumar' => $sumar,
        ]);
    }
}
