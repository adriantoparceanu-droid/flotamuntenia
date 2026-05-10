<?php

namespace App\Livewire\Cheltuieli;

use App\Models\Cheltuiala;
use App\Models\Deposit;
use App\Services\MiscariStocService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $cautare = '';

    #[Url(as: 'depozit')]
    public ?int $filtruDepozit = null;

    // 'toate' | 'achitat' | 'neachitat'
    #[Url(as: 'achitat')]
    public string $filtruAchitat = 'toate';

    // Default: luna curenta (acelasi pattern ca CI3 — admin filtreaza de obicei
    // cheltuielile lunii in curs)
    #[Url(as: 'de_la')]
    public string $deLa = '';

    #[Url(as: 'pana_la')]
    public string $panaLa = '';

    // ===== Modal stergere =====
    public bool $modalStergere = false;
    public ?int $idDeSters = null;
    public string $denumireDeSters = '';

    public function mount(): void
    {
        if ($this->deLa === '') {
            $this->deLa = now()->startOfMonth()->toDateString();
        }
        if ($this->panaLa === '') {
            $this->panaLa = now()->endOfMonth()->toDateString();
        }
    }

    public function updating($prop): void
    {
        if (in_array($prop, ['cautare', 'filtruDepozit', 'filtruAchitat', 'deLa', 'panaLa'], true)) {
            $this->resetPage();
        }
    }

    public function reseteazaFiltre(): void
    {
        $this->cautare = '';
        $this->filtruDepozit = null;
        $this->filtruAchitat = 'toate';
        $this->deLa = now()->startOfMonth()->toDateString();
        $this->panaLa = now()->endOfMonth()->toDateString();
        $this->resetPage();
    }

    public function comutaAchitat(int $id): void
    {
        $c = Cheltuiala::find($id);
        if (! $c) {
            return;
        }
        $c->achitat = ! $c->achitat;
        $c->save();
        session()->flash('mesaj', $c->achitat ? 'Factura marcata ca achitata.' : 'Factura marcata ca neachitata.');
    }

    // ===== Stergere =====

    public function deschideModalStergere(int $id): void
    {
        $c = Cheltuiala::find($id);
        if (! $c) {
            return;
        }
        $this->idDeSters = $c->id;
        $this->denumireDeSters = '#' . $c->id . ' — ' . $c->nr_factura . ' / ' . $c->furnizor;
        $this->modalStergere = true;
    }

    public function inchideModalStergere(): void
    {
        $this->modalStergere = false;
        $this->idDeSters = null;
        $this->denumireDeSters = '';
    }

    public function confirmaStergere(MiscariStocService $stocService): void
    {
        if (! $this->idDeSters) {
            return;
        }
        $c = Cheltuiala::find($this->idDeSters);
        if (! $c) {
            $this->inchideModalStergere();
            return;
        }
        $stocService->revertIntrariCheltuiala($c);
        $c->delete(); // cascade pe linii
        $this->inchideModalStergere();
        session()->flash('mesaj', 'Factura stearsa. Mişcarile de stoc au fost reversate.');
    }

    public function render()
    {
        $q = Cheltuiala::query()
            ->with(['depozit'])
            ->withCount('produse')
            ->orderByDesc('data')
            ->orderByDesc('id');

        if ($this->cautare !== '') {
            $term = '%' . $this->cautare . '%';
            $q->where(function ($qq) use ($term) {
                $qq->where('nr_factura', 'like', $term)
                    ->orWhere('furnizor', 'like', $term)
                    ->orWhere('id', $this->cautare);
            });
        }

        if ($this->filtruDepozit) {
            $q->where('id_depozit', $this->filtruDepozit);
        }

        if ($this->filtruAchitat === 'achitat') {
            $q->where('achitat', true);
        } elseif ($this->filtruAchitat === 'neachitat') {
            $q->where('achitat', false);
        }

        if ($this->deLa !== '') {
            $q->where('data', '>=', $this->deLa);
        }
        if ($this->panaLa !== '') {
            $q->where('data', '<=', $this->panaLa);
        }

        $cheltuieli = $q->paginate(20);

        // Sume agregate pe filtrele active (acelasi query, doar SUM)
        $sumaTotala = (float) (clone $q)->sum('total');
        $sumaAchitata = (float) (clone $q)->where('achitat', true)->sum('total');
        $sumaNeachitata = $sumaTotala - $sumaAchitata;

        return view('livewire.cheltuieli.index', [
            'cheltuieli' => $cheltuieli,
            'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
            'sumaTotala' => $sumaTotala,
            'sumaAchitata' => $sumaAchitata,
            'sumaNeachitata' => $sumaNeachitata,
        ]);
    }
}
