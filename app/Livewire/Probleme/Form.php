<?php

namespace App\Livewire\Probleme;

use App\Models\AdresaLivrare;
use App\Models\Car;
use App\Models\Client;
use App\Models\Deposit;
use App\Models\Problema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $problemaId = null;

    // Identificare
    public ?int $idClient = null;
    public string $cautareClient = '';
    public ?int $idAdresa = null;

    // Conținut intervenție
    public string $descriere = '';
    public string $suma = '0.00';
    public int $idModalitatePlata = Problema::MODPLATA_CASH;

    // Programare
    public string $dataLivrare = '';
    public string $intervalLivrare = '';

    // Asignare
    public ?int $idMasina = null;
    public ?int $idDepozit = null;

    // Contact override
    public string $nume = '';
    public string $telefon = '';

    public function mount(?Problema $problema = null): void
    {
        if ($problema && $problema->exists) {
            $this->incarcaProblema($problema);
        } else {
            $this->dataLivrare = now()->toDateString();

            // Pre-completare client din query string (de pe pagina detalii client).
            $idClient = (int) request()->query('id_client', 0);
            if ($idClient > 0 && Client::where('id', $idClient)->exists()) {
                $this->idClient = $idClient;
                $this->cautareClient = (string) Client::where('id', $idClient)->value('denumire');
            }
        }
    }

    private function incarcaProblema(Problema $p): void
    {
        $p->loadMissing('client');
        $this->problemaId = $p->id;
        $this->idClient = $p->id_client;
        $this->cautareClient = $p->client?->denumire ?? '';
        $this->idAdresa = $p->id_adresa;
        $this->descriere = $p->descriere ?? '';
        $this->suma = (string) ($p->suma ?? '0.00');
        $this->idModalitatePlata = (int) $p->id_modalitate_plata;
        $this->dataLivrare = $p->data_livrare?->format('Y-m-d') ?? '';
        $this->intervalLivrare = $p->interval_livrare ?? '';
        $this->idMasina = $p->id_masina;
        $this->idDepozit = $p->id_depozit;
        $this->nume = $p->nume ?? '';
        $this->telefon = $p->telefon ?? '';
    }

    public function updatedIdClient(): void
    {
        // Reset adresă când se schimbă clientul.
        $this->idAdresa = null;
    }

    public function updatedIdAdresa(): void
    {
        // La selectarea unei adrese, pre-completam contactul cu cel al clientului
        // (similar cu pattern-ul din Comenzi/Form, dar fără linii produse).
        if (! $this->idAdresa) {
            return;
        }
        $adresa = AdresaLivrare::find($this->idAdresa);
        if (! $adresa) {
            return;
        }
        $client = Client::find($adresa->id_client);
        if ($client && $this->nume === '' && $this->telefon === '') {
            $this->nume = $client->denumire ?? '';
            $this->telefon = $client->telefon ?? '';
        }
    }

    public function salveaza()
    {
        $this->validate([
            'idClient' => ['required', 'exists:clienti,id'],
            'idAdresa' => ['required', 'exists:adresa_livrare,id'],
            'descriere' => ['required', 'string', 'min:3'],
            'suma' => ['required', 'numeric', 'min:0'],
            'idModalitatePlata' => ['required', 'in:1,2,3,4'],
            'dataLivrare' => ['required', 'date'],
            'intervalLivrare' => ['nullable', 'string', 'max:50'],
            'idMasina' => ['nullable', 'exists:cars,id'],
            'idDepozit' => ['nullable', 'exists:deposits,id'],
            'nume' => ['nullable', 'string', 'max:255'],
            'telefon' => ['nullable', 'string', 'max:50'],
        ], [
            'idClient.required' => 'Selecteaza un client.',
            'idAdresa.required' => 'Selecteaza o adresa de livrare.',
            'descriere.required' => 'Descrierea problemei este obligatorie.',
            'descriere.min' => 'Descrierea trebuie sa aiba cel putin 3 caractere.',
            'suma.required' => 'Suma este obligatorie (0 daca interventia e gratuita).',
            'dataLivrare.required' => 'Data este obligatorie.',
        ]);

        $payload = [
            'id_client' => $this->idClient,
            'id_adresa' => $this->idAdresa,
            'id_masina' => $this->idMasina,
            'id_depozit' => $this->idDepozit,
            'descriere' => $this->descriere,
            'suma' => $this->suma,
            'id_modalitate_plata' => $this->idModalitatePlata,
            'data_livrare' => $this->dataLivrare,
            'interval_livrare' => $this->intervalLivrare ?: null,
            'nume' => $this->nume ?: null,
            'telefon' => $this->telefon ?: null,
        ];

        if ($this->problemaId) {
            $p = Problema::findOrFail($this->problemaId);
            $p->update($payload);
        } else {
            $p = Problema::create($payload);
            $this->problemaId = $p->id;
        }

        session()->flash('mesaj', 'Problema salvata.');

        // Daca am pornit din pagina de detalii client (?id_client=X) ne intoarcem
        // pe tab-ul probleme; altfel mergem in lista generala.
        $idClientQuery = (int) request()->query('id_client', 0);
        if ($idClientQuery > 0) {
            return redirect()->route('clienti.detalii', ['client' => $idClientQuery, 'tab' => 'probleme']);
        }
        return redirect()->route('probleme.index');
    }

    public function render()
    {
        // Cautare client (pattern identic cu Comenzi/Form).
        $clienti = collect();
        if (! $this->idClient) {
            $q = Client::where('reziliat', false);
            if ($this->cautareClient !== '') {
                $term = '%' . $this->cautareClient . '%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('denumire', 'like', $term)
                        ->orWhere('cod_client', 'like', $term)
                        ->orWhere('cif', 'like', $term);
                });
            }
            $clienti = $q->orderBy('denumire')->limit(15)->get();
        }

        $clientSelectat = $this->idClient ? Client::find($this->idClient) : null;
        $adrese = $this->idClient
            ? AdresaLivrare::where('id_client', $this->idClient)->where('activ', true)->orderBy('denumire')->get()
            : collect();

        return view('livewire.probleme.form', [
            'clientiCautare' => $clienti,
            'clientSelectat' => $clientSelectat,
            'adrese' => $adrese,
            'masini' => Car::where('activ', true)->orderBy('denumire')->get(),
            'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
        ]);
    }
}
