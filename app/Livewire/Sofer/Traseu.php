<?php

namespace App\Livewire\Sofer;

use App\Models\Car;
use App\Models\Comanda;
use App\Models\ComandaRapida;
use App\Models\Recipient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Traseu extends Component
{
    public const TIP_COMANDA = 'comanda';
    public const TIP_RAPIDA = 'comanda_rapida';

    #[Url(as: 'data')]
    public string $data = '';

    // ID-ul itemului expandat — combinatie tip+id ('comanda-12' sau 'comanda_rapida-3')
    #[Url(as: 'open')]
    public string $cheieExpandata = '';

    // ===== Modal recipienti / confirmare livrare (doar pentru tip='comanda') =====
    public bool $modalRecipienti = false;
    // Cand e true, salvarea modalului insereaza recipienti SI marcheaza comanda livrata
    // (flux combinat din butonul "Confirma livrarea"). Cand e false, doar insert recipienti.
    public bool $modConfirmareLivrare = false;
    public ?int $idComandaActiva = null;
    public int $recLasati19l = 0;
    public int $recRecuperati19l = 0;
    public int $recLasati11l = 0;
    public int $recRecuperati11l = 0;
    public string $recObservatii = '';

    public function mount(): void
    {
        if ($this->data === '') {
            $this->data = now()->toDateString();
        }
    }

    public function navigheazaZi(int $delta): void
    {
        try {
            $this->data = Carbon::parse($this->data)->addDays($delta)->toDateString();
        } catch (\Throwable $e) {
            $this->data = now()->toDateString();
        }
    }

    public function setAzi(): void
    {
        $this->data = now()->toDateString();
    }

    public function expand(string $tip, int $id): void
    {
        $cheie = $tip . '-' . $id;
        $this->cheieExpandata = ($this->cheieExpandata === $cheie) ? '' : $cheie;
    }

    public function comutaLivrat(string $tip, int $id): void
    {
        $c = $this->gaseste($tip, $id);
        if (! $c) {
            return;
        }
        $stareNoua = ! $c->livrat;
        $c->livrat = $stareNoua;
        $c->save();

        $this->dispatch('toast', [
            'mesaj' => $stareNoua ? 'Marcata ca livrata' : 'Anulata livrarea',
            'tip' => 'success',
            'undoAction' => 'comutaLivrat',
            'undoArg' => [$tip, $id],
        ]);
    }

    public function comutaAchitat(string $tip, int $id): void
    {
        $c = $this->gaseste($tip, $id);
        if (! $c) {
            return;
        }
        $stareNoua = ! $c->achitat;
        $c->achitat = $stareNoua;
        $c->save();

        $this->dispatch('toast', [
            'mesaj' => $stareNoua ? 'Marcata ca achitata' : 'Anulata plata',
            'tip' => 'success',
            'undoAction' => 'comutaAchitat',
            'undoArg' => [$tip, $id],
        ]);
    }

    /**
     * Gaseste o comanda (clasica sau rapida) DOAR daca e asignata masinii
     * soferului — guard suplimentar peste middleware (regula §8.7).
     */
    private function gaseste(string $tip, int $id)
    {
        $idMasina = auth()->user()?->id_masina;
        if (! $idMasina) {
            return null;
        }
        if ($tip === self::TIP_RAPIDA) {
            return ComandaRapida::where('id', $id)->where('id_masina', $idMasina)->first();
        }
        return Comanda::where('id', $id)->where('id_masina', $idMasina)->first();
    }

    // ===== Recipienti — doar pentru comenzi clasice (au id_adresa) =====

    /**
     * Deschide modalul in modul "doar recipienti" (fara a marca comanda livrata).
     * Util cand soferul a ridicat doar bidoane goale fara livrare.
     */
    public function deschideRecipienti(int $idComanda): void
    {
        $this->prepareModalRecipienti($idComanda, modConfirmareLivrare: false);
    }

    /**
     * Flux combinat: butonul "Confirma livrarea" deschide modalul cu prefill
     * din comanda; salvarea insereaza recipienti SI marcheaza comanda ca livrata
     * intr-o singura tranzactie. Pinul comenzii dispare apoi de pe harta.
     */
    public function confirmaLivrare(int $idComanda): void
    {
        $this->prepareModalRecipienti($idComanda, modConfirmareLivrare: true);
    }

    private function prepareModalRecipienti(int $idComanda, bool $modConfirmareLivrare): void
    {
        $c = $this->gaseste(self::TIP_COMANDA, $idComanda);
        if (! $c instanceof Comanda) {
            return;
        }
        $this->reseteazaRecipienti();
        $this->idComandaActiva = $c->id;
        $this->recLasati19l = (int) $c->nr_recipienti;
        $this->recLasati11l = (int) $c->nr_pahare;
        $this->modConfirmareLivrare = $modConfirmareLivrare;
        $this->modalRecipienti = true;
    }

    public function inchideRecipienti(): void
    {
        $this->modalRecipienti = false;
        $this->reseteazaRecipienti();
    }

    public function salveazaRecipienti(): void
    {
        $date = $this->validate([
            'recLasati19l' => ['required', 'integer', 'min:0'],
            'recRecuperati19l' => ['required', 'integer', 'min:0'],
            'recLasati11l' => ['required', 'integer', 'min:0'],
            'recRecuperati11l' => ['required', 'integer', 'min:0'],
            'recObservatii' => ['nullable', 'string'],
        ], [
            'recLasati19l.required' => 'Completeaza cantitatea lasata 19L.',
            'recRecuperati19l.required' => 'Completeaza cantitatea recuperata 19L.',
            'recLasati11l.required' => 'Completeaza cantitatea lasata 11L.',
            'recRecuperati11l.required' => 'Completeaza cantitatea recuperata 11L.',
        ]);

        $c = $this->gaseste(self::TIP_COMANDA, $this->idComandaActiva ?? 0);
        if (! $c instanceof Comanda) {
            return;
        }

        // Soldul recipientilor poate fi negativ (clientul returneaza mai multe
        // goale decat a primit pline) — nu mai blocam recuperarea.
        $confirma = $this->modConfirmareLivrare;

        // Tranzactie: insert recipient + (optional) marcare livrat.
        $idRecipient = DB::transaction(function () use ($c, $date, $confirma) {
            $r = Recipient::create([
                'id_client' => $c->id_client,
                'id_adresa' => $c->id_adresa,
                'lasati' => (int) $date['recLasati19l'],
                'recuperati' => (int) $date['recRecuperati19l'],
                'lasati_11l' => (int) $date['recLasati11l'],
                'recuperati_11l' => (int) $date['recRecuperati11l'],
                'data' => $c->data_livrare,
                'id_comanda' => $c->id,
                'id_utilizator' => auth()->id(),
                'observatii' => $date['recObservatii'] ?: null,
            ]);

            if ($confirma) {
                $c->livrat = true;
                $c->save();
            }

            return $r->id;
        });

        $idComanda = $c->id;
        $this->modalRecipienti = false;
        $this->reseteazaRecipienti();

        if ($confirma) {
            $this->dispatch('toast', [
                'mesaj' => 'Livrare confirmata',
                'tip' => 'success',
                'undoAction' => 'revertConfirmareLivrare',
                'undoArg' => [$idComanda, $idRecipient],
            ]);
        } else {
            $this->dispatch('toast', [
                'mesaj' => 'Recipienti actualizati',
                'tip' => 'success',
                'undoAction' => 'stergeMiscareRecipient',
                'undoArg' => [$idRecipient],
            ]);
        }
    }

    /**
     * Anuleaza confirmarea de livrare: sterge inregistrarea de recipienti
     * adaugata si readuce comanda la livrat=false. Folosit din toast undo.
     */
    public function revertConfirmareLivrare(int $idComanda, int $idRecipient): void
    {
        $c = $this->gaseste(self::TIP_COMANDA, $idComanda);
        if (! $c instanceof Comanda) {
            return;
        }

        DB::transaction(function () use ($c, $idRecipient) {
            // Verificam ca miscarea apartine acestei comenzi (defense in depth).
            Recipient::where('id', $idRecipient)
                ->where('id_comanda', $c->id)
                ->delete();
            $c->livrat = false;
            $c->save();
        });

        $this->dispatch('toast', [
            'mesaj' => 'Confirmare livrare anulata',
            'tip' => 'info',
        ]);
    }

    /**
     * Anuleaza ultima miscare de recipienti (fara modificare livrat). Folosit
     * din toast undo cand se deschide modalul izolat (nu flux combinat).
     */
    public function stergeMiscareRecipient(int $idRecipient): void
    {
        $idMasina = auth()->user()?->id_masina;
        if (! $idMasina) {
            return;
        }
        // Stergem doar daca miscarea e legata de o comanda asignata masinii soferului.
        Recipient::where('id', $idRecipient)
            ->whereHas('comanda', fn ($q) => $q->where('id_masina', $idMasina))
            ->delete();

        $this->dispatch('toast', [
            'mesaj' => 'Miscare anulata',
            'tip' => 'info',
        ]);
    }

    private function reseteazaRecipienti(): void
    {
        $this->idComandaActiva = null;
        $this->recLasati19l = 0;
        $this->recRecuperati19l = 0;
        $this->recLasati11l = 0;
        $this->recRecuperati11l = 0;
        $this->recObservatii = '';
        $this->modConfirmareLivrare = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $idMasina = auth()->user()?->id_masina;

        $clasice = collect();
        $rapide = collect();
        $masinaSofer = null;

        if ($idMasina) {
            $masinaSofer = Car::find($idMasina);

            $clasice = Comanda::query()
                ->with(['client', 'adresa', 'produse.produs'])
                ->where('id_masina', $idMasina)
                ->whereDate('data_livrare', $this->data)
                ->vizibile()
                ->orderBy('ordine_traseu')
                ->orderBy('id')
                ->get();

            $rapide = ComandaRapida::query()
                ->with(['produse.produs'])
                ->where('id_masina', $idMasina)
                ->whereDate('data_livrare', $this->data)
                ->orderBy('ordine_traseu')
                ->orderBy('id')
                ->get();
        }

        // Pre-calculam soldul recipientilor doar pentru comenzile clasice
        // (rapidele nu au id_adresa).
        $idAdrese = $clasice->pluck('id_adresa')->unique()->filter();
        $solduri = [];
        foreach ($idAdrese as $idA) {
            $solduri[$idA] = Recipient::soldPerAdresa($idA);
        }

        // Format unificat — ambele tipuri intr-o singura colectie sortata
        $itemiUnificati = collect();

        foreach ($clasice as $c) {
            $itemiUnificati->push([
                'tip' => self::TIP_COMANDA,
                'id' => $c->id,
                'cheie' => self::TIP_COMANDA . '-' . $c->id,
                'ordine_traseu' => (int) $c->ordine_traseu,
                'titlu' => $c->client?->denumire ?? '?',
                'subtitlu' => $c->adresa?->denumire ?? '',
                'adresa_completa' => $c->adresa?->adresaCompleta() ?: '',
                'interfon' => $c->adresa?->interfon,
                'lat' => $c->adresa?->lat,
                'lng' => $c->adresa?->lng,
                'nume_contact' => $c->nume,
                'telefon' => $c->telefon ?: $c->client?->telefon,
                'produse' => $c->produse,
                'total' => $c->total(),
                'mod_plata' => $c->etichetaModPlata(),
                'observatii' => $c->observatii,
                'livrat' => (bool) $c->livrat,
                'achitat' => (bool) $c->achitat,
                'sold' => $solduri[$c->id_adresa] ?? ['19l' => 0, '11l' => 0],
                'are_recipienti' => true, // poate deschide modalul
                'nr19l_comanda' => (int) $c->nr_recipienti,
                'nr11l_comanda' => (int) $c->nr_pahare,
            ]);
        }

        foreach ($rapide as $c) {
            $itemiUnificati->push([
                'tip' => self::TIP_RAPIDA,
                'id' => $c->id,
                'cheie' => self::TIP_RAPIDA . '-' . $c->id,
                'ordine_traseu' => (int) $c->ordine_traseu,
                'titlu' => $c->denumire,
                'subtitlu' => $c->adresa ?: '',
                'adresa_completa' => $c->adresa ?: '',
                'interfon' => null,
                'lat' => $c->lat,
                'lng' => $c->lng,
                'nume_contact' => null,
                'telefon' => $c->telefon,
                'produse' => $c->produse,
                'total' => $c->total(),
                'mod_plata' => 'Cash', // Comenzi rapide nu au mod_plata explicit; default
                'observatii' => $c->observatii,
                'livrat' => (bool) $c->livrat,
                'achitat' => (bool) $c->achitat,
                'sold' => ['19l' => 0, '11l' => 0],
                'are_recipienti' => false, // nu au id_adresa, nu putem inregistra recipienti
                'nr19l_comanda' => (int) $c->produse->where('id_produs', 45)->sum('cantitate'),
                'nr11l_comanda' => (int) $c->produse->where('id_produs', 46)->sum('cantitate'),
            ]);
        }

        $itemiUnificati = $itemiUnificati->sortBy(fn ($i) => [$i['ordine_traseu'] ?: 999999, $i['id']])->values();

        $sumarLei = $itemiUnificati->sum('total');
        $nrLivrate = $itemiUnificati->where('livrat', true)->count();
        $nr19l = $clasice->sum('nr_recipienti') + $rapide->sum(fn ($c) => $c->produse->where('id_produs', 45)->sum('cantitate'));
        $nr11l = $clasice->sum('nr_pahare') + $rapide->sum(fn ($c) => $c->produse->where('id_produs', 46)->sum('cantitate'));

        // Puncte pentru harta — DOAR comenzile NE-livrate cu GPS.
        // Pinul livrat dispare complet (regula stabilita cu user-ul).
        $culoareMasina = $masinaSofer?->culoare ?: '#3b82f6';
        $puncteHarta = [];
        foreach ($itemiUnificati as $i) {
            if ($i['livrat']) {
                continue; // pinul livrat dispare
            }
            if ($i['lat'] === null || $i['lng'] === null) {
                continue;
            }
            $puncteHarta[] = [
                'tip' => $i['tip'],
                'id' => $i['id'],
                'cheie' => $i['cheie'],
                'lat' => (float) $i['lat'],
                'lng' => (float) $i['lng'],
                'culoare' => $culoareMasina,
                'ordine' => $i['ordine_traseu'],
                'titlu' => $i['titlu'],
                'subtitlu' => $i['subtitlu'],
                'nr19l' => $i['nr19l_comanda'],
                'nr11l' => $i['nr11l_comanda'],
                'rapida' => $i['tip'] === self::TIP_RAPIDA,
            ];
        }

        return view('livewire.sofer.traseu', [
            'itemi' => $itemiUnificati,
            'sumarLei' => $sumarLei,
            'nrLivrate' => $nrLivrate,
            'nr19l' => $nr19l,
            'nr11l' => $nr11l,
            'soferAreMasina' => (bool) $idMasina,
            'puncteHarta' => $puncteHarta,
            'apiKey' => config('services.google_maps.key'),
        ]);
    }
}
