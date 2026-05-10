<?php

namespace App\Livewire\Cheltuieli;

use App\Models\Cheltuiala;
use App\Models\CostProduct;
use App\Models\Deposit;
use App\Services\MiscariStocService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Faza 5.1 — Form pentru factura de cheltuieli (pattern identic cu Comenzi\Form).
 *
 * - Linii dinamice cu add/remove
 * - Total auto-calculat read-only (sum cantitate × pret)
 * - Furnizor input cu datalist HTML5 populat din furnizori distincti anteriori
 * - La salvare: revert+recreate mişcari de stoc IN prin MiscariStocService
 */
#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $cheltuialaId = null;

    public string $nrFactura = '';
    public string $furnizor = '';
    public ?int $idDepozit = null;
    public string $data = '';
    public bool $achitat = false;
    public string $observatii = '';

    /**
     * Linii produse: ['id_produs' => int|null, 'denumire' => string, 'cantitate' => int, 'pret' => string]
     * Pentru cheltuieli, `id_produs` e obligatoriu pentru fiecare linie cu cantitate > 0
     * (altfel mişcarea de stoc ar fi orfana de produs).
     */
    public array $linii = [];

    public function mount(?Cheltuiala $cheltuiala = null): void
    {
        if ($cheltuiala && $cheltuiala->exists) {
            $this->incarcaCheltuiala($cheltuiala->id);
        } else {
            $this->data = now()->toDateString();
            $this->adaugaLinieGoala();
        }
    }

    private function incarcaCheltuiala(int $id): void
    {
        $c = Cheltuiala::with('produse.produs')->findOrFail($id);
        $this->cheltuialaId = $c->id;
        $this->nrFactura = $c->nr_factura;
        $this->furnizor = $c->furnizor;
        $this->idDepozit = $c->id_depozit;
        $this->data = $c->data?->format('Y-m-d') ?? '';
        $this->achitat = (bool) $c->achitat;
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
     * Cand admin selecteaza un produs din catalog, prefill denumirea + pretul standard.
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
        $total = 0.0;
        foreach ($this->linii as $l) {
            $total += (int) ($l['cantitate'] ?? 0) * (float) ($l['pret'] ?? 0);
        }
        return $total;
    }

    public function salveaza(MiscariStocService $stocService)
    {
        $this->validate([
            'nrFactura' => ['required', 'string', 'max:100'],
            'furnizor' => ['required', 'string', 'max:255'],
            'idDepozit' => ['required', 'exists:deposits,id'],
            'data' => ['required', 'date'],
            'observatii' => ['nullable', 'string'],
            'linii' => ['required', 'array', 'min:1'],
            'linii.*.id_produs' => ['required', 'exists:cost_products,id'],
            'linii.*.cantitate' => ['required', 'integer', 'min:1'],
            'linii.*.pret' => ['required', 'numeric', 'min:0'],
        ], [
            'nrFactura.required' => 'Numarul facturii este obligatoriu.',
            'furnizor.required' => 'Furnizorul este obligatoriu.',
            'idDepozit.required' => 'Selecteaza depozitul destinatie.',
            'data.required' => 'Data facturii este obligatorie.',
            'linii.required' => 'Adauga cel putin o linie de produs.',
            'linii.min' => 'Adauga cel putin o linie de produs.',
            'linii.*.id_produs.required' => 'Selecteaza produsul din catalog pe fiecare linie.',
            'linii.*.cantitate.required' => 'Cantitatea e obligatorie pe fiecare linie.',
            'linii.*.cantitate.min' => 'Cantitatea trebuie sa fie cel putin 1.',
            'linii.*.pret.required' => 'Pretul e obligatoriu pe fiecare linie.',
        ]);

        $payload = [
            'nr_factura' => $this->nrFactura,
            'furnizor' => $this->furnizor,
            'id_depozit' => $this->idDepozit,
            'data' => $this->data,
            'achitat' => $this->achitat,
            'observatii' => $this->observatii ?: null,
            'total' => $this->totalCalculat(), // auto-calculat la salvare
        ];

        $cheltuiala = DB::transaction(function () use ($payload, $stocService) {
            if ($this->cheltuialaId) {
                $c = Cheltuiala::findOrFail($this->cheltuialaId);
                $c->update($payload);
                $c->produse()->delete(); // recreate clean
            } else {
                $c = Cheltuiala::create($payload);
                $this->cheltuialaId = $c->id;
            }

            foreach ($this->linii as $l) {
                $c->produse()->create([
                    'id_produs' => $l['id_produs'],
                    'cantitate' => (int) $l['cantitate'],
                    'pret' => (float) $l['pret'],
                ]);
            }

            $c->refresh();
            $stocService->sincronizeazaIntrariCheltuiala($c);

            return $c;
        });

        session()->flash('mesaj', 'Factura salvata. Mişcarile de stoc IN au fost generate.');
        return redirect()->route('cheltuieli.index');
    }

    public function render()
    {
        // Furnizori distincti pentru datalist autocomplete
        $furnizoriSugerati = Cheltuiala::query()
            ->select('furnizor')
            ->distinct()
            ->orderBy('furnizor')
            ->limit(100)
            ->pluck('furnizor');

        return view('livewire.cheltuieli.form', [
            'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
            'produse' => CostProduct::where('activ', true)->orderBy('denumire')->get(),
            'furnizoriSugerati' => $furnizoriSugerati,
            'totalCalculat' => $this->totalCalculat(),
        ]);
    }
}
