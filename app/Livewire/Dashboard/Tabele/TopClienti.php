<?php

namespace App\Livewire\Dashboard\Tabele;

use App\Models\Client;
use Livewire\Attributes\Poll;
use Livewire\Component;

/**
 * Sub-componenta dashboard — top 10 clienti dupa nr comenzi luna curenta.
 * Polling 30s — date relevante pentru relatii VIP / oferte fidelizare.
 */
class TopClienti extends Component
{
    #[Poll('30s')]
    public function render()
    {
        $luna = now()->month;
        $an = now()->year;

        $clienti = Client::query()
            ->where('reziliat', false)
            ->withCount(['comenzi' => function ($q) use ($an, $luna) {
                $q->whereYear('data_livrare', $an)
                  ->whereMonth('data_livrare', $luna)
                  ->vizibile();
            }])
            ->orderByDesc('comenzi_count')
            ->limit(10)
            ->get();

        // Excludem clientii cu 0 comenzi luna curenta — nu au ce face in „top"
        $clienti = $clienti->filter(fn ($c) => $c->comenzi_count > 0)->values();

        return view('livewire.dashboard.tabele.top-clienti', [
            'clienti' => $clienti,
        ]);
    }
}
