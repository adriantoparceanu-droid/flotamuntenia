<?php

namespace App\Livewire\Comenzi;

use App\Models\AdresaLivrare;
use App\Models\Car;
use App\Models\Client;
use App\Models\Comanda;
use App\Models\CostProduct;
use App\Models\Deposit;
use App\Models\Produs;
use App\Services\Facturare\FacturareException;
use App\Services\Facturare\FacturareService;
use App\Services\MailService;
use App\Services\MiscariStocService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $comandaId = null;

    // ===== Identificare =====
    public ?int $idClient = null;
    public string $cautareClient = '';
    public ?int $idAdresa = null;

    // ===== Tip + plata + data =====
    public string $tipComanda = Comanda::TIP_FARA_ABONAMENT;
    public int $idModalitatePlata = Comanda::MODPLATA_CASH;
    public string $dataLivrare = '';
    public string $intervalLivrare = '';
    public string $lunaLivrata = '';

    // ===== Asignare =====
    public ?int $idMasina = null;
    public ?int $idDepozit = null;

    // ===== Contact + observatii =====
    public string $nume = '';
    public string $telefon = '';
    public string $observatii = '';

    // ===== Linii produse =====
    // Fiecare linie: ['id_produs' => int|null, 'denumire' => string, 'cantitate' => int, 'pret' => string]
    // Folosim 'denumire' separat pentru a permite linii custom (ex: denumire abonament).
    public array $linii = [];

    // ===== Aprobare (Faza 3.3) =====
    // Cand admin vine pe form via /comenzi/{id}/editare?aprobare=1 (din pagina de
    // aprobare comenzi portal), butonul de save devine "Salveaza & aproba" si
    // dupa salvare seteaza status=NULL + trimite email confirmare.
    public bool $aprobaLaSalvare = false;
    public bool $eraInAsteptare = false;

    public function mount(?Comanda $comanda = null): void
    {
        if ($comanda && $comanda->exists) {
            $this->incarcaComanda($comanda->id);
            $this->aprobaLaSalvare = request()->boolean('aprobare') && $this->eraInAsteptare;
        } else {
            $this->dataLivrare = now()->toDateString();
            $this->lunaLivrata = now()->format('Y/m');
            $this->adaugaLinieGoala();

            // Faza 5.4 — pre-fill din query string cand admin vine din raportul
            // de abonamente lipsa: ?id_adresa=X&tip=abonament&luna=YYYY-MM
            $idAdresaQuery = (int) request()->query('id_adresa', 0);
            if ($idAdresaQuery > 0) {
                $adresa = AdresaLivrare::find($idAdresaQuery);
                if ($adresa) {
                    // Setam clientul + adresa, apoi declansam updatedIdAdresa
                    // pentru prefill nume/telefon/produse din configurarea adresei
                    $this->idClient = $adresa->id_client;
                    $this->cautareClient = $adresa->client?->denumire ?? '';
                    $this->idAdresa = $idAdresaQuery;
                    $this->updatedIdAdresa();

                    // Reasigurare tip 'abonament' (override-ul auto-detect din
                    // updatedIdAdresa pentru cazul filtre care nu seteaza tipComanda)
                    if (request()->query('tip') === Comanda::TIP_ABONAMENT) {
                        $this->tipComanda = Comanda::TIP_ABONAMENT;
                    }
                }
            }

            $lunaQuery = (string) request()->query('luna', '');
            if ($lunaQuery !== '') {
                // URL: YYYY-MM, DB: YYYY/MM
                $this->lunaLivrata = str_replace('-', '/', $lunaQuery);
                // Aliniem si data_livrare la prima zi a lunii alese pentru
                // a evita confuzia (default era now()).
                if (preg_match('/^(\d{4})\/(\d{2})$/', $this->lunaLivrata, $m)) {
                    $this->dataLivrare = $m[1] . '-' . $m[2] . '-01';
                }
            }
        }
    }

    private function incarcaComanda(int $id): void
    {
        $c = Comanda::with('produse.produs', 'client', 'adresa')->findOrFail($id);
        $this->comandaId = $c->id;
        $this->idClient = $c->id_client;
        $this->cautareClient = $c->client?->denumire ?? '';
        $this->idAdresa = $c->id_adresa;
        $this->tipComanda = $c->tip_comanda;
        $this->idModalitatePlata = (int) $c->id_modalitate_plata;
        $this->dataLivrare = $c->data_livrare?->format('Y-m-d') ?? '';
        $this->intervalLivrare = $c->interval_livrare ?? '';
        $this->lunaLivrata = $c->luna_livrata ?? '';
        $this->idMasina = $c->id_masina;
        $this->idDepozit = $c->id_depozit;
        $this->nume = $c->nume ?? '';
        $this->telefon = $c->telefon ?? '';
        $this->observatii = $c->observatii ?? '';
        $this->eraInAsteptare = $c->isInAsteptare();

        $this->linii = $c->produse->map(fn ($l) => [
            'id_produs' => $l->id_produs,
            'denumire' => $l->produs?->denumire ?? '',
            'cantitate' => (int) $l->cantitate,
            'pret' => (string) $l->pret,
        ])->values()->all();

        if (empty($this->linii)) {
            $this->adaugaLinieGoala();
        }
    }

    /**
     * Cand admin schimba data, propagam automat luna_livrata daca era goala
     * sau identica cu vechea valoare auto-completata. Nu suprascriem o luna
     * setata manual de admin.
     */
    public function updatedDataLivrare(): void
    {
        if ($this->dataLivrare === '') {
            return;
        }
        try {
            $luna = \Carbon\Carbon::parse($this->dataLivrare)->format('Y/m');
            // Auto-completam doar daca nu e setat sau e gol
            if ($this->lunaLivrata === '') {
                $this->lunaLivrata = $luna;
            }
        } catch (\Throwable $e) {
            // ignor
        }
    }

    /**
     * Cand admin selecteaza/schimba adresa, suprascrie complet configurarea
     * comenzii cu cea salvata pe noua adresa (tabela `produs`). User-ul a
     * cerut explicit ca la fiecare schimbare de adresa sa se aduca datele
     * de pe acea adresa — chiar daca asta inseamna ca pierdem modificari
     * manuale facute intre timp.
     */
    public function updatedIdAdresa(): void
    {
        if (! $this->idAdresa) {
            // Adresa golita — golim si liniile/asignarea legate de ea
            $this->resetCampuriDinAdresa();
            return;
        }

        $adresa = AdresaLivrare::with('produs')->find($this->idAdresa);
        if (! $adresa) {
            return;
        }

        // Suprascriem CONTACT cu datele clientului noii adrese
        $client = Client::find($adresa->id_client);
        if ($client) {
            $this->nume = $client->denumire ?? '';
            $this->telefon = $client->telefon ?? '';
        }
        // Observatii adresa — suprascriem cu cele specifice noii adrese
        $this->observatii = $adresa->observatii ?? '';

        $produs = $adresa->produs;

        if (! $produs) {
            // Adresa nu are configurare — golim asignarea + linii la default
            $this->idMasina = null;
            $this->idDepozit = null;
            $this->tipComanda = Comanda::TIP_FARA_ABONAMENT;
            $this->linii = [[
                'id_produs' => null,
                'denumire' => '',
                'cantitate' => 1,
                'pret' => '0.00',
            ]];
            return;
        }

        // Suprascriem asignarea default cu cea de pe noua adresa
        $this->idMasina = $produs->id_masina;
        $this->idDepozit = $produs->id_depozit;

        // Suprascriem tip + linii in functie de tipul configuratiei
        if ($produs->isAbonament()) {
            $this->tipComanda = Comanda::TIP_ABONAMENT;
            $this->linii = [[
                'id_produs' => null, // linie custom — denumirea abonamentului
                'denumire' => $produs->denumire_abonament ?: 'Abonament lunar',
                'cantitate' => 1,
                'pret' => (string) $produs->pret,
            ]];
        } elseif ($produs->abonament === Produs::TIP_PER_BUCATA) {
            $this->tipComanda = Comanda::TIP_FARA_ABONAMENT;
            $linii = [];
            $produs19l = CostProduct::find(45);
            $produs11l = CostProduct::find(46);
            if ($produs19l) {
                $linii[] = [
                    'id_produs' => 45,
                    'denumire' => $produs19l->denumire,
                    'cantitate' => 0,
                    'pret' => (string) $produs->pret,
                ];
            }
            if ($produs11l) {
                $linii[] = [
                    'id_produs' => 46,
                    'denumire' => $produs11l->denumire,
                    'cantitate' => 0,
                    'pret' => (string) $produs->pret_11l,
                ];
            }
            $this->linii = ! empty($linii) ? $linii : [[
                'id_produs' => null,
                'denumire' => '',
                'cantitate' => 1,
                'pret' => '0.00',
            ]];
        } else {
            // FILTRE / APARATE — fara linii pre-completate, dar pastram tipul
            $this->tipComanda = Comanda::TIP_FARA_ABONAMENT;
            $this->linii = [[
                'id_produs' => null,
                'denumire' => '',
                'cantitate' => 1,
                'pret' => '0.00',
            ]];
        }
    }

    /**
     * Reseteaza campurile dependente de adresa cand adresa e golita.
     */
    private function resetCampuriDinAdresa(): void
    {
        $this->idMasina = null;
        $this->idDepozit = null;
        $this->nume = '';
        $this->telefon = '';
        $this->observatii = '';
        $this->linii = [[
            'id_produs' => null,
            'denumire' => '',
            'cantitate' => 1,
            'pret' => '0.00',
        ]];
    }

    private function linieEsteGoala(array $l): bool
    {
        // O linie e considerata "neatinsa" daca admin nu a selectat un produs
        // si nici nu a tastat o denumire. Cantitatea/pretul default (1, 0.00)
        // sunt rezultatul `adaugaLinieGoala()`, nu un input al admin-ului.
        return empty($l['id_produs'])
            && trim((string) ($l['denumire'] ?? '')) === '';
    }

    /**
     * Reset adresa cand se schimba clientul.
     */
    public function updatedIdClient(): void
    {
        $this->idAdresa = null;
    }

    public function adaugaLinieGoala(): void
    {
        $this->linii[] = [
            'id_produs' => null,
            'denumire' => '',
            'cantitate' => 1,
            'pret' => '0.00',
        ];
    }

    public function eliminaLinie(int $index): void
    {
        if (isset($this->linii[$index])) {
            unset($this->linii[$index]);
            $this->linii = array_values($this->linii);
        }
        if (empty($this->linii)) {
            $this->adaugaLinieGoala();
        }
    }

    /**
     * Cand admin selecteaza un produs din catalog, prefill denumirea + pretul standard.
     */
    public function updatedLinii($value, $key): void
    {
        // $key e ceva de genul "0.id_produs" sau "0.cantitate"
        if (! str_contains((string) $key, '.id_produs')) {
            return;
        }
        $idx = (int) explode('.', $key)[0];
        $idProdus = $this->linii[$idx]['id_produs'] ?? null;
        if (! $idProdus) {
            return;
        }
        $produs = CostProduct::find($idProdus);
        if (! $produs) {
            return;
        }
        $this->linii[$idx]['denumire'] = $produs->denumire;
        // Doar daca pretul e 0 — sa nu suprascriem o valoare introdusa de admin
        if ((float) ($this->linii[$idx]['pret'] ?? 0) == 0.0) {
            $this->linii[$idx]['pret'] = (string) $produs->pret;
        }
    }

    public function totalCalculat(): float
    {
        $total = 0.0;
        foreach ($this->linii as $l) {
            $total += (int) ($l['cantitate'] ?? 0) * (float) ($l['pret'] ?? 0);
        }
        return $total;
    }

    public function salveaza(MiscariStocService $stocService)
    {
        $reguli = [
            'idClient' => ['required', 'exists:clienti,id'],
            'idAdresa' => ['required', 'exists:adresa_livrare,id'],
            'tipComanda' => ['required', 'in:abonament,consum suplimentar,fara abonament'],
            'idModalitatePlata' => ['required', 'in:1,2,3,4'],
            'dataLivrare' => ['required', 'date'],
            'intervalLivrare' => ['nullable', 'string', 'max:50'],
            'idMasina' => ['nullable', 'exists:cars,id'],
            'idDepozit' => ['nullable', 'exists:deposits,id'],
            'nume' => ['nullable', 'string', 'max:255'],
            'telefon' => ['nullable', 'string', 'max:50'],
            'observatii' => ['nullable', 'string'],
            'linii' => ['required', 'array', 'min:1'],
            'linii.*.denumire' => ['required_without:linii.*.id_produs', 'nullable', 'string', 'max:255'],
            'linii.*.id_produs' => ['nullable', 'exists:cost_products,id'],
            'linii.*.cantitate' => ['required', 'integer', 'min:1'],
            'linii.*.pret' => ['required', 'numeric', 'min:0'],
        ];

        // luna_livrata obligatorie pe abonament (regula §8.4)
        if ($this->tipComanda === Comanda::TIP_ABONAMENT) {
            $reguli['lunaLivrata'] = ['required', 'regex:/^\d{4}\/(0[1-9]|1[0-2])$/'];
        } else {
            $reguli['lunaLivrata'] = ['nullable', 'regex:/^\d{4}\/(0[1-9]|1[0-2])$/'];
        }

        $this->validate($reguli, [
            'idClient.required' => 'Selecteaza un client.',
            'idAdresa.required' => 'Selecteaza o adresa de livrare.',
            'dataLivrare.required' => 'Data livrarii este obligatorie.',
            'lunaLivrata.required' => 'Luna livrata (YYYY/MM) este obligatorie pentru comenzile de tip abonament.',
            'lunaLivrata.regex' => 'Format invalid. Foloseste YYYY/MM (ex: 2026/05).',
            'linii.required' => 'Adauga cel putin un produs.',
            'linii.min' => 'Adauga cel putin un produs.',
            'linii.*.denumire.required_without' => 'Denumirea liniei e obligatorie cand nu selectezi un produs din catalog.',
            'linii.*.cantitate.required' => 'Cantitatea e obligatorie pe fiecare linie.',
            'linii.*.cantitate.min' => 'Cantitatea trebuie sa fie cel putin 1.',
        ]);

        // Calcul cantitati 19L/11L (denormalizate la nivel de comanda)
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
            'id_client' => $this->idClient,
            'id_adresa' => $this->idAdresa,
            'id_masina' => $this->idMasina,
            'id_depozit' => $this->idDepozit,
            'tip_comanda' => $this->tipComanda,
            'nr_recipienti' => $nr19l,
            'nr_pahare' => $nr11l,
            'id_modalitate_plata' => $this->idModalitatePlata,
            'data_livrare' => $this->dataLivrare,
            'interval_livrare' => $this->intervalLivrare ?: null,
            'luna_livrata' => $this->lunaLivrata ?: null,
            'nume' => $this->nume ?: null,
            'telefon' => $this->telefon ?: null,
            'observatii' => $this->observatii ?: null,
        ];

        // Daca admin a venit din fluxul de aprobare (?aprobare=1), payload-ul
        // marcheaza comanda ca aprobata: status=NULL + audit cine a aprobat.
        if ($this->aprobaLaSalvare && $this->eraInAsteptare) {
            $payload['status'] = null;
            $payload['aprobat_de'] = auth()->id();
        }

        DB::transaction(function () use ($payload, $stocService) {
            if ($this->comandaId) {
                $comanda = Comanda::findOrFail($this->comandaId);
                $comanda->update($payload);
                $comanda->produse()->delete();
            } else {
                $comanda = Comanda::create($payload);
            }

            foreach ($this->linii as $l) {
                $comanda->produse()->create([
                    'id_produs' => $l['id_produs'] ?: $this->produsCustomFallback($l['denumire']),
                    'cantitate' => (int) $l['cantitate'],
                    'pret' => $l['pret'],
                ]);
            }

            $comanda->refresh();
            $stocService->sincronizeazaIesiriComanda($comanda);

            $this->comandaId = $comanda->id;
        });

        // Email confirmare aprobare (dupa commit, cu modelul refresh-uit)
        if ($this->aprobaLaSalvare && $this->eraInAsteptare && $this->comandaId) {
            $comanda = Comanda::with('client')->find($this->comandaId);
            MailService::send('comanda_aprobata', $comanda?->client?->email, [
                'client' => $comanda?->client?->denumire,
                'cod_comanda' => $comanda?->id,
                'data_livrare' => $comanda?->data_livrare?->format('d.m.Y'),
                'interval' => $comanda?->interval_livrare,
                'total' => $comanda?->total(),
            ]);
            session()->flash('mesaj', "Comanda #{$this->comandaId} a fost aprobata. Email confirmare trimis.");
            return redirect()->route('comenzi.aprobare');
        }

        session()->flash('mesaj', 'Comanda salvata cu succes.');

        return redirect()->route('comenzi.index');
    }

    /**
     * Pentru linii custom (ex: linia "Abonament lunar Pachet Standard") nu
     * avem un id_produs din catalog. Creem/refolosim un produs generic in
     * cost_products pentru a respecta FK-ul. ID-urile dedicate (45/46/47/52/55)
     * raman fixate; produsul generic este creat la cerere si reutilizat.
     */
    private function produsCustomFallback(string $denumire): int
    {
        // Cautam un produs generic "Abonament" deja creat sau il cream.
        $denumire = trim($denumire) ?: 'Linie comanda';
        $produs = CostProduct::firstOrCreate(
            ['denumire' => $denumire],
            ['id_category' => $this->categorieAbonamentId(), 'pret' => 0, 'activ' => true]
        );
        return $produs->id;
    }

    private function categorieAbonamentId(): int
    {
        // Folosim categoria "Apa imbuteliata" daca exista; altfel prima categorie activa
        $cat = \App\Models\CostCategory::where('denumire', 'Apa imbuteliata')->first()
            ?? \App\Models\CostCategory::where('activ', true)->first();
        return (int) ($cat?->id ?? 1);
    }

    /**
     * Faza 6.1 — Emite factura electronica pentru comanda curenta prin
     * furnizorul activ (Oblio/SmartBill). Persisteaza serie/numar/link/furnizor
     * pe comanda si seteaza invoice_generated=true.
     *
     * Butonul e vizibil DOAR dupa salvarea comenzii (cand exista comandaId)
     * si daca nu exista deja o factura emisa.
     */
    public function emiteFactura(): void
    {
        if (! $this->comandaId) {
            session()->flash('eroare', 'Salveaza comanda inainte de a emite factura.');
            return;
        }

        $comanda = Comanda::with(['client', 'adresa', 'produse.produs.tva'])->find($this->comandaId);
        if (! $comanda) {
            session()->flash('eroare', 'Comanda nu mai exista.');
            return;
        }
        if ($comanda->invoice_generated) {
            session()->flash('eroare', 'Factura a fost deja emisa pentru aceasta comanda.');
            return;
        }

        try {
            $rezultat = FacturareService::emiteSiPersisteaza($comanda);
            $serie = $rezultat['serie'] ?? '';
            $numar = $rezultat['numar'] ?? '';
            session()->flash('mesaj', "Factura emisa cu succes: {$serie}-{$numar}.");
        } catch (FacturareException $e) {
            session()->flash('eroare', 'Eroare emitere factura: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Clienti vizibili in dropdown-ul de cautare. Afisam mereu o lista
        // (limitata la 15 inregistrari) ca admin sa poata alege fara sa fie
        // nevoit sa tasteze ceva — util mai ales cand sunt putini clienti.
        // Cand admin tasteaza, lista se restrange la potriviri.
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

        // Status facturare pentru comanda existenta (Faza 6.1).
        $comandaPersistata = $this->comandaId ? Comanda::find($this->comandaId) : null;
        $furnizorActivConfigurat = \App\Models\FacturareSetari::activ()?->esteConfigurat() ?? false;

        return view('livewire.comenzi.form', [
            'clientiCautare' => $clienti,
            'clientSelectat' => $clientSelectat,
            'adrese' => $adrese,
            'masini' => Car::where('activ', true)->orderBy('denumire')->get(),
            'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
            'produseCatalog' => CostProduct::where('activ', true)->with('categorie')->orderBy('denumire')->get(),
            'totalCalculat' => $this->totalCalculat(),
            'comandaPersistata' => $comandaPersistata,
            'furnizorActivConfigurat' => $furnizorActivConfigurat,
        ]);
    }
}
