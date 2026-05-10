<?php

namespace App\Livewire\Rapoarte;

use App\Models\Comanda;
use App\Models\CostCategory;
use App\Models\Produs;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Faza 5.4 — Raport abonamente lipsă (vezi DOCUMENTATION.md §4.5).
 *
 * Detecteaza pentru fiecare adresa cu abonament (bidoane SAU filtre) lunile
 * fara comanda de abonament cu `luna_livrata` setata. Iterare luna cu luna
 * de la prima `luna_livrata` din istoric pana la luna selectata (inclusiv).
 *
 * Acopera ambele tipuri de abonament:
 *   - Produs::TIP_ABONAMENT (1) = abonament bidoane (livrare fizica 19L/11L)
 *   - Produs::TIP_FILTRE    (2) = abonament dozator filtre (doar facturare)
 *
 * Definitia „lipsa" (validata cu user-ul): nu exista comanda cu
 * `tip_comanda='abonament'` si `luna_livrata=YYYY/MM` pentru acea adresa.
 * NU verificam `livrat=1` — daca admin a creat comanda dar n-a livrat-o,
 * o consideram acoperita (apare in alt raport viitor de comenzi nelivrate).
 *
 * Filtru: o luna selectata (default = luna curenta). Raportul afiseaza pentru
 * fiecare adresa cu lipsuri TOATE lunile lipsa de la prima `luna_livrata`
 * pana la luna selectata.
 */
#[Layout('layouts.app')]
class AbonamenteLipsa extends Component
{
    /**
     * Format luna selectata: `YYYY-MM` (input HTML5 `month`).
     * Stocata intern asa pentru compat cu input-ul; convertita la `YYYY/MM`
     * (formatul `comenzi.luna_livrata`) la query.
     */
    #[Url(as: 'luna')]
    public string $lunaSelectata = '';

    public function mount(): void
    {
        if ($this->lunaSelectata === '') {
            $this->lunaSelectata = now()->format('Y-m');
        }
    }

    public function reseteazaFiltre(): void
    {
        $this->lunaSelectata = now()->format('Y-m');
    }

    /**
     * Converteste `YYYY-MM` (input UI) la `YYYY/MM` (format DB).
     */
    private function lunaDb(string $yyyymm): string
    {
        return str_replace('-', '/', $yyyymm);
    }

    /**
     * Itereaza luna cu luna de la $start la $end (inclusiv) si returneaza
     * lista lunilor in format `YYYY/MM`.
     */
    private function genereazaLuni(string $startYyyymm, string $endYyyymm): array
    {
        $start = Carbon::createFromFormat('Y/m-d', $startYyyymm . '-01');
        $end = Carbon::createFromFormat('Y/m-d', $endYyyymm . '-01');
        if ($start->gt($end)) {
            return [];
        }
        $luni = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $luni[] = $cursor->format('Y/m');
            $cursor->addMonth();
        }
        return $luni;
    }

    /**
     * Eticheta romana pentru luna `2026/05` -> `Mai 2026`.
     */
    public function formatLuna(string $yyyymmWithSlash): string
    {
        try {
            $c = Carbon::createFromFormat('Y/m-d', $yyyymmWithSlash . '-01');
        } catch (\Throwable $e) {
            return $yyyymmWithSlash;
        }
        $luniRo = [
            1 => 'Ian', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mai', 6 => 'Iun', 7 => 'Iul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Noi', 12 => 'Dec',
        ];
        return $luniRo[(int) $c->format('n')] . ' ' . $c->format('Y');
    }

    /**
     * Eticheta tip abonament (Bidoane / Filtre) pe baza valorii `produs.abonament`.
     */
    public function etichetaTipAbonament(int $tip): string
    {
        return match ($tip) {
            Produs::TIP_FILTRE => 'Filtre',
            Produs::TIP_ABONAMENT => 'Bidoane',
            default => 'N/A',
        };
    }

    public function culoareTipAbonament(int $tip): string
    {
        return match ($tip) {
            Produs::TIP_FILTRE => 'bg-amber-100 text-amber-700',
            Produs::TIP_ABONAMENT => 'bg-sky-100 text-sky-700',
            default => 'bg-gray-100 text-gray-500',
        };
    }

    public function render()
    {
        $lunaDbSelectata = $this->lunaDb($this->lunaSelectata);

        // Toate adresele cu abonament (bidoane + filtre)
        $produseAbonament = Produs::query()
            ->with(['adresa.client', 'adresa'])
            ->whereIn('abonament', [Produs::TIP_ABONAMENT, Produs::TIP_FILTRE])
            ->get();

        $idAdrese = $produseAbonament->pluck('id_adresa')->all();

        // Distinct (id_adresa, luna_livrata) pentru tip_comanda='abonament'
        $istoricLuni = Comanda::query()
            ->whereIn('id_adresa', $idAdrese)
            ->where('tip_comanda', Comanda::TIP_ABONAMENT)
            ->whereNotNull('luna_livrata')
            ->where('luna_livrata', '!=', '')
            ->select('id_adresa', 'luna_livrata')
            ->distinct()
            ->get()
            ->groupBy('id_adresa')
            ->map(fn ($g) => $g->pluck('luna_livrata')->all());

        $randuri = [];
        $totalLipsuri = 0;

        foreach ($produseAbonament as $produs) {
            $adresa = $produs->adresa;
            if (! $adresa) {
                continue; // adresa stearsa orfana — skip
            }
            $idAdresa = $adresa->id;
            $luniExistente = $istoricLuni->get($idAdresa, []);

            // Daca adresa nu are nicio comanda cu luna_livrata, nu avem
            // punct de start — skip (nu o raportam ca lipsa retroactiv).
            if (empty($luniExistente)) {
                continue;
            }

            // Luna minima din istoric (cea mai veche luna_livrata)
            sort($luniExistente);
            $primaLuna = $luniExistente[0];

            // Daca filtrul selectat e inainte de prima luna, nu avem nimic
            if ($lunaDbSelectata < $primaLuna) {
                continue;
            }

            // Iterez de la prima luna pana la luna selectata
            $toateLuniIntervale = $this->genereazaLuni($primaLuna, $lunaDbSelectata);
            $lipsuri = array_values(array_diff($toateLuniIntervale, $luniExistente));

            if (empty($lipsuri)) {
                continue; // adresa OK pe perioada — nu o afisam
            }

            $randuri[] = [
                'produs' => $produs,
                'adresa' => $adresa,
                'client' => $adresa->client,
                'tipAbonament' => (int) $produs->abonament,
                'primaLuna' => $primaLuna,
                'lipsuri' => $lipsuri,
                'numarLipsuri' => count($lipsuri),
            ];
            $totalLipsuri += count($lipsuri);
        }

        // Sortare: cele cu cele mai multe lipsuri primele
        usort($randuri, fn ($a, $b) => $b['numarLipsuri'] <=> $a['numarLipsuri']);

        return view('livewire.rapoarte.abonamente-lipsa', [
            'randuri' => $randuri,
            'totalLipsuri' => $totalLipsuri,
            'totalAdrese' => count($randuri),
        ]);
    }
}
