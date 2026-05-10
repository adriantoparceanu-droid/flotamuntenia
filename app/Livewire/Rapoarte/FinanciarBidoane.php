<?php

namespace App\Livewire\Rapoarte;

use App\Models\Comanda;
use App\Models\ComandaProdus;
use App\Models\ComandaRapida;
use App\Models\ComandaRapidaProdus;
use App\Models\CostProduct;
use App\Models\Problema;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Faza 5.5 — Raport financiar bidoane (vezi DOCUMENTATION.md §9 punct 19).
 *
 * Raport pe interval de date cu:
 *   - Numar bidoane livrate (19L = id_produs 45, 11L = id_produs 46) pe coloane separate
 *   - Sume incasate per modalitate de plata (Cash / OP / Card / Alta) per zi
 *   - Defalcare pe sursa: Comenzi clasice / Comenzi rapide / Probleme
 *   - Totaluri generale
 *
 * Surse de date (decizie validata cu user):
 *   - Comenzi (cu produse) → conteaza bidoane + sume pe modalitate
 *   - Comenzi rapide (cu produse) → conteaza bidoane + sume pe „Alta" (vezi mai jos)
 *   - Probleme → doar sume pe modalitate (fara bidoane)
 *
 * NU includem garantii recipienti din CI3: in Laravel modelul Recipient nu are
 * coloane de bani (suma_dozator/suma_recipienti din CI3 erau pe alt model). Acest
 * flux financiar nu mai exista in noua arhitectura — daca apare nevoie, se va
 * extrage dintr-o noua sursa explicita.
 *
 * NOTA: ComandaRapida NU are coloana `id_modalitate_plata` (omisa fata de CI3).
 * Convenție: cand filtrul de modalitate este „Toate", comenzile rapide intra
 * la coloana „Alta" (default). Cand admin filtreaza pe o modalitate specifica,
 * comenzile rapide se exclud complet (nu putem clasifica). Ramane de discutat
 * daca adaugam coloana in viitor.
 *
 * Sursa de adevar pentru pretul comenzii este suma liniilor `comenzi_produse`
 * (resp. `comenzi_rapide_produse`); nu exista coloana `suma_incasata` separata
 * ca in CI3.
 *
 * Filtre:
 *   - deLa / panaLa: interval data_livrare (default = luna curenta)
 *   - idModalitatePlata: 0 = Toate / 1 = Cash / 2 = OP / 3 = Card / 4 = Alta
 *   - achitat: -1 = Toate / 1 = Da / 0 = Nu (default = Toate)
 */
#[Layout('layouts.app')]
class FinanciarBidoane extends Component
{
    public const ID_BIDON_19L = 45;
    public const ID_BIDON_11L = 46;

    public const ACHITAT_TOATE = -1;

    #[Url(as: 'de_la')]
    public string $deLa = '';

    #[Url(as: 'pana_la')]
    public string $panaLa = '';

    /**
     * Filtru modalitate plata: 0 = Toate, 1 = Cash, 2 = OP, 3 = Card, 4 = Alta.
     */
    #[Url(as: 'modalitate')]
    public int $idModalitatePlata = 0;

    /**
     * Filtru achitat: -1 = Toate, 1 = Da, 0 = Nu.
     */
    #[Url(as: 'achitat')]
    public int $achitat = self::ACHITAT_TOATE;

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
        $this->deLa = now()->startOfMonth()->toDateString();
        $this->panaLa = now()->endOfMonth()->toDateString();
        $this->idModalitatePlata = 0;
        $this->achitat = self::ACHITAT_TOATE;
    }

    /**
     * Formateaza data `2026-05-10` -> `10 Mai 2026` in romana.
     */
    public function formatData(string $data): string
    {
        try {
            $c = Carbon::createFromFormat('Y-m-d', $data);
        } catch (\Throwable $e) {
            return $data;
        }
        $luniRo = [
            1 => 'Ian', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mai', 6 => 'Iun', 7 => 'Iul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Noi', 12 => 'Dec',
        ];
        return $c->format('d') . ' ' . $luniRo[(int) $c->format('n')] . ' ' . $c->format('Y');
    }

    /**
     * Eticheta sursa pentru randul agregat.
     */
    public function etichetaSursa(string $cod): string
    {
        return match ($cod) {
            'comenzi' => 'Comenzi',
            'rapide' => 'Comenzi rapide',
            'probleme' => 'Probleme',
            default => $cod,
        };
    }

    public function culoareSursa(string $cod): string
    {
        return match ($cod) {
            'comenzi' => 'bg-sky-100 text-sky-700',
            'rapide' => 'bg-violet-100 text-violet-700',
            'probleme' => 'bg-rose-100 text-rose-700',
            default => 'bg-gray-100 text-gray-500',
        };
    }

    /**
     * Helper: contorizeaza nr bidoane (19L + 11L) din liniile unei comenzi
     * sau comenzi rapide (id-ul liniei e id_comanda / id_comanda_rapida).
     *
     * @param  array<int, object>  $linii  rezultatele queryului grouped per id+id_produs
     * @return array{nr19l: int, nr11l: int}
     */
    private function contBidoane(int $idParinte, array &$mapBidoane): array
    {
        return $mapBidoane[$idParinte] ?? ['nr19l' => 0, 'nr11l' => 0];
    }

    public function render()
    {
        $de = $this->deLa;
        $pana = $this->panaLa;
        $cheieZi = [];

        // Pre-incarcam liniile bidoane (19L + 11L) pentru toate comenzile/rapidele
        // din interval, intr-o singura interogare per sursa, ca sa evitam N+1.

        // ===== COMENZI =====
        $comenziQuery = Comanda::query()
            ->whereDate('data_livrare', '>=', $de)
            ->whereDate('data_livrare', '<=', $pana);
        if ($this->achitat === 0) {
            $comenziQuery->where('achitat', false);
        } elseif ($this->achitat === 1) {
            $comenziQuery->where('achitat', true);
        }
        if ($this->idModalitatePlata > 0) {
            $comenziQuery->where('id_modalitate_plata', $this->idModalitatePlata);
        }
        $comenzi = $comenziQuery->get(['id', 'data_livrare', 'id_modalitate_plata']);

        $idsComenzi = $comenzi->pluck('id')->all();
        $bidoaneComenzi = [];
        if (! empty($idsComenzi)) {
            $linii = ComandaProdus::query()
                ->whereIn('id_comanda', $idsComenzi)
                ->whereIn('id_produs', [self::ID_BIDON_19L, self::ID_BIDON_11L])
                ->get(['id_comanda', 'id_produs', 'cantitate']);
            foreach ($linii as $l) {
                $bidoaneComenzi[(int) $l->id_comanda] ??= ['nr19l' => 0, 'nr11l' => 0];
                if ((int) $l->id_produs === self::ID_BIDON_19L) {
                    $bidoaneComenzi[(int) $l->id_comanda]['nr19l'] += (int) $l->cantitate;
                } else {
                    $bidoaneComenzi[(int) $l->id_comanda]['nr11l'] += (int) $l->cantitate;
                }
            }
        }

        // Pretul total per comanda = SUM(cantitate * pret) pe linii
        $sumeComenzi = [];
        if (! empty($idsComenzi)) {
            $rows = ComandaProdus::query()
                ->whereIn('id_comanda', $idsComenzi)
                ->selectRaw('id_comanda, SUM(cantitate * pret) as total')
                ->groupBy('id_comanda')
                ->get();
            foreach ($rows as $r) {
                $sumeComenzi[(int) $r->id_comanda] = (float) $r->total;
            }
        }

        foreach ($comenzi as $c) {
            $zi = $c->data_livrare->format('Y-m-d');
            $cheia = $zi . '|comenzi';
            $cheieZi[$cheia] ??= $this->celulaGoala($zi, 'comenzi');
            $bid = $bidoaneComenzi[(int) $c->id] ?? ['nr19l' => 0, 'nr11l' => 0];
            $cheieZi[$cheia]['nr19l'] += $bid['nr19l'];
            $cheieZi[$cheia]['nr11l'] += $bid['nr11l'];

            $sumaC = $sumeComenzi[(int) $c->id] ?? 0.0;
            $this->adaugaSumaPeColoana($cheieZi[$cheia], (int) $c->id_modalitate_plata, $sumaC);
        }

        // ===== COMENZI RAPIDE =====
        // Atentie: comenzi_rapide NU au id_modalitate_plata.
        // Daca filtrul modalitate este o valoare specifica (≠ 0/Toate), excludem.
        $rapideleSeIau = $this->idModalitatePlata === 0 || $this->idModalitatePlata === Comanda::MODPLATA_ALTA;

        if ($rapideleSeIau) {
            $rapideQuery = ComandaRapida::query()
                ->whereDate('data_livrare', '>=', $de)
            ->whereDate('data_livrare', '<=', $pana);
            if ($this->achitat === 0) {
                $rapideQuery->where('achitat', false);
            } elseif ($this->achitat === 1) {
                $rapideQuery->where('achitat', true);
            }
            $rapide = $rapideQuery->get(['id', 'data_livrare']);

            $idsRapide = $rapide->pluck('id')->all();
            $bidoaneRapide = [];
            if (! empty($idsRapide)) {
                $linii = ComandaRapidaProdus::query()
                    ->whereIn('id_comanda_rapida', $idsRapide)
                    ->whereIn('id_produs', [self::ID_BIDON_19L, self::ID_BIDON_11L])
                    ->get(['id_comanda_rapida', 'id_produs', 'cantitate']);
                foreach ($linii as $l) {
                    $bidoaneRapide[(int) $l->id_comanda_rapida] ??= ['nr19l' => 0, 'nr11l' => 0];
                    if ((int) $l->id_produs === self::ID_BIDON_19L) {
                        $bidoaneRapide[(int) $l->id_comanda_rapida]['nr19l'] += (int) $l->cantitate;
                    } else {
                        $bidoaneRapide[(int) $l->id_comanda_rapida]['nr11l'] += (int) $l->cantitate;
                    }
                }
            }

            $sumeRapide = [];
            if (! empty($idsRapide)) {
                $rows = ComandaRapidaProdus::query()
                    ->whereIn('id_comanda_rapida', $idsRapide)
                    ->selectRaw('id_comanda_rapida, SUM(cantitate * pret) as total')
                    ->groupBy('id_comanda_rapida')
                    ->get();
                foreach ($rows as $r) {
                    $sumeRapide[(int) $r->id_comanda_rapida] = (float) $r->total;
                }
            }

            foreach ($rapide as $r) {
                $zi = $r->data_livrare->format('Y-m-d');
                $cheia = $zi . '|rapide';
                $cheieZi[$cheia] ??= $this->celulaGoala($zi, 'rapide');
                $bid = $bidoaneRapide[(int) $r->id] ?? ['nr19l' => 0, 'nr11l' => 0];
                $cheieZi[$cheia]['nr19l'] += $bid['nr19l'];
                $cheieZi[$cheia]['nr11l'] += $bid['nr11l'];

                $sumaR = $sumeRapide[(int) $r->id] ?? 0.0;
                // ComandaRapida → coloana Alta (default), conform conventiei documentate
                $this->adaugaSumaPeColoana($cheieZi[$cheia], Comanda::MODPLATA_ALTA, $sumaR);
            }
        }

        // ===== PROBLEME =====
        $problemeQuery = Problema::query()
            ->whereDate('data_livrare', '>=', $de)
            ->whereDate('data_livrare', '<=', $pana);
        if ($this->achitat === 0) {
            $problemeQuery->where('achitat', false);
        } elseif ($this->achitat === 1) {
            $problemeQuery->where('achitat', true);
        }
        if ($this->idModalitatePlata > 0) {
            $problemeQuery->where('id_modalitate_plata', $this->idModalitatePlata);
        }
        $probleme = $problemeQuery->get(['id', 'data_livrare', 'id_modalitate_plata', 'suma']);

        foreach ($probleme as $p) {
            $zi = $p->data_livrare->format('Y-m-d');
            $cheia = $zi . '|probleme';
            $cheieZi[$cheia] ??= $this->celulaGoala($zi, 'probleme');
            // Probleme nu au bidoane — doar suma
            $this->adaugaSumaPeColoana($cheieZi[$cheia], (int) $p->id_modalitate_plata, (float) $p->suma);
        }

        // ===== Sortare: pe zi asc, in cadrul zilei ordinea sursa: comenzi → rapide → probleme =====
        $ordineSursa = ['comenzi' => 0, 'rapide' => 1, 'probleme' => 2];
        $randuri = array_values($cheieZi);
        usort($randuri, function ($a, $b) use ($ordineSursa) {
            if ($a['zi'] !== $b['zi']) {
                return $a['zi'] <=> $b['zi'];
            }
            return $ordineSursa[$a['sursa']] <=> $ordineSursa[$b['sursa']];
        });

        // ===== Totaluri =====
        $totalNr19l = 0;
        $totalNr11l = 0;
        $totalCash = 0.0;
        $totalOp = 0.0;
        $totalCard = 0.0;
        $totalAlta = 0.0;
        foreach ($randuri as $r) {
            $totalNr19l += $r['nr19l'];
            $totalNr11l += $r['nr11l'];
            $totalCash += $r['sumaCash'];
            $totalOp += $r['sumaOp'];
            $totalCard += $r['sumaCard'];
            $totalAlta += $r['sumaAlta'];
        }
        $totalGeneral = $totalCash + $totalOp + $totalCard + $totalAlta;

        return view('livewire.rapoarte.financiar-bidoane', [
            'randuri' => $randuri,
            'totalNr19l' => $totalNr19l,
            'totalNr11l' => $totalNr11l,
            'totalCash' => $totalCash,
            'totalOp' => $totalOp,
            'totalCard' => $totalCard,
            'totalAlta' => $totalAlta,
            'totalGeneral' => $totalGeneral,
        ]);
    }

    /**
     * Initializeaza o celula goala pentru combinarea (zi, sursa).
     */
    private function celulaGoala(string $zi, string $sursa): array
    {
        return [
            'zi' => $zi,
            'sursa' => $sursa,
            'nr19l' => 0,
            'nr11l' => 0,
            'sumaCash' => 0.0,
            'sumaOp' => 0.0,
            'sumaCard' => 0.0,
            'sumaAlta' => 0.0,
        ];
    }

    /**
     * Adauga suma in coloana corespunzatoare modalitatii de plata.
     */
    private function adaugaSumaPeColoana(array &$celula, int $idModalitate, float $suma): void
    {
        switch ($idModalitate) {
            case Comanda::MODPLATA_CASH:
                $celula['sumaCash'] += $suma;
                break;
            case Comanda::MODPLATA_OP:
                $celula['sumaOp'] += $suma;
                break;
            case Comanda::MODPLATA_CARD:
                $celula['sumaCard'] += $suma;
                break;
            case Comanda::MODPLATA_ALTA:
            default:
                $celula['sumaAlta'] += $suma;
                break;
        }
    }
}
