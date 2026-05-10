<?php

namespace App\Services;

use App\Models\Cheltuiala;
use App\Models\Comanda;
use App\Models\ComandaRapida;
use App\Models\Dozator;
use App\Models\DozatorFiltre;
use App\Models\Stoc;
use Illuminate\Support\Facades\DB;

// Centralizeaza generarea miscarilor de stoc pentru entitatile care
// produc iesiri/intrari (comenzi, comenzi rapide, dozatoare etc.).
//
// Convenția adoptata: la editarea unei comenzi NU mentinem un istoric de
// "compensari" (IN compensator + OUT nou). In schimb, stergem fizic miscarile
// vechi generate de comanda (DELETE WHERE tip_referinta='comanda' AND
// id_referinta=$comanda->id) si recream miscari noi din liniile curente.
// Asta pastreaza jurnalul de stoc curat si consistent cu starea actuala
// a comenzii.
class MiscariStocService
{
    /**
     * Inregistreaza iesirile de stoc pentru toate liniile unei comenzi.
     * Idempotent: revertaza intai eventualele miscari existente generate
     * de aceasta comanda, apoi insereaza miscari proaspete.
     *
     * Trebuie apelat doar daca comanda are id_depozit setat — altfel
     * miscarile sunt orfane (nu putem urmari soldul).
     */
    public function sincronizeazaIesiriComanda(Comanda $comanda): void
    {
        DB::transaction(function () use ($comanda) {
            $this->revertIesiriComanda($comanda);

            // Daca depozitul nu e setat, nu generam miscari acum — admin il
            // poate completa la editare ulterioara, cand se rechema sync.
            if (! $comanda->id_depozit) {
                return;
            }

            $comanda->loadMissing('produse');

            foreach ($comanda->produse as $linie) {
                if ((int) $linie->cantitate <= 0) {
                    continue;
                }

                Stoc::create([
                    'id_produs' => $linie->id_produs,
                    'id_depozit' => $comanda->id_depozit,
                    'cantitate' => (int) $linie->cantitate,
                    'tip' => Stoc::TIP_OUT,
                    'id_referinta' => $comanda->id,
                    'tip_referinta' => Stoc::REF_COMANDA,
                    'data' => $comanda->data_livrare,
                    'observatii' => null,
                ]);
            }
        });
    }

    /**
     * Sterge fizic toate miscarile generate de o comanda. Folosit la
     * stergerea comenzii sau ca prim pas in sincronizare.
     */
    public function revertIesiriComanda(Comanda $comanda): void
    {
        Stoc::pentruReferinta(Stoc::REF_COMANDA, $comanda->id)->delete();
    }

    /**
     * Acelasi pattern pentru comenzi rapide — `tip_referinta='comanda_rapida'`.
     * Logica e identica: revert + recreate la fiecare salvare; nu pastram
     * istoric de compensari.
     */
    public function sincronizeazaIesiriComandaRapida(ComandaRapida $comanda): void
    {
        DB::transaction(function () use ($comanda) {
            $this->revertIesiriComandaRapida($comanda);

            if (! $comanda->id_depozit) {
                return;
            }

            $comanda->loadMissing('produse');

            foreach ($comanda->produse as $linie) {
                if ((int) $linie->cantitate <= 0) {
                    continue;
                }

                Stoc::create([
                    'id_produs' => $linie->id_produs,
                    'id_depozit' => $comanda->id_depozit,
                    'cantitate' => (int) $linie->cantitate,
                    'tip' => Stoc::TIP_OUT,
                    'id_referinta' => $comanda->id,
                    'tip_referinta' => Stoc::REF_COMANDA_RAPIDA,
                    'data' => $comanda->data_livrare,
                    'observatii' => null,
                ]);
            }
        });
    }

    public function revertIesiriComandaRapida(ComandaRapida $comanda): void
    {
        Stoc::pentruReferinta(Stoc::REF_COMANDA_RAPIDA, $comanda->id)->delete();
    }

    /**
     * Sincronizeaza miscarea de stoc pentru un dozator (cu BIDOANE) dat in
     * custodie sau cumparat.
     *
     * Pattern: revert + recreate. La fiecare apel:
     *   - dozator dezactivat (activ=false): doar revert (bidonul s-a recuperat)
     *   - dozator activ + tranzactie='custodie': miscare CUSTODIE (cantitate=1)
     *   - dozator activ + tranzactie='cumparat': miscare OUT (cantitate=1)
     *
     * Cantitatea = 1 pentru ca un dozator e o unitate fizica unica per
     * inregistrare (NU agregat). Daca admin vrea N dozatoare la aceeasi adresa,
     * creeaza N intrari distincte (cu serii diferite).
     */
    public function sincronizeazaCustodieDozator(Dozator $dozator): void
    {
        DB::transaction(function () use ($dozator) {
            $this->revertCustodieDozator($dozator);

            // Daca e dezactivat sau lipseste depozitul sursa, doar revert.
            if (! $dozator->activ || ! $dozator->id_depozit) {
                return;
            }

            $tip = $dozator->tranzactie === Dozator::TRANZACTIE_CUMPARAT
                ? Stoc::TIP_OUT
                : Stoc::TIP_CUSTODIE;

            Stoc::create([
                'id_produs' => $dozator->id_produs,
                'id_depozit' => $dozator->id_depozit,
                'cantitate' => 1,
                'tip' => $tip,
                'id_referinta' => $dozator->id,
                'tip_referinta' => Stoc::REF_DOZATOR,
                'data' => $dozator->data_instalare,
                'observatii' => $dozator->serie ? 'Serie ' . $dozator->serie : null,
            ]);
        });
    }

    public function revertCustodieDozator(Dozator $dozator): void
    {
        Stoc::pentruReferinta(Stoc::REF_DOZATOR, $dozator->id)->delete();
    }

    /**
     * Mirror al `sincronizeazaCustodieDozator` pentru `DozatorFiltre` (Faza 4.3).
     * NU putem reutiliza acelasi `tip_referinta` — sunt entitati separate cu
     * ID-uri ce pot coincide intre tabele. Folosim `Stoc::REF_DOZATOR_FILTRU`.
     *
     * Reguli:
     *  - status='retras' sau id_depozit lipseste: doar revert
     *  - status='activ' + tranzactie='custodie': miscare CUSTODIE (cantitate=1)
     *  - status='activ' + tranzactie='cumparat': miscare OUT (cantitate=1)
     */
    public function sincronizeazaCustodieDozatorFiltre(DozatorFiltre $dozator): void
    {
        DB::transaction(function () use ($dozator) {
            $this->revertCustodieDozatorFiltre($dozator);

            if (! $dozator->esteActiv() || ! $dozator->id_depozit) {
                return;
            }

            $tip = $dozator->tranzactie === DozatorFiltre::TRANZACTIE_CUMPARAT
                ? Stoc::TIP_OUT
                : Stoc::TIP_CUSTODIE;

            Stoc::create([
                'id_produs' => $dozator->id_produs,
                'id_depozit' => $dozator->id_depozit,
                'cantitate' => 1,
                'tip' => $tip,
                'id_referinta' => $dozator->id,
                'tip_referinta' => Stoc::REF_DOZATOR_FILTRU,
                'data' => $dozator->data_instalare,
                'observatii' => $dozator->serie ? 'Filtru serie ' . $dozator->serie : 'Dozator filtru',
            ]);
        });
    }

    public function revertCustodieDozatorFiltre(DozatorFiltre $dozator): void
    {
        Stoc::pentruReferinta(Stoc::REF_DOZATOR_FILTRU, $dozator->id)->delete();
    }

    /**
     * Faza 5.1 — Sincronizeaza intrarile de stoc IN pentru o factura de
     * cheltuieli. Pattern revert+recreate identic cu Comenzi:
     *  - sterge mişcarile vechi generate de aceasta factura
     *  - insereaza miscari proaspete pentru fiecare linie cu cantitate > 0
     *
     * Sursa pentru `id_depozit` e `cheltuiala.id_depozit` (destinatie achizitie).
     */
    public function sincronizeazaIntrariCheltuiala(Cheltuiala $cheltuiala): void
    {
        DB::transaction(function () use ($cheltuiala) {
            $this->revertIntrariCheltuiala($cheltuiala);

            $cheltuiala->loadMissing('produse');

            foreach ($cheltuiala->produse as $linie) {
                if ((int) $linie->cantitate <= 0) {
                    continue;
                }

                Stoc::create([
                    'id_produs' => $linie->id_produs,
                    'id_depozit' => $cheltuiala->id_depozit,
                    'cantitate' => (int) $linie->cantitate,
                    'tip' => Stoc::TIP_IN,
                    'id_referinta' => $cheltuiala->id,
                    'tip_referinta' => Stoc::REF_CHELTUIALA,
                    'data' => $cheltuiala->data,
                    'observatii' => null,
                ]);
            }
        });
    }

    public function revertIntrariCheltuiala(Cheltuiala $cheltuiala): void
    {
        Stoc::pentruReferinta(Stoc::REF_CHELTUIALA, $cheltuiala->id)->delete();
    }
}
