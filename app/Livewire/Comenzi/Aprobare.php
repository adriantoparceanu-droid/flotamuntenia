<?php

namespace App\Livewire\Comenzi;

use App\Models\Comanda;
use App\Services\MailService;
use App\Services\MiscariStocService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Aprobare extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $cautare = '';

    // 'in_asteptare' | 'respinse' | 'toate' (audit)
    #[Url(as: 'status')]
    public string $filtruStatus = 'in_asteptare';

    // Modal respingere
    public bool $modalRespingere = false;
    public ?int $idDeRespins = null;
    public string $denumireDeRespins = '';
    public string $motivRespingere = '';

    public function updating($prop): void
    {
        if (in_array($prop, ['cautare', 'filtruStatus'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Aprobare directa: status -> NULL, audit aprobat_de, sincronizeaza miscari
     * stoc OUT (comenzile portal nu aveau OUT cat erau In asteptare), trimite
     * email confirmare prin MailService stub.
     */
    public function aproba(int $id, MiscariStocService $stocService): void
    {
        $c = Comanda::with(['client', 'produse', 'adresa'])->find($id);
        if (! $c || ! $c->isInAsteptare()) {
            session()->flash('eroare', 'Comanda nu mai este in asteptare.');
            return;
        }

        DB::transaction(function () use ($c, $stocService) {
            $c->status = null;
            $c->aprobat_de = auth()->id();
            $c->save();

            // Acum genereaza miscarile OUT (cat era 'In asteptare' nu existau)
            $stocService->sincronizeazaIesiriComanda($c);
        });

        MailService::send('comanda_aprobata', $c->client?->email, [
            'client' => $c->client?->denumire,
            'cod_comanda' => $c->id,
            'data_livrare' => $c->data_livrare?->format('d.m.Y'),
            'interval' => $c->interval_livrare,
            'total' => $c->total(),
        ]);

        session()->flash('mesaj', "Comanda #{$c->id} a fost aprobata. Email confirmare trimis.");
    }

    /**
     * Deschide modalul de respingere.
     */
    public function deschideModalRespingere(int $id): void
    {
        $c = Comanda::with('client')->find($id);
        if (! $c || ! $c->isInAsteptare()) {
            session()->flash('eroare', 'Comanda nu mai este in asteptare.');
            return;
        }
        $this->idDeRespins = $c->id;
        $this->denumireDeRespins = '#' . $c->id . ' — ' . ($c->client?->denumire ?? '?');
        $this->motivRespingere = '';
        $this->modalRespingere = true;
    }

    public function inchideModalRespingere(): void
    {
        $this->modalRespingere = false;
        $this->idDeRespins = null;
        $this->denumireDeRespins = '';
        $this->motivRespingere = '';
    }

    public function confirmaRespingere(): void
    {
        if (! $this->idDeRespins) {
            return;
        }

        $c = Comanda::with('client')->find($this->idDeRespins);
        if (! $c || ! $c->isInAsteptare()) {
            $this->inchideModalRespingere();
            session()->flash('eroare', 'Comanda nu mai este in asteptare.');
            return;
        }

        $motiv = trim($this->motivRespingere);

        $c->status = Comanda::STATUS_RESPINS;
        $c->motiv_respingere = $motiv !== '' ? $motiv : null;
        $c->data_respingere = now();
        $c->aprobat_de = auth()->id(); // refolosim ca audit "decided_by"
        $c->save();

        MailService::send('comanda_respinsa', $c->client?->email, [
            'client' => $c->client?->denumire,
            'cod_comanda' => $c->id,
            'data_livrare' => $c->data_livrare?->format('d.m.Y'),
            'motiv' => $motiv !== '' ? $motiv : null,
        ]);

        $this->inchideModalRespingere();
        session()->flash('mesaj', "Comanda #{$c->id} a fost respinsa. Email notificare trimis.");
    }

    public function render()
    {
        $q = Comanda::query()
            ->with(['client', 'adresa', 'produse.produs', 'aprobatDe'])
            ->orderBy('created_at')
            ->orderBy('id');

        if ($this->filtruStatus === 'in_asteptare') {
            $q->where('status', Comanda::STATUS_IN_ASTEPTARE);
        } elseif ($this->filtruStatus === 'respinse') {
            $q->where('status', Comanda::STATUS_RESPINS);
        } elseif ($this->filtruStatus === 'toate') {
            $q->whereIn('status', [Comanda::STATUS_IN_ASTEPTARE, Comanda::STATUS_RESPINS]);
        }

        if ($this->cautare !== '') {
            $term = '%' . $this->cautare . '%';
            $q->where(function ($qq) use ($term) {
                $qq->whereHas('client', fn ($cq) => $cq->where('client', 'like', $term)
                    ->orWhere('cod_client', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('cif', 'like', $term))
                    ->orWhere('nume', 'like', $term)
                    ->orWhere('telefon', 'like', $term)
                    ->orWhere('id', $this->cautare);
            });
        }

        return view('livewire.comenzi.aprobare', [
            'comenzi' => $q->paginate(20),
            'totalInAsteptare' => Comanda::where('status', Comanda::STATUS_IN_ASTEPTARE)->count(),
            'totalRespinse' => Comanda::where('status', Comanda::STATUS_RESPINS)->count(),
        ]);
    }
}
