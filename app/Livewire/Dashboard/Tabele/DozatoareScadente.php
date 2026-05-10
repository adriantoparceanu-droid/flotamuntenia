<?php

namespace App\Livewire\Dashboard\Tabele;

use App\Models\Dozator;
use App\Models\DozatorFiltre;
use Livewire\Attributes\Poll;
use Livewire\Component;

/**
 * Sub-componenta dashboard — top 10 dozatoare cu deadline cel mai apropiat.
 * Combina:
 *   - Dozatoare BIDOANE cu igienizare scadenta in <= 7 zile (sau expirate)
 *   - Dozatoare FILTRE cu mentenanta scadenta in <= 15 zile (sau expirate)
 *
 * Sortat dupa zile ramase (ascending — cele expirate sus, urmate de scadente
 * imediate). Badge color per urgenta: rosu (expirat), amber (≤7 zile), galben (>7).
 *
 * Polling 30s.
 */
class DozatoareScadente extends Component
{
    #[Poll('30s')]
    public function render()
    {
        $azi = today();
        $azistr = $azi->toDateString();

        // Igienizari bidoane scadente in 7 zile + expirate
        $bidoane = Dozator::with(['client', 'adresa'])
            ->where('activ', true)
            ->whereNotNull('perioada_igenizare')
            ->whereDate('perioada_igenizare', '<=', $azi->copy()->addDays(7)->toDateString())
            ->get()
            ->map(function ($d) use ($azi) {
                $zileRamase = (int) $azi->diffInDays($d->perioada_igenizare->startOfDay(), false);
                return (object) [
                    'tip' => 'bidoane',
                    'icon' => 'cube',
                    'culoare_tip' => 'cyan',
                    'id_client' => $d->client?->id,
                    'client' => $d->client?->denumire ?? '?',
                    'adresa' => $d->adresa?->denumire ?? $d->adresa?->adresaCompleta() ?? '—',
                    'serie' => $d->serie,
                    'data_scadenta' => $d->perioada_igenizare,
                    'zile' => $zileRamase,
                    'href' => route('dozatoare.index') . '?tip=bidoane',
                ];
            });

        // Filtre scadente in 15 zile + expirate
        $filtre = DozatorFiltre::with(['client', 'adresa'])
            ->where('status', DozatorFiltre::STATUS_ACTIV)
            ->whereNotNull('data_urmatoare_mentenanta')
            ->whereDate('data_urmatoare_mentenanta', '<=', $azi->copy()->addDays(15)->toDateString())
            ->get()
            ->map(function ($d) use ($azi) {
                $zileRamase = (int) $azi->diffInDays($d->data_urmatoare_mentenanta->startOfDay(), false);
                return (object) [
                    'tip' => 'filtre',
                    'icon' => 'funnel',
                    'culoare_tip' => 'amber',
                    'id_client' => $d->client?->id,
                    'client' => $d->client?->denumire ?? '?',
                    'adresa' => $d->adresa?->denumire ?? $d->adresa?->adresaCompleta() ?? '—',
                    'serie' => $d->serie,
                    'data_scadenta' => $d->data_urmatoare_mentenanta,
                    'zile' => $zileRamase,
                    'href' => route('dozatoare.index') . '?tip=filtre',
                ];
            });

        // Combinam si sortam — cele expirate (zile negative) primele.
        // Folosim `concat()` peste `merge()` pentru ca items-urile sunt stdClass
        // (rezultate din (object) cast in map). Eloquent\Collection::merge()
        // apeleaza getKey() pe items pentru dedupe — ar arunca pe stdClass.
        // `concat()` doar appendeaza fara dedupe — exact ce vrem aici.
        $randuri = $bidoane->concat($filtre)
            ->sortBy('zile')
            ->take(10)
            ->values();

        return view('livewire.dashboard.tabele.dozatoare-scadente', [
            'randuri' => $randuri,
        ]);
    }
}
