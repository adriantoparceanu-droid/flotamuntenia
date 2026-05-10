<?php

namespace App\Livewire\ComenziRapide;

use App\Models\Car;
use App\Models\ComandaRapida;
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

    // 'toate' | 'livrate' | 'nelivrate'
    #[Url(as: 'status')]
    public string $filtruStatus = 'toate';

    public bool $modalStergere = false;
    public ?int $idDeSters = null;
    public string $denumireDeSters = '';

    public function updating($prop): void
    {
        if (in_array($prop, ['cautare', 'dataDeLa', 'dataPanaLa', 'filtruMasina', 'filtruDepozit', 'filtruStatus'], true)) {
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
        $this->resetPage();
    }

    public function comutaLivrat(int $id): void
    {
        $c = ComandaRapida::findOrFail($id);
        $c->livrat = ! $c->livrat;
        $c->save();
        session()->flash('mesaj', 'Status livrare actualizat.');
    }

    public function comutaAchitat(int $id): void
    {
        $c = ComandaRapida::findOrFail($id);
        $c->achitat = ! $c->achitat;
        $c->save();
        session()->flash('mesaj', 'Status plata actualizat.');
    }

    public function deschideModalStergere(int $id): void
    {
        $c = ComandaRapida::findOrFail($id);
        if ($c->livrat) {
            session()->flash('eroare', 'Nu poti sterge o comanda deja livrata.');
            return;
        }
        $this->idDeSters = $c->id;
        $this->denumireDeSters = '#' . $c->id . ' — ' . $c->denumire . ' (' . $c->data_livrare?->format('d.m.Y') . ')';
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
        $c = ComandaRapida::find($this->idDeSters);
        if (! $c) {
            $this->inchideModalStergere();
            return;
        }
        if ($c->livrat) {
            session()->flash('eroare', 'Nu poti sterge o comanda deja livrata.');
            $this->inchideModalStergere();
            return;
        }

        $stocService->revertIesiriComandaRapida($c);
        $c->delete();

        $this->inchideModalStergere();
        session()->flash('mesaj', 'Comanda rapida stearsa.');
    }

    public function render()
    {
        $q = ComandaRapida::query()
            ->with(['masina', 'depozit'])
            ->orderByDesc('data_livrare')
            ->orderByDesc('id');

        if ($this->cautare !== '') {
            $term = '%' . $this->cautare . '%';
            $q->where(function ($qq) use ($term) {
                $qq->where('denumire', 'like', $term)
                    ->orWhere('adresa', 'like', $term)
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
            $q->where('livrat', false);
        }

        return view('livewire.comenzi-rapide.index', [
            'comenzi' => $q->paginate(20),
            'masini' => Car::where('activ', true)->orderBy('denumire')->get(),
            'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
        ]);
    }
}
