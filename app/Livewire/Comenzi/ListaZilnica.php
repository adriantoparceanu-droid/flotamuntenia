<?php

namespace App\Livewire\Comenzi;

use App\Models\Car;
use App\Models\Comanda;
use App\Models\ComandaRapida;
use App\Models\Deposit;
use App\Models\Problema;
use App\Services\MiscariStocService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class ListaZilnica extends Component
{
    public const TIP_COMANDA = 'comanda';
    public const TIP_RAPIDA = 'comanda_rapida';
    public const TIP_PROBLEMA = 'problema';

    #[Url(as: 'data')]
    public string $data = '';

    // Filtru masina: '' = toate, '0' = doar neasignate, 'X' = masina X
    #[Url(as: 'masina')]
    public string $filtruMasina = '';

    #[Url(as: 'depozit')]
    public ?int $idDepozit = null;

    /**
     * Alocari pendente per comanda clasica: [id => id_masina|null].
     * Populat din DB la prima randare; modificarile raman locale pana la "Salveaza alocarile".
     */
    public array $alocariClasice = [];

    /** Alocari pendente per comanda rapida: [id => id_masina|null]. */
    public array $alocariRapide = [];

    /** Alocari pendente per problema: [id => id_masina|null]. */
    public array $alocariProbleme = [];

    // Modal stergere
    public bool $modalStergere = false;
    public ?string $tipDeStergere = null;
    public ?int $idDeStergere = null;
    public string $denumireDeStergere = '';

    public function mount(): void
    {
        if ($this->data === '') {
            $this->data = now()->toDateString();
        }
    }

    public function navigheazaZi(int $delta): void
    {
        try {
            $this->data = Carbon::parse($this->data)->addDays($delta)->toDateString();
        } catch (\Throwable $e) {
            $this->data = now()->toDateString();
        }
        // Resetam alocarile pendente la schimbarea zilei (alta lista)
        $this->alocariClasice = [];
        $this->alocariRapide = [];
        $this->alocariProbleme = [];
    }

    public function setAzi(): void
    {
        $this->data = now()->toDateString();
        $this->alocariClasice = [];
        $this->alocariRapide = [];
        $this->alocariProbleme = [];
    }

    public function updatedData(): void
    {
        $this->alocariClasice = [];
        $this->alocariRapide = [];
        $this->alocariProbleme = [];
    }

    /**
     * Persista in DB toate alocarile schimbate fata de starea curenta.
     */
    public function salveazaAlocari(): void
    {
        $contor = 0;

        foreach ($this->alocariClasice as $id => $valoare) {
            $c = Comanda::find($id);
            if (! $c) {
                continue;
            }
            $nou = ($valoare === '' || $valoare === null) ? null : (int) $valoare;
            if ($c->id_masina !== $nou) {
                $c->id_masina = $nou;
                $c->save();
                $contor++;
            }
        }

        foreach ($this->alocariRapide as $id => $valoare) {
            $c = ComandaRapida::find($id);
            if (! $c) {
                continue;
            }
            $nou = ($valoare === '' || $valoare === null) ? null : (int) $valoare;
            if ($c->id_masina !== $nou) {
                $c->id_masina = $nou;
                $c->save();
                $contor++;
            }
        }

        foreach ($this->alocariProbleme as $id => $valoare) {
            $p = Problema::find($id);
            if (! $p) {
                continue;
            }
            $nou = ($valoare === '' || $valoare === null) ? null : (int) $valoare;
            if ($p->id_masina !== $nou) {
                $p->id_masina = $nou;
                $p->save();
                $contor++;
            }
        }

        // Re-sincronizam din DB la urmatorul render
        $this->alocariClasice = [];
        $this->alocariRapide = [];
        $this->alocariProbleme = [];

        session()->flash('mesaj', $contor === 0
            ? 'Nicio modificare de salvat.'
            : ($contor === 1 ? 'O alocare salvata.' : "$contor alocari salvate."));
    }

    /**
     * Asculta evenimentul dispatch-uit din JS atunci cand userul schimba
     * dropdown-ul de masina direct din popup-ul pinului de pe harta.
     * Actualizeaza overlay-ul exact ca dropdown-ul de pe rand (wire:model.live)
     * — render-ul recalculeaza puncteHarta + sumarPerMasina, harta se redeseneaza.
     *
     * @param  string  $tip  'comanda' sau 'comanda_rapida'
     * @param  int  $id  id-ul comenzii
     * @param  string|null  $valoare  '' sau '0' pentru nealocata, altfel id_masina
     */
    #[On('aloca-masina-harta')]
    public function alocaDinHarta(string $tip, int $id, ?string $valoare): void
    {
        $val = ($valoare === '' || $valoare === null) ? null : (int) $valoare;

        if ($tip === self::TIP_RAPIDA) {
            $this->alocariRapide[$id] = $val;
        } elseif ($tip === self::TIP_PROBLEMA) {
            $this->alocariProbleme[$id] = $val;
        } else {
            $this->alocariClasice[$id] = $val;
        }
    }

    public function comutaLivrat(string $tip, int $id): void
    {
        $c = $this->gaseste($tip, $id);
        if (! $c) {
            return;
        }
        $c->livrat = ! $c->livrat;
        $c->save();
    }

    public function comutaAchitat(string $tip, int $id): void
    {
        $c = $this->gaseste($tip, $id);
        if (! $c) {
            return;
        }
        $c->achitat = ! $c->achitat;
        $c->save();
    }

    // ===== Stergere =====

    public function deschideModalStergere(string $tip, int $id): void
    {
        $c = $this->gaseste($tip, $id);
        if (! $c) {
            return;
        }
        if ($c->livrat) {
            session()->flash('eroare', 'Nu poti sterge o comanda deja livrata.');
            return;
        }

        $titlu = match ($tip) {
            self::TIP_RAPIDA => ($c->denumire ?? 'Rapida'),
            self::TIP_PROBLEMA => ($c->client?->denumire ?? 'Problema'),
            default => ($c->client?->denumire ?? '?'),
        };

        $this->tipDeStergere = $tip;
        $this->idDeStergere = $id;
        $this->denumireDeStergere = '#' . $id . ' — ' . $titlu;
        $this->modalStergere = true;
    }

    public function inchideModalStergere(): void
    {
        $this->modalStergere = false;
        $this->tipDeStergere = null;
        $this->idDeStergere = null;
        $this->denumireDeStergere = '';
    }

    public function confirmaStergere(MiscariStocService $stocService): void
    {
        if (! $this->idDeStergere || ! $this->tipDeStergere) {
            return;
        }

        $c = $this->gaseste($this->tipDeStergere, $this->idDeStergere);
        if (! $c) {
            $this->inchideModalStergere();
            return;
        }
        if ($c->livrat) {
            session()->flash('eroare', 'Nu poti sterge o comanda deja livrata.');
            $this->inchideModalStergere();
            return;
        }

        if ($this->tipDeStergere === self::TIP_RAPIDA) {
            $stocService->revertIesiriComandaRapida($c);
        } elseif ($this->tipDeStergere === self::TIP_PROBLEMA) {
            // Problemele NU au miscari de stoc — doar DELETE direct.
        } else {
            $stocService->revertIesiriComanda($c);
        }
        $c->delete();

        $this->inchideModalStergere();
        session()->flash('mesaj', 'Comanda stearsa.');
    }

    private function gaseste(string $tip, int $id)
    {
        return match ($tip) {
            self::TIP_RAPIDA => ComandaRapida::find($id),
            self::TIP_PROBLEMA => Problema::find($id),
            default => Comanda::find($id),
        };
    }

    public function render()
    {
        $masini = Car::where('activ', true)->orderBy('denumire')->get();
        $depozite = Deposit::where('activ', true)->orderBy('denumire')->get();

        $filtru = $this->filtruMasina;
        $aplicaFiltruMasina = function ($q) use ($filtru) {
            if ($filtru === '') {
                return $q;
            }
            if ($filtru === '0') {
                return $q->whereNull('id_masina');
            }
            return $q->where('id_masina', (int) $filtru);
        };

        $qClasice = Comanda::query()
            ->with(['client', 'adresa', 'depozit', 'produse.produs'])
            ->whereDate('data_livrare', $this->data)
            ->whereNull('status'); // ascundem 'In asteptare' (portal client)

        $qRapide = ComandaRapida::query()
            ->with(['depozit', 'produse.produs'])
            ->whereDate('data_livrare', $this->data);

        $qProbleme = Problema::query()
            ->with(['client', 'adresa', 'depozit'])
            ->whereDate('data_livrare', $this->data);

        if ($this->idDepozit) {
            $qClasice->where('id_depozit', $this->idDepozit);
            $qRapide->where('id_depozit', $this->idDepozit);
            $qProbleme->where('id_depozit', $this->idDepozit);
        }

        $qClasice = $aplicaFiltruMasina($qClasice);
        $qRapide = $aplicaFiltruMasina($qRapide);
        $qProbleme = $aplicaFiltruMasina($qProbleme);

        $clasice = $qClasice->orderBy('ordine_traseu')->orderBy('id')->get();
        $rapide = $qRapide->orderBy('ordine_traseu')->orderBy('id')->get();
        $probleme = $qProbleme->orderBy('ordine_traseu')->orderBy('id')->get();

        $itemi = collect();

        // Helper: rezolva valoarea efectiva pentru id_masina, tinand cont de alocarile pendente
        $rezolvaMasinaEfectiv = function ($id, ?int $idMasinaDb, array $overlay) {
            if (! array_key_exists($id, $overlay)) {
                return $idMasinaDb;
            }
            $val = $overlay[$id];
            return ($val === '' || $val === null) ? null : (int) $val;
        };

        foreach ($clasice as $c) {
            $descriere = $c->produse->map(function ($l) {
                $denumire = $l->produs?->denumire ?? '?';
                return ((int) $l->cantitate) . 'x ' . $denumire;
            })->implode(', ');

            $idMasinaEfectiv = $rezolvaMasinaEfectiv($c->id, $c->id_masina, $this->alocariClasice);

            $itemi->push([
                'tip' => self::TIP_COMANDA,
                'id' => $c->id,
                'id_masina' => $idMasinaEfectiv,
                'id_masina_db' => $c->id_masina,
                'ordine_traseu' => (int) $c->ordine_traseu,
                'tip_cod' => $c->tip_comanda,
                'tip_comanda_label' => $c->etichetaTip(),
                'titlu' => $c->client?->denumire ?? ($c->nume ?: '?'),
                'subtitlu' => $c->adresa?->denumire ?? '',
                'adresa_completa' => $c->adresa?->adresaCompleta() ?: '',
                'descriere_produse' => $descriere ?: '—',
                'total' => $c->total(),
                'mod_plata_cod' => (int) $c->id_modalitate_plata,
                'mod_plata_label' => $c->etichetaModPlata(),
                'mod_plata_short' => $this->modPlataShort((int) $c->id_modalitate_plata),
                'nr19l' => (int) $c->nr_recipienti,
                'nr11l' => (int) $c->nr_pahare,
                'livrat' => (bool) $c->livrat,
                'achitat' => (bool) $c->achitat,
                'ruta_editare' => route('comenzi.editare', $c),
                'ruta_client' => $c->id_client ? route('clienti.detalii', $c->id_client) : null,
                'lat' => $c->adresa?->lat,
                'lng' => $c->adresa?->lng,
            ]);
        }

        foreach ($rapide as $c) {
            $descriere = $c->produse->map(function ($l) {
                $denumire = $l->produs?->denumire ?? '?';
                return ((int) $l->cantitate) . 'x ' . $denumire;
            })->implode(', ');

            $idMasinaEfectiv = $rezolvaMasinaEfectiv($c->id, $c->id_masina, $this->alocariRapide);

            $itemi->push([
                'tip' => self::TIP_RAPIDA,
                'id' => $c->id,
                'id_masina' => $idMasinaEfectiv,
                'id_masina_db' => $c->id_masina,
                'ordine_traseu' => (int) $c->ordine_traseu,
                'tip_cod' => 'rapida',
                'tip_comanda_label' => 'Rapida',
                'titlu' => $c->denumire,
                'subtitlu' => $c->adresa ?: '',
                'adresa_completa' => $c->adresa ?: '',
                'descriere_produse' => $descriere ?: '—',
                'total' => $c->total(),
                'mod_plata_cod' => Comanda::MODPLATA_CASH,
                'mod_plata_label' => 'Cash',
                'mod_plata_short' => 'Cash',
                'nr19l' => (int) $c->produse->where('id_produs', 45)->sum('cantitate'),
                'nr11l' => (int) $c->produse->where('id_produs', 46)->sum('cantitate'),
                'livrat' => (bool) $c->livrat,
                'achitat' => (bool) $c->achitat,
                'ruta_editare' => route('comenzi-rapide.editare', $c),
                'ruta_client' => null,
                'lat' => $c->lat,
                'lng' => $c->lng,
            ]);
        }

        foreach ($probleme as $p) {
            $idMasinaEfectiv = $rezolvaMasinaEfectiv($p->id, $p->id_masina, $this->alocariProbleme);

            $itemi->push([
                'tip' => self::TIP_PROBLEMA,
                'id' => $p->id,
                'id_masina' => $idMasinaEfectiv,
                'id_masina_db' => $p->id_masina,
                'ordine_traseu' => (int) $p->ordine_traseu,
                'tip_cod' => 'problema',
                'tip_comanda_label' => 'Problema',
                'titlu' => $p->client?->denumire ?? ($p->nume ?: '?'),
                'subtitlu' => $p->adresa?->denumire ?? '',
                'adresa_completa' => $p->adresa?->adresaCompleta() ?: '',
                'descriere_produse' => $p->descriere ?: '—',
                'total' => $p->total(),
                'mod_plata_cod' => (int) $p->id_modalitate_plata,
                'mod_plata_label' => $p->etichetaModPlata(),
                'mod_plata_short' => $this->modPlataShort((int) $p->id_modalitate_plata),
                'nr19l' => 0, // problemele sunt servicii — fara cantitati de bidoane
                'nr11l' => 0,
                'livrat' => (bool) $p->livrat,
                'achitat' => (bool) $p->achitat,
                'ruta_editare' => route('probleme.editare', $p),
                'ruta_client' => $p->id_client ? route('clienti.detalii', $p->id_client) : null,
                'lat' => $p->adresa?->lat,
                'lng' => $p->adresa?->lng,
            ]);
        }

        // Sortare automata: ordine_traseu (0/null la final), apoi id
        $itemi = $itemi->sortBy(fn ($i) => [$i['ordine_traseu'] ?: 999999, $i['id']])->values();

        // Initializam alocari pentru orice item nou (fara a suprascrie modificari pendente)
        foreach ($itemi as $i) {
            if ($i['tip'] === self::TIP_COMANDA) {
                if (! array_key_exists($i['id'], $this->alocariClasice)) {
                    $this->alocariClasice[$i['id']] = $i['id_masina_db'];
                }
            } elseif ($i['tip'] === self::TIP_RAPIDA) {
                if (! array_key_exists($i['id'], $this->alocariRapide)) {
                    $this->alocariRapide[$i['id']] = $i['id_masina_db'];
                }
            } elseif ($i['tip'] === self::TIP_PROBLEMA) {
                if (! array_key_exists($i['id'], $this->alocariProbleme)) {
                    $this->alocariProbleme[$i['id']] = $i['id_masina_db'];
                }
            }
        }

        // Sumar global pe modalitate de plata (toate comenzile)
        $totalPePlata = [
            Comanda::MODPLATA_CASH => 0.0,
            Comanda::MODPLATA_OP => 0.0,
            Comanda::MODPLATA_CARD => 0.0,
            Comanda::MODPLATA_ALTA => 0.0,
        ];
        foreach ($itemi as $i) {
            $cod = $i['mod_plata_cod'] ?: Comanda::MODPLATA_CASH;
            $totalPePlata[$cod] = ($totalPePlata[$cod] ?? 0) + (float) $i['total'];
        }
        $totalGlobal = array_sum($totalPePlata);

        // Sumar achitat per masina (doar comenzi cu achitat=true)
        // Format: [idMasina|0 => ['nume' => ..., 'cash' => x, 'op' => x, 'card' => x, 'alta' => x, 'total' => x]]
        $sumarPerMasina = [];
        foreach ($masini as $m) {
            $sumarPerMasina[$m->id] = [
                'nume' => $m->denumire,
                'nr_inmatriculare' => $m->nr_inmatriculare,
                'culoare' => $m->culoare ?: '#3b82f6',
                'cash' => 0.0,
                'op' => 0.0,
                'card' => 0.0,
                'alta' => 0.0,
                'total' => 0.0,
                'nr_comenzi' => 0,
                'nr_livrate' => 0,
            ];
        }
        $sumarPerMasina[0] = [
            'nume' => 'Nealocate',
            'nr_inmatriculare' => null,
            'culoare' => '#9ca3af',
            'cash' => 0.0,
            'op' => 0.0,
            'card' => 0.0,
            'alta' => 0.0,
            'total' => 0.0,
            'nr_comenzi' => 0,
            'nr_livrate' => 0,
        ];

        foreach ($itemi as $i) {
            $cheie = $i['id_masina'] ?: 0;
            if (! isset($sumarPerMasina[$cheie])) {
                continue;
            }
            $sumarPerMasina[$cheie]['nr_comenzi']++;
            if ($i['livrat']) {
                $sumarPerMasina[$cheie]['nr_livrate']++;
            }
            if ($i['achitat']) {
                $col = match ($i['mod_plata_cod']) {
                    Comanda::MODPLATA_OP => 'op',
                    Comanda::MODPLATA_CARD => 'card',
                    Comanda::MODPLATA_ALTA => 'alta',
                    default => 'cash',
                };
                $sumarPerMasina[$cheie][$col] += (float) $i['total'];
                $sumarPerMasina[$cheie]['total'] += (float) $i['total'];
            }
        }

        // Pastram doar masinile cu cel putin 1 comanda + Nealocate daca au comenzi
        $sumarPerMasina = array_filter($sumarPerMasina, fn ($s) => $s['nr_comenzi'] > 0);

        return view('livewire.comenzi.lista-zilnica', [
            'itemi' => $itemi,
            'masini' => $masini,
            'depozite' => $depozite,
            'totalItemi' => $itemi->count(),
            'apiKey' => config('services.google_maps.key'),
            'totalPePlata' => $totalPePlata,
            'totalGlobal' => $totalGlobal,
            'sumarPerMasina' => $sumarPerMasina,
        ]);
    }

    private function modPlataShort(int $cod): string
    {
        return match ($cod) {
            Comanda::MODPLATA_OP => 'OP',
            Comanda::MODPLATA_CARD => 'Card',
            Comanda::MODPLATA_ALTA => 'Alta',
            default => 'Cash',
        };
    }
}
