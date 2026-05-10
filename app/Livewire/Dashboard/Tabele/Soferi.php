<?php

namespace App\Livewire\Dashboard\Tabele;

use App\Models\Car;
use App\Models\Comanda;
use Livewire\Attributes\Poll;
use Livewire\Component;

/**
 * Sub-componenta dashboard — performanta soferi luna curenta.
 * Pentru fiecare masina activa: nr comenzi livrate / nr comenzi asignate luna.
 * Procentul afisat ca progress bar pentru evaluare vizuala rapida.
 *
 * Polling 30s.
 */
class Soferi extends Component
{
    #[Poll('30s')]
    public function render()
    {
        $luna = now()->month;
        $an = now()->year;

        // Pentru fiecare masina activa, agregam comenzi luna curenta
        $masini = Car::where('activ', true)
            ->orderBy('denumire')
            ->get();

        // Construim statistici per masina
        $statistici = $masini->map(function ($m) use ($an, $luna) {
            $asignate = Comanda::where('id_masina', $m->id)
                ->whereYear('data_livrare', $an)
                ->whereMonth('data_livrare', $luna)
                ->whereNull('status')
                ->count();

            $livrate = Comanda::where('id_masina', $m->id)
                ->whereYear('data_livrare', $an)
                ->whereMonth('data_livrare', $luna)
                ->whereNull('status')
                ->where('livrat', true)
                ->count();

            return (object) [
                'id' => $m->id,
                'denumire' => $m->denumire,
                'asignate' => $asignate,
                'livrate' => $livrate,
                'procent' => $asignate > 0 ? (int) round(($livrate / $asignate) * 100) : 0,
            ];
        })
        // Excludem masinile fara comenzi luna curenta — nu au sens in raport
        ->filter(fn ($s) => $s->asignate > 0)
        ->sortByDesc('livrate')
        ->values();

        return view('livewire.dashboard.tabele.soferi', [
            'statistici' => $statistici,
        ]);
    }
}
