<?php

namespace App\Livewire\Gestiune;

use App\Models\Car;
use App\Models\Comanda;
use App\Models\ComandaRapida;
use App\Models\Deposit;
use App\Models\Problema;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class ListaComenzi extends Component
{
    #[Url(as: 'data')]
    public string $data = '';

    #[Url(as: 'masina')]
    public string $filtruMasina = '';

    #[Url(as: 'depozit')]
    public ?int $idDepozit = null;

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
    }

    public function render()
    {
        $masini   = Car::where('activ', true)->orderBy('denumire')->get();
        $depozite = Deposit::where('activ', true)->orderBy('denumire')->get();

        // Label afisat in headerul casetei Observatii
        if ($this->filtruMasina === '') {
            $labelFiltruMasina = 'Toate masinile';
        } elseif ($this->filtruMasina === '0') {
            $labelFiltruMasina = 'Nealocate';
        } else {
            $labelFiltruMasina = $masini->find((int) $this->filtruMasina)?->denumire ?? 'Masina selectata';
        }

        $aplicaFiltruMasina = function ($q) {
            if ($this->filtruMasina === '') {
                return $q;
            }
            if ($this->filtruMasina === '0') {
                return $q->whereNull('id_masina');
            }
            return $q->where('id_masina', (int) $this->filtruMasina);
        };

        $qClasice = Comanda::query()
            ->with(['client', 'masina', 'produse.produs'])
            ->vizibile()
            ->whereDate('data_livrare', $this->data);

        $qRapide = ComandaRapida::query()
            ->with(['produse.produs'])
            ->whereDate('data_livrare', $this->data);

        $qProbleme = Problema::query()
            ->with(['client'])
            ->whereDate('data_livrare', $this->data);

        if ($this->idDepozit) {
            $qClasice->where('id_depozit', $this->idDepozit);
            $qRapide->where('id_depozit', $this->idDepozit);
            $qProbleme->where('id_depozit', $this->idDepozit);
        }

        $clasice  = $aplicaFiltruMasina($qClasice)->orderBy('ordine_traseu')->orderBy('id')->get();
        $rapide   = $aplicaFiltruMasina($qRapide)->orderBy('ordine_traseu')->orderBy('id')->get();
        $probleme = $aplicaFiltruMasina($qProbleme)->orderBy('ordine_traseu')->orderBy('id')->get();

        // ---- Tabel Observatii (doar Nume + Observatii) ----
        $cuObservatii = collect();

        foreach ($clasice as $c) {
            if (filled($c->observatii)) {
                $cuObservatii->push([
                    'nume'   => $c->client?->denumire ?? ($c->nume ?: '?'),
                    'obs'    => $c->observatii,
                    'ordine' => (int) $c->ordine_traseu,
                ]);
            }
        }

        foreach ($rapide as $c) {
            if (filled($c->observatii)) {
                $cuObservatii->push([
                    'nume'   => $c->denumire,
                    'obs'    => $c->observatii,
                    'ordine' => (int) $c->ordine_traseu,
                ]);
            }
        }

        foreach ($probleme as $p) {
            if (filled($p->observatii)) {
                $cuObservatii->push([
                    'nume'   => $p->client?->denumire ?? ($p->nume ?: '?'),
                    'obs'    => $p->observatii,
                    'ordine' => (int) $p->ordine_traseu,
                ]);
            }
        }

        $cuObservatii = $cuObservatii->sortBy(fn ($i) => $i['ordine'] ?: 999999)->values();

        // ---- Sumar produse: total + per masina ----
        // [masina_key => ['key'=>'masina_0', 'denumire'=>'Iveco 01', 'culoare'=>'#...', 'produse'=>[...], 'total'=>N]]
        $produseTotale = [];   // [denumire => cantitate]
        $produsePerMasina = []; // [masina_key => ['denumire'=>..., 'culoare'=>..., 'produse'=>[...]]]

        $rezolvaMasina = function (Comanda|ComandaRapida $c) use ($masini): array {
            $idM = $c->id_masina;
            if (! $idM) {
                return ['key' => 'nealocate', 'denumire' => 'Nealocate', 'culoare' => '#9ca3af'];
            }
            $car = $masini->find($idM);
            return [
                'key'      => 'masina_' . $idM,
                'denumire' => $car?->denumire ?? 'Masina #' . $idM,
                'culoare'  => $car?->culoare ?: '#3b82f6',
            ];
        };

        $adaugaProdus = function (string $masinaKey, string $masinaDenumire, string $masinaCuloare, string $denumire, int $cantitate) use (&$produseTotale, &$produsePerMasina): void {
            if ($cantitate <= 0) {
                return;
            }
            $produseTotale[$denumire] = ($produseTotale[$denumire] ?? 0) + $cantitate;

            if (! isset($produsePerMasina[$masinaKey])) {
                $produsePerMasina[$masinaKey] = [
                    'key'      => $masinaKey,
                    'denumire' => $masinaDenumire,
                    'culoare'  => $masinaCuloare,
                    'produse'  => [],
                ];
            }
            $produsePerMasina[$masinaKey]['produse'][$denumire] = ($produsePerMasina[$masinaKey]['produse'][$denumire] ?? 0) + $cantitate;
        };

        foreach ($clasice as $c) {
            $m = $rezolvaMasina($c);
            foreach ($c->produse as $linie) {
                $adaugaProdus($m['key'], $m['denumire'], $m['culoare'], $linie->produs?->denumire ?? 'Produs necunoscut', (int) $linie->cantitate);
            }
        }

        foreach ($rapide as $c) {
            $m = $rezolvaMasina($c);
            foreach ($c->produse as $linie) {
                $adaugaProdus($m['key'], $m['denumire'], $m['culoare'], $linie->produs?->denumire ?? 'Produs necunoscut', (int) $linie->cantitate);
            }
        }

        // Sorteaza produsele in fiecare masina dupa cantitate desc
        foreach ($produsePerMasina as &$grup) {
            arsort($grup['produse']);
            $grup['total'] = array_sum($grup['produse']);
        }
        unset($grup);

        // Sorteaza total desc
        arsort($produseTotale);

        $totalComenzi = $clasice->count() + $rapide->count() + $probleme->count();

        return view('livewire.gestiune.lista-comenzi', [
            'masini'            => $masini,
            'depozite'          => $depozite,
            'cuObservatii'      => $cuObservatii,
            'labelFiltruMasina' => $labelFiltruMasina,
            'produseTotale'     => $produseTotale,
            'produsePerMasina'  => array_values($produsePerMasina),
            'totalComenzi'      => $totalComenzi,
        ]);
    }
}
