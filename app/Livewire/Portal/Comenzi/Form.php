<?php

namespace App\Livewire\Portal\Comenzi;

use App\Models\AdresaLivrare;
use App\Models\Comanda;
use App\Models\CostProduct;
use App\Models\Produs;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Faza 6.3 — Formular comanda noua (portal client).
 *
 * Pasi:
 *   1. Clientul alege adresa de livrare (din adresele propriului cont)
 *   2. Sistemul prefill-uieste liniile din configurarea `produs` per adresa
 *      (la fel ca admin Form, dar simplificat — clientul nu poate adauga
 *      linii custom)
 *   3. Clientul ajusteaza cantitatile + alege data + observatii
 *   4. Submit -> salveaza cu status='In asteptare', tip='consum suplimentar'
 *      sau 'fara abonament' (NU 'abonament' — abonamentele sunt programate
 *      doar de admin), id_utilizator setat (audit)
 *   5. Email catre adminii activi via MailService stub
 *   6. NU genereaza miscari stoc (acelea vin la aprobare in admin)
 *
 * Constraint: clientul vede DOAR adresele propriului `id_client`.
 * Validare suplimentara la salvare ca id_adresa apartine clientului
 * (defense in depth contra manipulare client-side).
 */
#[Layout('layouts.portal')]
class Form extends Component
{
    public ?int $idAdresa = null;

    public string $tipComanda = Comanda::TIP_FARA_ABONAMENT;

    public int $idModalitatePlata = Comanda::MODPLATA_CASH;

    public string $dataLivrare = '';

    public string $intervalLivrare = '';

    public string $observatii = '';

    /**
     * Linii editabile: ['id_produs' => int|null, 'denumire' => string,
     * 'cantitate' => int, 'pret' => string]
     * Pre-completate din configurarea adresei la selectarea acesteia.
     */
    public array $linii = [];

    public function mount(): void
    {
        // Default: maine (clientul nu poate plasa pentru azi — admin trebuie sa aprobe)
        $this->dataLivrare = now()->addDay()->toDateString();

        // Daca clientul are o singura adresa, o pre-selectam automat
        $adrese = $this->adreseleClientului();
        if ($adrese->count() === 1) {
            $this->idAdresa = $adrese->first()->id;
            $this->updatedIdAdresa();
        }
    }

    /**
     * Adresele clientului autentificat (sursa de adevar pentru dropdown).
     * Limita stricta: id_client = auth()->user()->id_client + activ=true.
     */
    public function adreseleClientului()
    {
        $idClient = auth()->user()->id_client;
        if (! $idClient) {
            return collect();
        }
        return AdresaLivrare::where('id_client', $idClient)
            ->where('activ', true)
            ->orderBy('denumire')
            ->orderBy('id')
            ->get();
    }

    /**
     * La selectarea adresei, pre-completeaza liniile din configurarea `produs`
     * (similar cu admin Form, dar simplificat: pastram doar 19L + 11L).
     */
    public function updatedIdAdresa(): void
    {
        $this->linii = [];

        if (! $this->idAdresa) {
            return;
        }

        // Defense in depth: verificam ca adresa apartine clientului
        $adresa = AdresaLivrare::with('produs')
            ->where('id', $this->idAdresa)
            ->where('id_client', auth()->user()->id_client)
            ->first();

        if (! $adresa) {
            $this->idAdresa = null;
            return;
        }

        $produs = $adresa->produs;
        $produs19l = CostProduct::find(45);
        $produs11l = CostProduct::find(46);

        // Pretul: din configurarea adresei daca exista, altfel default catalog
        $pret19l = $produs?->pret ?? $produs19l?->pret ?? 0;
        $pret11l = $produs?->pret_11l ?? $produs11l?->pret ?? 0;

        if ($produs19l) {
            $this->linii[] = [
                'id_produs' => 45,
                'denumire' => $produs19l->denumire,
                'cantitate' => 0,
                'pret' => (string) $pret19l,
            ];
        }
        if ($produs11l) {
            $this->linii[] = [
                'id_produs' => 46,
                'denumire' => $produs11l->denumire,
                'cantitate' => 0,
                'pret' => (string) $pret11l,
            ];
        }

        // Daca adresa are configurare 'consum suplimentar' (peste abonament),
        // setam tipComanda corespunzator pentru a sugera adminului ce-i acolo
        if ($produs && $produs->isAbonament()) {
            $this->tipComanda = Comanda::TIP_CONSUM_SUPLIMENTAR;
        } else {
            $this->tipComanda = Comanda::TIP_FARA_ABONAMENT;
        }
    }

    public function incrementeaza(int $idx): void
    {
        if (! isset($this->linii[$idx])) return;
        $this->linii[$idx]['cantitate'] = (int) ($this->linii[$idx]['cantitate'] ?? 0) + 1;
    }

    public function decrementeaza(int $idx): void
    {
        if (! isset($this->linii[$idx])) return;
        $this->linii[$idx]['cantitate'] = max(0, (int) ($this->linii[$idx]['cantitate'] ?? 0) - 1);
    }

    public function totalCalculat(): float
    {
        $total = 0.0;
        foreach ($this->linii as $l) {
            $total += (int) ($l['cantitate'] ?? 0) * (float) ($l['pret'] ?? 0);
        }
        return $total;
    }

    public function salveaza()
    {
        $this->validate([
            'idAdresa' => ['required', 'exists:adresa_livrare,id'],
            'tipComanda' => ['required', 'in:consum suplimentar,fara abonament'],
            'idModalitatePlata' => ['required', 'in:1,2,3,4'],
            'dataLivrare' => ['required', 'date', 'after_or_equal:today'],
            'intervalLivrare' => ['nullable', 'string', 'max:50'],
            'observatii' => ['nullable', 'string', 'max:2000'],
            'linii' => ['required', 'array', 'min:1'],
            'linii.*.cantitate' => ['required', 'integer', 'min:0'],
            'linii.*.pret' => ['required', 'numeric', 'min:0'],
        ], [
            'idAdresa.required' => 'Selecteaza o adresa de livrare.',
            'dataLivrare.required' => 'Data livrarii este obligatorie.',
            'dataLivrare.after_or_equal' => 'Data livrarii nu poate fi in trecut.',
            'linii.required' => 'Comanda nu poate fi goala.',
        ]);

        // Defense in depth: revalidam ca adresa apartine clientului
        $adresa = AdresaLivrare::where('id', $this->idAdresa)
            ->where('id_client', auth()->user()->id_client)
            ->first();
        if (! $adresa) {
            session()->flash('eroare', 'Adresa selectata nu apartine contului tau.');
            return;
        }

        // Cel putin o linie cu cantitate > 0
        $totalBucati = collect($this->linii)->sum(fn ($l) => (int) ($l['cantitate'] ?? 0));
        if ($totalBucati < 1) {
            $this->addError('linii', 'Selecteaza cel putin o cantitate.');
            return;
        }

        $idClient = auth()->user()->id_client;
        $idUtilizator = auth()->id();

        // Calcul cantitati 19L/11L (denormalizate)
        $nr19l = 0;
        $nr11l = 0;
        foreach ($this->linii as $l) {
            if ((int) ($l['id_produs'] ?? 0) === 45) {
                $nr19l += (int) $l['cantitate'];
            } elseif ((int) ($l['id_produs'] ?? 0) === 46) {
                $nr11l += (int) $l['cantitate'];
            }
        }

        $payload = [
            'id_client' => $idClient,
            'id_adresa' => $this->idAdresa,
            'id_masina' => null,
            'id_depozit' => null,
            'tip_comanda' => $this->tipComanda,
            'nr_recipienti' => $nr19l,
            'nr_pahare' => $nr11l,
            'id_modalitate_plata' => $this->idModalitatePlata,
            'data_livrare' => $this->dataLivrare,
            'interval_livrare' => $this->intervalLivrare ?: null,
            'observatii' => $this->observatii ?: null,
            'status' => Comanda::STATUS_IN_ASTEPTARE,
            'id_utilizator' => $idUtilizator,
        ];

        $comandaId = null;
        DB::transaction(function () use ($payload, &$comandaId) {
            $comanda = Comanda::create($payload);

            foreach ($this->linii as $l) {
                if ((int) ($l['cantitate'] ?? 0) < 1) {
                    continue;
                }
                $comanda->produse()->create([
                    'id_produs' => (int) $l['id_produs'],
                    'cantitate' => (int) $l['cantitate'],
                    'pret' => $l['pret'],
                ]);
            }

            $comandaId = $comanda->id;
        });

        // Email catre toti adminii activi (sa stie ca au o comanda noua de aprobat)
        $emailuriAdmini = User::whereIn('tip', [User::TIP_ADMIN, User::TIP_SUPERADMIN])
            ->where('confirmat', true)
            ->whereNotNull('email')
            ->pluck('email');

        $client = auth()->user()->client;
        foreach ($emailuriAdmini as $emailAdmin) {
            MailService::send('comanda_portal_noua', $emailAdmin, [
                'client' => $client?->denumire,
                'cod_comanda' => $comandaId,
                'data_livrare' => Carbon::parse($this->dataLivrare)->format('d.m.Y'),
                'total' => $this->totalCalculat(),
                'plasata_de' => auth()->user()->name,
            ]);
        }

        session()->flash('mesaj', 'Comanda a fost trimisa spre aprobare. Vei primi un email cand este aprobata sau respinsa.');
        $this->redirectRoute('portal.comenzi.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.portal.comenzi.form', [
            'adrese' => $this->adreseleClientului(),
        ]);
    }
}
