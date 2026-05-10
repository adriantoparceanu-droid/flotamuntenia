<?php

namespace App\Livewire\Clienti;

use App\Models\Client;
use App\Services\AnafService;
use App\Support\CifValidator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Client $client = null;

    // Tip: 1 = PJ, 2 = PF
    public int $tip = Client::TIP_PJ;

    public string $cod_client = '';
    public string $denumire = '';
    public string $cif = '';
    public string $reg_com = '';

    // Adresa sediu
    public string $oras = '';
    public string $strada = '';
    public string $nr = '';
    public string $bloc = '';
    public string $scara = '';
    public string $etaj = '';
    public string $apartament = '';
    public string $sector = '';
    public string $interfon = '';

    // Contact
    public string $email = '';
    public string $telefon = '';

    public string $observatii = '';

    public function mount(?Client $client = null): void
    {
        if ($client && $client->exists) {
            $this->client = $client;
            $this->tip = $client->client;
            $this->cod_client = $client->cod_client;
            $this->denumire = $client->denumire;
            $this->cif = $client->cif ?? '';
            $this->reg_com = $client->reg_com ?? '';
            $this->oras = $client->oras ?? '';
            $this->strada = $client->strada ?? '';
            $this->nr = $client->nr ?? '';
            $this->bloc = $client->bloc ?? '';
            $this->scara = $client->scara ?? '';
            $this->etaj = $client->etaj ?? '';
            $this->apartament = $client->apartament ?? '';
            $this->sector = $client->sector ?? '';
            $this->interfon = $client->interfon ?? '';
            $this->email = $client->email ?? '';
            $this->telefon = $client->telefon ?? '';
            $this->observatii = $client->observatii ?? '';
        }
    }

    protected function rules(): array
    {
        $idIgnore = $this->client?->id ?? 'NULL';

        return [
            'tip' => ['required', 'in:' . Client::TIP_PJ . ',' . Client::TIP_PF],
            'cod_client' => ['nullable', 'string', 'max:50', 'unique:clienti,cod_client,' . $idIgnore],
            'denumire' => ['required', 'string', 'max:255'],
            // CIF obligatoriu doar pentru PJ; pentru PF e CNP optional.
            'cif' => [
                $this->tip === Client::TIP_PJ ? 'required' : 'nullable',
                'string',
                'max:20',
                'unique:clienti,cif,' . $idIgnore,
            ],
            'reg_com' => ['nullable', 'string', 'max:50'],
            'oras' => ['nullable', 'string', 'max:100'],
            'strada' => ['nullable', 'string', 'max:255'],
            'nr' => ['nullable', 'string', 'max:20'],
            'bloc' => ['nullable', 'string', 'max:20'],
            'scara' => ['nullable', 'string', 'max:10'],
            'etaj' => ['nullable', 'string', 'max:10'],
            'apartament' => ['nullable', 'string', 'max:20'],
            'sector' => ['nullable', 'string', 'max:20'],
            'interfon' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefon' => ['nullable', 'string', 'max:20'],
            'observatii' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'tip.required' => 'Selecteaza tipul (PJ sau PF).',
            'denumire.required' => 'Denumirea este obligatorie.',
            'cif.required' => 'CIF-ul este obligatoriu pentru persoane juridice.',
            'cif.unique' => 'Mai exista un client cu acest CIF/CNP.',
            'cod_client.unique' => 'Codul de client este deja folosit.',
            'email.email' => 'Adresa de email este invalida.',
        ];
    }

    /**
     * Faza 6.6 — Completare automata date PJ din CIF prin ANAF.
     *
     * Pasi: 1) validare format + checksum CIF; 2) apel AnafService cu cache 24h;
     * 3) suprascriere campuri (denumire, reg_com, oras, strada, nr, sector) +
     * flash mesaj. Comportament: intotdeauna suprascrie (decizia user-ului 6.6).
     *
     * Vizibil doar pentru tip=PJ in UI; aici facem dublu check defensiv.
     */
    public function completeazaDinAnaf(AnafService $anaf): void
    {
        if ($this->tip !== Client::TIP_PJ) {
            session()->flash('eroare', 'Completarea din ANAF e disponibila doar pentru persoane juridice.');
            return;
        }

        if (! CifValidator::esteValid($this->cif)) {
            session()->flash('eroare', 'CIF-ul introdus nu este valid (verifica cifrele si checksum-ul).');
            return;
        }

        $date = $anaf->cautaCif($this->cif);
        if (! $date) {
            session()->flash('eroare', 'Firma nu a fost gasita in ANAF sau serviciul ANAF nu raspunde momentan.');
            return;
        }

        $this->denumire = $date['denumire'];
        $this->cif = $date['cif']; // formatul normalizat (fara prefix RO)
        $this->reg_com = $date['reg_com'];
        $this->oras = $date['oras'];
        $this->strada = $date['strada'];
        $this->nr = $date['nr'];
        $this->sector = $date['sector'];

        session()->flash('mesaj', "Datele au fost completate din ANAF: {$date['denumire']}.");
    }

    public function salveaza()
    {
        $date = $this->validate();

        // Auto-generare cod_client la creare daca user-ul l-a lasat gol.
        if (empty($date['cod_client'])) {
            $next = (Client::max('id') ?? 0) + 1;
            $date['cod_client'] = 'C-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        }

        // Mapam 'tip' -> 'client' (numele coloanei in DB)
        $payload = [
            'client' => $date['tip'],
            'cod_client' => $date['cod_client'],
            'denumire' => $date['denumire'],
            'cif' => $date['cif'] ?: null,
            'reg_com' => $date['reg_com'] ?: null,
            'oras' => $date['oras'] ?: null,
            'strada' => $date['strada'] ?: null,
            'nr' => $date['nr'] ?: null,
            'bloc' => $date['bloc'] ?: null,
            'scara' => $date['scara'] ?: null,
            'etaj' => $date['etaj'] ?: null,
            'apartament' => $date['apartament'] ?: null,
            'sector' => $date['sector'] ?: null,
            'interfon' => $date['interfon'] ?: null,
            'email' => $date['email'] ?: null,
            'telefon' => $date['telefon'] ?: null,
            'observatii' => $date['observatii'] ?: null,
        ];

        if ($this->client) {
            $this->client->update($payload);
            $client = $this->client;
            $mesaj = 'Client actualizat cu succes.';
        } else {
            $client = Client::create($payload);
            $mesaj = 'Client adaugat cu succes.';
        }

        session()->flash('mesaj', $mesaj);

        return $this->redirectRoute('clienti.detalii', ['client' => $client->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.clienti.form');
    }
}
