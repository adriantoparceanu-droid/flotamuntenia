<?php

namespace App\Livewire\Comenzi;

use App\Models\Car;
use App\Models\Comanda;
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

    #[Url(as: 'de_la')]
    public string $dataDeLa = '';

    #[Url(as: 'pana_la')]
    public string $dataPanaLa = '';

    #[Url(as: 'masina')]
    public ?int $filtruMasina = null;

    #[Url(as: 'depozit')]
    public ?int $filtruDepozit = null;

    // 'toate' | 'livrate' | 'nelivrate' | 'in_asteptare' | 'respinse'
    #[Url(as: 'status')]
    public string $filtruStatus = 'toate';

    // 'toate' | 'abonament' | 'consum suplimentar' | 'fara abonament'
    #[Url(as: 'tip')]
    public string $filtruTip = 'toate';

    // Stergere
    public bool $modalStergere = false;
    public ?int $idDeSters = null;
    public string $denumireDeSters = '';

    public function updating($prop): void
    {
        // Resetam paginarea cand se schimba filtrele
        if (in_array($prop, ['cautare', 'dataDeLa', 'dataPanaLa', 'filtruMasina', 'filtruDepozit', 'filtruStatus', 'filtruTip'], true)) {
            $this->resetPage();
        }
    }

    public function reseteazaFiltre(): void
    {
        $this->cautare = '';
        $this->dataDeLa = '';
        $this->dataPanaLa = '';
        $this->filtruMasina = null;
        $this->filtruDepozit = null;
        $this->filtruStatus = 'toate';
        $this->filtruTip = 'toate';
        $this->resetPage();
    }

    public function comutaLivrat(int $id): void
    {
        $c = Comanda::findOrFail($id);
        $c->livrat = ! $c->livrat;
        $c->save();
        session()->flash('mesaj', 'Status livrare actualizat.');
    }

    public function comutaAchitat(int $id): void
    {
        $c = Comanda::findOrFail($id);
        $c->achitat = ! $c->achitat;
        $c->save();
        session()->flash('mesaj', 'Status plata actualizat.');
    }

    // ===== Stergere =====

    public function deschideModalStergere(int $id): void
    {
        $c = Comanda::with('client')->findOrFail($id);
        if ($c->livrat) {
            session()->flash('eroare', 'Nu poti sterge o comanda deja livrata.');
            return;
        }
        $this->idDeSters = $c->id;
        $this->denumireDeSters = '#' . $c->id . ' — ' . ($c->client?->denumire ?? '?') . ' (' . $c->data_livrare?->format('d.m.Y') . ')';
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

        $c = Comanda::find($this->idDeSters);
        if (! $c) {
            $this->inchideModalStergere();
            return;
        }
        if ($c->livrat) {
            session()->flash('eroare', 'Nu poti sterge o comanda deja livrata.');
            $this->inchideModalStergere();
            return;
        }

        // Reverse miscari stoc + DELETE comanda (cascade pe linii)
        $stocService->revertIesiriComanda($c);
        $c->delete();

        $this->inchideModalStergere();
        session()->flash('mesaj', 'Comanda stearsa.');
    }

    public function render()
    {
        $q = Comanda::query()
            ->with(['client', 'adresa', 'masina', 'depozit'])
            ->orderByDesc('data_livrare')
            ->orderByDesc('id');

        if ($this->cautare !== '') {
            $term = '%' . $this->cautare . '%';
            $q->where(function ($qq) use ($term) {
                $qq->whereHas('client', fn ($cq) => $cq->where('client', 'like', $term)
                    ->orWhere('cod_client', 'like', $term)
                    ->orWhere('cif', 'like', $term))
                    ->orWhere('nume', 'like', $term)
                    ->orWhere('telefon', 'like', $term)
                    ->orWhere('id', $this->cautare);
            });
        }

        if ($this->dataDeLa !== '') {
            $q->whereDate('data_livrare', '>=', $this->dataDeLa);
        }
        if ($this->dataPanaLa !== '') {
            $q->whereDate('data_livrare', '<=', $this->dataPanaLa);
        }
        if ($this->filtruMasina) {
            $q->where('id_masina', $this->filtruMasina);
        }
        if ($this->filtruDepozit) {
            $q->where('id_depozit', $this->filtruDepozit);
        }
        if ($this->filtruStatus === 'livrate') {
            $q->where('livrat', true);
        } elseif ($this->filtruStatus === 'nelivrate') {
            $q->where('livrat', false)->vizibile();
        } elseif ($this->filtruStatus === 'in_asteptare') {
            $q->where('status', Comanda::STATUS_IN_ASTEPTARE);
        } elseif ($this->filtruStatus === 'respinse') {
            $q->where('status', Comanda::STATUS_RESPINS);
        } elseif ($this->filtruStatus === 'toate') {
            // Default: ascundem comenzile respinse din lista generala (raman accesibile prin filtru explicit)
            $q->where(function ($qq) {
                $qq->whereNull('status')->orWhere('status', '!=', Comanda::STATUS_RESPINS);
            });
        }
        if ($this->filtruTip !== 'toate') {
            $q->where('tip_comanda', $this->filtruTip);
        }

        return view('livewire.comenzi.index', [
            'comenzi' => $q->paginate(20),
            'masini' => Car::where('activ', true)->orderBy('denumire')->get(),
            'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
        ]);
    }
}
