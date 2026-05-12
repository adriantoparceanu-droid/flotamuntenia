<?php

namespace App\Livewire\Portal\Comenzi;

use App\Models\Comanda;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Faza 6.3 — Lista comenzi proprii (portal client).
 *
 * Filtreaza strict pe `id_client = auth()->user()->id_client` (clientul nu
 * vede comenzile altor clienti).
 *
 * Filtre:
 *   - status: toate | in_asteptare | aprobate | livrate | respinse
 *   - interval data
 *
 * Detalii inline: click pe rand → expandeaza si arata produsele + total.
 * Nu avem rute /detalii (decizie MVP — toate informatiile incap inline).
 */
#[Layout('layouts.portal')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $filtruStatus = 'toate';

    #[Url(as: 'de_la')]
    public string $dataDeLa = '';

    #[Url(as: 'pana_la')]
    public string $dataPanaLa = '';

    public ?int $expandatId = null;

    public function updating($prop): void
    {
        if (in_array($prop, ['filtruStatus', 'dataDeLa', 'dataPanaLa'], true)) {
            $this->resetPage();
        }
    }

    public function reseteazaFiltre(): void
    {
        $this->filtruStatus = 'toate';
        $this->dataDeLa = '';
        $this->dataPanaLa = '';
        $this->resetPage();
    }

    public function expandeaza(int $id): void
    {
        // Toggle: click din nou pe acelasi rand inchide
        $this->expandatId = $this->expandatId === $id ? null : $id;
    }

    public function render()
    {
        $idClient = auth()->user()->id_client;

        $comenzi = Comanda::query()
            ->where('id_client', $idClient)
            ->with(['adresa', 'produse.produs', 'masina', 'depozit'])
            ->when($this->filtruStatus !== 'toate', function ($q) {
                match ($this->filtruStatus) {
                    'in_asteptare' => $q->where('status', Comanda::STATUS_IN_ASTEPTARE),
                    'respinse' => $q->where('status', Comanda::STATUS_RESPINS),
                    'aprobate' => $q->vizibile()->where('livrat', false),
                    'livrate' => $q->where('livrat', true),
                    default => null,
                };
            })
            ->when($this->dataDeLa !== '', fn ($q) => $q->whereDate('data_livrare', '>=', $this->dataDeLa))
            ->when($this->dataPanaLa !== '', fn ($q) => $q->whereDate('data_livrare', '<=', $this->dataPanaLa))
            ->orderByDesc('data_livrare')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.portal.comenzi.index', compact('comenzi'));
    }
}
