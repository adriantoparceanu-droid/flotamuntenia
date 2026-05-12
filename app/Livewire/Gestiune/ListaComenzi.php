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
        $masini  = Car::where('activ', true)->orderBy('denumire')->get();
        $depozite = Deposit::where('activ', true)->orderBy('denumire')->get();

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
            ->with(['client', 'adresa', 'produse.produs', 'masina'])
            ->vizibile()
            ->whereDate('data_livrare', $this->data);

        $qRapide = ComandaRapida::query()
            ->with(['produse.produs'])
            ->whereDate('data_livrare', $this->data);

        $qProbleme = Problema::query()
            ->with(['client', 'adresa'])
            ->whereDate('data_livrare', $this->data);

        if ($this->idDepozit) {
            $qClasice->where('id_depozit', $this->idDepozit);
            $qRapide->where('id_depozit', $this->idDepozit);
            $qProbleme->where('id_depozit', $this->idDepozit);
        }

        $clasice  = $aplicaFiltruMasina($qClasice)->orderBy('ordine_traseu')->orderBy('id')->get();
        $rapide   = $aplicaFiltruMasina($qRapide)->orderBy('ordine_traseu')->orderBy('id')->get();
        $probleme = $aplicaFiltruMasina($qProbleme)->orderBy('ordine_traseu')->orderBy('id')->get();

        // ---- Tabel 1: comenzi cu observatii ----
        $cuObservatii = collect();

        foreach ($clasice as $c) {
            if (filled($c->observatii)) {
                $cuObservatii->push([
                    'tip'      => $c->etichetaTip(),
                    'tip_cod'  => $c->tip_comanda,
                    'nume'     => $c->client?->denumire ?? ($c->nume ?: '?'),
                    'adresa'   => $c->adresa?->adresaCompleta() ?: '',
                    'obs'      => $c->observatii,
                    'masina'   => $c->masina?->denumire ?? '—',
                    'ordine'   => (int) $c->ordine_traseu,
                ]);
            }
        }

        foreach ($rapide as $c) {
            if (filled($c->observatii)) {
                $cuObservatii->push([
                    'tip'     => 'Rapida',
                    'tip_cod' => 'rapida',
                    'nume'    => $c->denumire,
                    'adresa'  => $c->adresa ?: '',
                    'obs'     => $c->observatii,
                    'masina'  => '—',
                    'ordine'  => (int) $c->ordine_traseu,
                ]);
            }
        }

        foreach ($probleme as $p) {
            if (filled($p->observatii)) {
                $cuObservatii->push([
                    'tip'     => 'Problema',
                    'tip_cod' => 'problema',
                    'nume'    => $p->client?->denumire ?? ($p->nume ?: '?'),
                    'adresa'  => $p->adresa?->adresaCompleta() ?: '',
                    'obs'     => $p->observatii,
                    'masina'  => '—',
                    'ordine'  => (int) $p->ordine_traseu,
                ]);
            }
        }

        $cuObservatii = $cuObservatii->sortBy(fn ($i) => [$i['ordine'] ?: 999999])->values();

        // ---- Tabel 2: sumar produse ----
        // Agregam cantitati per denumire produs din comenzi clasice + rapide
        $sumarProduse = collect();

        foreach ($clasice as $c) {
            foreach ($c->produse as $linie) {
                $denumire = $linie->produs?->denumire ?? 'Produs necunoscut';
                $cantitate = (int) $linie->cantitate;
                if ($cantitate > 0) {
                    $sumarProduse->push(['denumire' => $denumire, 'cantitate' => $cantitate]);
                }
            }
        }

        foreach ($rapide as $c) {
            foreach ($c->produse as $linie) {
                $denumire = $linie->produs?->denumire ?? 'Produs necunoscut';
                $cantitate = (int) $linie->cantitate;
                if ($cantitate > 0) {
                    $sumarProduse->push(['denumire' => $denumire, 'cantitate' => $cantitate]);
                }
            }
        }

        $sumarProduse = $sumarProduse
            ->groupBy('denumire')
            ->map(fn ($grup, $denumire) => [
                'denumire'  => $denumire,
                'cantitate' => $grup->sum('cantitate'),
            ])
            ->sortByDesc('cantitate')
            ->values();

        $totalComenzi = $clasice->count() + $rapide->count() + $probleme->count();

        return view('livewire.gestiune.lista-comenzi', [
            'masini'        => $masini,
            'depozite'      => $depozite,
            'cuObservatii'  => $cuObservatii,
            'sumarProduse'  => $sumarProduse,
            'totalComenzi'  => $totalComenzi,
        ]);
    }
}
