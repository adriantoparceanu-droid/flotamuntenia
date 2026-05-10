<?php

namespace App\Livewire\Probleme;

use App\Models\Car;
use App\Models\Deposit;
use App\Models\Problema;
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

    // 'toate' | 'nelivrate' | 'livrate' | 'neachitate' | 'achitate'
    #[Url(as: 'status')]
    public string $filtruStatus = 'toate';

    // Modal stergere
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
        $p = Problema::findOrFail($id);
        $p->livrat = ! $p->livrat;
        $p->save();
        session()->flash('mesaj', 'Status rezolvare actualizat.');
    }

    public function comutaAchitat(int $id): void
    {
        $p = Problema::findOrFail($id);
        $p->achitat = ! $p->achitat;
        $p->save();
        session()->flash('mesaj', 'Status plata actualizat.');
    }

    // ===== Stergere =====

    public function deschideModalStergere(int $id): void
    {
        $p = Problema::with('client')->findOrFail($id);
        if ($p->livrat) {
            session()->flash('eroare', 'Nu poti sterge o problema deja rezolvata.');
            return;
        }
        $this->idDeSters = $p->id;
        $this->denumireDeSters = '#' . $p->id . ' — ' . ($p->client?->denumire ?? '?')
            . ' (' . $p->data_livrare?->format('d.m.Y') . ')';
        $this->modalStergere = true;
    }

    public function inchideModalStergere(): void
    {
        $this->modalStergere = false;
        $this->idDeSters = null;
        $this->denumireDeSters = '';
    }

    public function confirmaStergere(): void
    {
        if (! $this->idDeSters) {
            return;
        }
        $p = Problema::find($this->idDeSters);
        if (! $p) {
            $this->inchideModalStergere();
            return;
        }
        if ($p->livrat) {
            session()->flash('eroare', 'Nu poti sterge o problema deja rezolvata.');
            $this->inchideModalStergere();
            return;
        }

        // Problemele NU au mișcări de stoc — DELETE direct.
        $p->delete();

        $this->inchideModalStergere();
        session()->flash('mesaj', 'Problema stearsa.');
    }

    public function render()
    {
        $q = Problema::query()
            ->with(['client', 'adresa', 'masina', 'depozit'])
            ->orderByDesc('data_livrare')
            ->orderByDesc('id');

        if ($this->cautare !== '') {
            $term = '%' . $this->cautare . '%';
            $q->where(function ($qq) use ($term) {
                $qq->whereHas('client', fn ($cq) => $cq->where('denumire', 'like', $term)
                        ->orWhere('cod_client', 'like', $term)
                        ->orWhere('cif', 'like', $term))
                    ->orWhere('descriere', 'like', $term)
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
            $q->where('livrat', false);
        } elseif ($this->filtruStatus === 'achitate') {
            $q->where('achitat', true);
        } elseif ($this->filtruStatus === 'neachitate') {
            $q->where('achitat', false);
        }

        return view('livewire.probleme.index', [
            'probleme' => $q->paginate(20),
            'masini' => Car::where('activ', true)->orderBy('denumire')->get(),
            'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
        ]);
    }
}
