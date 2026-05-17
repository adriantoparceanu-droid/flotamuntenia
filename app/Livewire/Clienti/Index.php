<?php

namespace App\Livewire\Clienti;

use App\Models\Client;
use App\Models\Produs;
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

    // 'activi' = doar non-reziliati (default), 'reziliati' = doar reziliati, 'toti' = tot
    #[Url(as: 'st')]
    public string $status = 'activi';

    #[Url(as: 'tip')]
    public string $tip = 'toti';

    public function updatingCautare(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingTip(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Client::query()
            ->withCount('adrese')
            ->withExists([
                'adrese as are_abonament'  => fn($q) => $q->whereHas('produs', fn($q2) => $q2->where('abonament', Produs::TIP_ABONAMENT)),
                'adrese as are_filtre'     => fn($q) => $q->whereHas('produs', fn($q2) => $q2->where('abonament', Produs::TIP_FILTRE)),
                'adrese as are_per_bucata' => fn($q) => $q->whereHas('produs', fn($q2) => $q2->where('abonament', Produs::TIP_PER_BUCATA)),
            ]);

        if ($this->cautare !== '') {
            $cautare = $this->cautare;
            $query->where(function ($q) use ($cautare) {
                $q->where('denumire', 'like', "%{$cautare}%")
                  ->orWhere('cif', 'like', "%{$cautare}%")
                  ->orWhere('cod_client', 'like', "%{$cautare}%")
                  ->orWhere('email', 'like', "%{$cautare}%")
                  ->orWhere('telefon', 'like', "%{$cautare}%");
            });
        }

        match ($this->status) {
            'reziliati' => $query->where('reziliat', true),
            'toti' => null,
            default => $query->where('reziliat', false),
        };

        if ($this->tip === 'pj') {
            $query->where('client', Client::TIP_PJ);
        } elseif ($this->tip === 'pf') {
            $query->where('client', Client::TIP_PF);
        }

        return view('livewire.clienti.index', [
            'clienti' => $query->orderByDesc('id')->paginate(20),
        ]);
    }
}
