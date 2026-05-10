<?php

namespace App\Livewire\Dashboard\Tabele;

use App\Models\AdresaLivrare;
use App\Models\Recipient;
use Livewire\Attributes\Poll;
use Livewire\Component;

/**
 * Sub-componenta dashboard — top 10 adrese cu sold pozitiv > 5 bidoane.
 * Sold pozitiv = bidoane de recuperat la client (bani blocati in custodie).
 * Sold negativ = datorie firma catre client (clientul are surplus de goale).
 *
 * Polling 30s.
 */
class Recipienti extends Component
{
    /** Pragul minim pentru a aparea in lista (evita zgomotul). */
    private const PRAG_MIN_BIDOANE = 5;

    #[Poll('30s')]
    public function render()
    {
        // Selectam toate adresele cu cel putin o miscare de recipienti
        // si calculam soldul. Pentru perf, agregam direct in DB cu un singur
        // query (sum cu group by id_adresa).
        $solduri = Recipient::query()
            ->selectRaw('
                id_adresa,
                COALESCE(SUM(lasati), 0) - COALESCE(SUM(recuperati), 0) as sold19l,
                COALESCE(SUM(lasati_11l), 0) - COALESCE(SUM(recuperati_11l), 0) as sold11l
            ')
            ->groupBy('id_adresa')
            ->get();

        // Filtram dupa total sold (19L + 11L) > prag
        $candidati = $solduri
            ->map(function ($r) {
                $totalSold = (int) $r->sold19l + (int) $r->sold11l;
                return (object) [
                    'id_adresa' => $r->id_adresa,
                    'sold19l' => (int) $r->sold19l,
                    'sold11l' => (int) $r->sold11l,
                    'total' => $totalSold,
                ];
            })
            ->filter(fn ($r) => $r->total >= self::PRAG_MIN_BIDOANE)
            ->sortByDesc('total')
            ->take(10)
            ->values();

        // Eager load adrese + clienti pentru afisare
        $idAdrese = $candidati->pluck('id_adresa')->toArray();
        $adrese = AdresaLivrare::with('client')
            ->whereIn('id', $idAdrese)
            ->get()
            ->keyBy('id');

        $randuri = $candidati->map(function ($c) use ($adrese) {
            $adresa = $adrese[$c->id_adresa] ?? null;
            return (object) [
                'id_adresa' => $c->id_adresa,
                'id_client' => $adresa?->id_client,
                'client' => $adresa?->client?->denumire ?? '?',
                'adresa_text' => $adresa?->adresaCompleta() ?? '—',
                'sold19l' => $c->sold19l,
                'sold11l' => $c->sold11l,
                'total' => $c->total,
            ];
        });

        return view('livewire.dashboard.tabele.recipienti', [
            'randuri' => $randuri,
        ]);
    }
}
