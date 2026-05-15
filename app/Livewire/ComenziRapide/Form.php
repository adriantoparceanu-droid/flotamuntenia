<?php

namespace App\Livewire\ComenziRapide;

use App\Models\Car;
use App\Models\ComandaRapida;
use App\Models\CostProduct;
use App\Models\Deposit;
use App\Services\MiscariStocService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $comandaId = null;

    // Date contact (text liber)
    public string $denumire = '';
    public string $adresa = '';
    public string $telefon = '';
    // GPS in UI: "lat, lng" copy/paste din Google Maps; parsat la save in cele 2 coloane.
    public string $gps = '';

    // Date livrare
    public string $dataLivrare = '';
    public ?int $idMasina = null;
    public ?int $idDepozit = null;

    public string $observatii = '';

    /** @var array<int, array{id_produs:?int, denumire:string, cantitate:int, pret:string}> */
    public array $linii = [];

    public function mount(?ComandaRapida $rapida = null): void
    {
        if ($rapida && $rapida->exists) {
            $this->incarca($rapida);
        } else {
            $this->dataLivrare = now()->toDateString();
            $this->idDepozit = Deposit::implicit()?->id;
            $this->adaugaLinieGoala();
        }
    }

    private function incarca(ComandaRapida $c): void
    {
        $c->loadMissing('produse.produs');
        $this->comandaId = $c->id;
        $this->denumire = $c->denumire;
        $this->adresa = $c->adresa ?? '';
        $this->telefon = $c->telefon ?? '';
        $this->gps = $c->areCoordonateGps()
            ? rtrim(rtrim((string) $c->lat, '0'), '.') . ', ' . rtrim(rtrim((string) $c->lng, '0'), '.')
            : '';
        $this->dataLivrare = $c->data_livrare?->format('Y-m-d') ?? '';
        $this->idMasina = $c->id_masina;
        $this->idDepozit = $c->id_depozit;
        $this->observatii = $c->observatii ?? '';

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
     * Auto-completare denumire + pret cand admin selecteaza produs din catalog.
     */
    public function updatedLinii($value, $key): void
    {
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
        if ((float) ($this->linii[$idx]['pret'] ?? 0) == 0.0) {
            $this->linii[$idx]['pret'] = (string) $produs->pret;
        }
    }

    public function totalCalculat(): float
    {
        $t = 0.0;
        foreach ($this->linii as $l) {
            $t += (int) ($l['cantitate'] ?? 0) * (float) ($l['pret'] ?? 0);
        }
        return $t;
    }

    public function salveaza(MiscariStocService $stocService)
    {
        $date = $this->validate([
            'denumire' => ['required', 'string', 'max:255'],
            'adresa' => ['nullable', 'string', 'max:500'],
            'telefon' => ['nullable', 'string', 'max:50'],
            'gps' => ['nullable', 'string', 'max:100'],
            'dataLivrare' => ['required', 'date'],
            'idMasina' => ['nullable', 'exists:cars,id'],
            'idDepozit' => ['nullable', 'exists:deposits,id'],
            'observatii' => ['nullable', 'string'],
            'linii' => ['required', 'array', 'min:1'],
            'linii.*.id_produs' => ['nullable', 'exists:cost_products,id'],
            'linii.*.denumire' => ['required_without:linii.*.id_produs', 'nullable', 'string', 'max:255'],
            'linii.*.cantitate' => ['required', 'integer', 'min:1'],
            'linii.*.pret' => ['required', 'numeric', 'min:0'],
        ], [
            'denumire.required' => 'Denumirea (numele clientului/punctului) e obligatorie.',
            'dataLivrare.required' => 'Data livrarii este obligatorie.',
            'linii.required' => 'Adauga cel putin un produs.',
            'linii.min' => 'Adauga cel putin un produs.',
            'linii.*.cantitate.min' => 'Cantitatea trebuie sa fie cel putin 1.',
        ]);

        [$lat, $lng] = $this->parseazaGps($date['gps'] ?? '');
        if ($this->getErrorBag()->has('gps')) {
            return;
        }

        $payload = [
            'id_masina' => $this->idMasina,
            'id_depozit' => $this->idDepozit,
            'denumire' => $date['denumire'],
            'adresa' => $date['adresa'] ?: null,
            'telefon' => $date['telefon'] ?: null,
            'lat' => $lat,
            'lng' => $lng,
            'data_livrare' => $date['dataLivrare'],
            'observatii' => $this->observatii ?: null,
        ];

        DB::transaction(function () use ($payload, $stocService) {
            if ($this->comandaId) {
                $comanda = ComandaRapida::findOrFail($this->comandaId);
                $comanda->update($payload);
                $comanda->produse()->delete();
            } else {
                $comanda = ComandaRapida::create($payload);
            }

            foreach ($this->linii as $l) {
                $comanda->produse()->create([
                    'id_produs' => $l['id_produs'] ?: $this->produsCustomFallback($l['denumire']),
                    'cantitate' => (int) $l['cantitate'],
                    'pret' => $l['pret'],
                ]);
            }

            $comanda->refresh();
            $stocService->sincronizeazaIesiriComandaRapida($comanda);

            $this->comandaId = $comanda->id;
        });

        session()->flash('mesaj', 'Comanda rapida salvata.');
        return redirect()->route('comenzi-rapide.index');
    }

    private function produsCustomFallback(string $denumire): int
    {
        $denumire = trim($denumire) ?: 'Linie comanda rapida';
        $produs = CostProduct::firstOrCreate(
            ['denumire' => $denumire],
            ['id_category' => $this->categorieFallbackId(), 'pret' => 0, 'activ' => true]
        );
        return $produs->id;
    }

    private function categorieFallbackId(): int
    {
        $cat = \App\Models\CostCategory::where('denumire', 'Apa imbuteliata')->first()
            ?? \App\Models\CostCategory::where('activ', true)->first();
        return (int) ($cat?->id ?? 1);
    }

    /**
     * Acelasi pattern ca pe adresa_livrare — parsare "lat, lng" in cele 2 coloane.
     *
     * @return array{0: float|null, 1: float|null}
     */
    private function parseazaGps(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [null, null];
        }
        if (! preg_match('/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/', $input, $m)) {
            $this->addError('gps', 'Format invalid. Foloseste "lat, lng" (ex: 44.4325, 26.1025).');
            return [null, null];
        }
        $lat = (float) $m[1];
        $lng = (float) $m[2];
        if ($lat < -90 || $lat > 90) {
            $this->addError('gps', 'Latitudinea trebuie sa fie intre -90 si 90.');
            return [null, null];
        }
        if ($lng < -180 || $lng > 180) {
            $this->addError('gps', 'Longitudinea trebuie sa fie intre -180 si 180.');
            return [null, null];
        }
        return [$lat, $lng];
    }

    public function render()
    {
        return view('livewire.comenzi-rapide.form', [
            'masini' => Car::where('activ', true)->orderBy('denumire')->get(),
            'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
            'produseCatalog' => CostProduct::where('activ', true)->orderBy('denumire')->get(),
            'totalCalculat' => $this->totalCalculat(),
        ]);
    }
}
