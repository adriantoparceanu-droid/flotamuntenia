<?php

namespace App\Livewire\Setari;

use App\Models\CostCategory;
use App\Models\CostProduct;
use App\Models\Tva as TvaModel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Catalog extends Component
{
    use WithPagination;

    #[Url(as: 'tab')]
    public string $tab = 'produse';

    #[Url(as: 'q')]
    public string $cautare = '';

    #[Url(as: 'inactive')]
    public bool $arataInactive = false;

    #[Url(as: 'cat')]
    public ?int $filtruCategorie = null;

    public bool $modalDeschis = false;
    public string $tipModal = ''; // 'categorie' | 'produs'

    // Camp form categorie
    public ?int $catId = null;
    public string $catDenumire = '';
    public bool $catActiv = true;

    // Camp form produs
    public ?int $prodId = null;
    public ?int $prodIdCategory = null;
    public ?int $prodIdTva = null;
    public string $prodDenumire = '';
    public string $prodPret = '0.00';
    public bool $prodActiv = true;

    public function updatingCautare(): void
    {
        $this->resetPage();
    }

    public function updatingArataInactive(): void
    {
        $this->resetPage();
    }

    public function updatingFiltruCategorie(): void
    {
        $this->resetPage();
    }

    public function comutaTab(string $tab): void
    {
        $this->tab = in_array($tab, ['produse', 'categorii'], true) ? $tab : 'produse';
        $this->resetPage();
    }

    // ===== Categorii =====

    public function categorieNoua(): void
    {
        $this->resetForm();
        $this->tipModal = 'categorie';
        $this->modalDeschis = true;
    }

    public function editeazaCategorie(int $id): void
    {
        $cat = CostCategory::findOrFail($id);
        $this->resetForm();
        $this->tipModal = 'categorie';
        $this->catId = $cat->id;
        $this->catDenumire = $cat->denumire;
        $this->catActiv = $cat->activ;
        $this->modalDeschis = true;
    }

    public function salveazaCategorie(): void
    {
        $date = $this->validate([
            'catDenumire' => 'required|string|max:255',
            'catActiv' => 'boolean',
        ], [
            'catDenumire.required' => 'Denumirea categoriei este obligatorie.',
        ]);

        CostCategory::updateOrCreate(
            ['id' => $this->catId],
            [
                'denumire' => $date['catDenumire'],
                'activ' => $date['catActiv'],
            ]
        );

        $this->modalDeschis = false;
        $this->resetForm();
        session()->flash('mesaj', 'Categorie salvata cu succes.');
    }

    public function comutaActivCategorie(int $id): void
    {
        $cat = CostCategory::findOrFail($id);
        $cat->activ = ! $cat->activ;
        $cat->save();
    }

    // ===== Produse =====

    public function produsNou(): void
    {
        $this->resetForm();
        $this->tipModal = 'produs';
        $this->prodIdCategory = $this->filtruCategorie;
        $this->modalDeschis = true;
    }

    public function editeazaProdus(int $id): void
    {
        $produs = CostProduct::findOrFail($id);
        $this->resetForm();
        $this->tipModal = 'produs';
        $this->prodId = $produs->id;
        $this->prodIdCategory = $produs->id_category;
        $this->prodIdTva = $produs->id_tva;
        $this->prodDenumire = $produs->denumire;
        $this->prodPret = (string) $produs->pret;
        $this->prodActiv = $produs->activ;
        $this->modalDeschis = true;
    }

    public function salveazaProdus(): void
    {
        $date = $this->validate([
            'prodDenumire' => 'required|string|max:255',
            'prodIdCategory' => 'required|exists:cost_categories,id',
            'prodIdTva' => 'nullable|exists:tva,id',
            'prodPret' => 'required|numeric|min:0',
            'prodActiv' => 'boolean',
        ], [
            'prodDenumire.required' => 'Denumirea produsului este obligatorie.',
            'prodIdCategory.required' => 'Selecteaza o categorie.',
            'prodPret.required' => 'Pretul este obligatoriu.',
            'prodPret.numeric' => 'Pretul trebuie sa fie un numar.',
        ]);

        CostProduct::updateOrCreate(
            ['id' => $this->prodId],
            [
                'denumire' => $date['prodDenumire'],
                'id_category' => $date['prodIdCategory'],
                'id_tva' => $date['prodIdTva'] ?? null,
                'pret' => $date['prodPret'],
                'activ' => $date['prodActiv'],
            ]
        );

        $this->modalDeschis = false;
        $this->resetForm();
        session()->flash('mesaj', 'Produs salvat cu succes.');
    }

    public function comutaActivProdus(int $id): void
    {
        $produs = CostProduct::findOrFail($id);
        $produs->activ = ! $produs->activ;
        $produs->save();
    }

    public function inchideModal(): void
    {
        $this->modalDeschis = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->tipModal = '';

        $this->catId = null;
        $this->catDenumire = '';
        $this->catActiv = true;

        $this->prodId = null;
        $this->prodIdCategory = null;
        $this->prodIdTva = null;
        $this->prodDenumire = '';
        $this->prodPret = '0.00';
        $this->prodActiv = true;

        $this->resetErrorBag();
    }

    public function render()
    {
        if ($this->tab === 'categorii') {
            $query = CostCategory::query();
            if ($this->cautare !== '') {
                $query->where('denumire', 'like', "%{$this->cautare}%");
            }
            if (! $this->arataInactive) {
                $query->where('activ', true);
            }
            $itemi = $query->withCount('produse')->orderBy('denumire')->paginate(15);
        } else {
            $query = CostProduct::query()->with(['categorie', 'tva']);
            if ($this->cautare !== '') {
                $query->where('denumire', 'like', "%{$this->cautare}%");
            }
            if ($this->filtruCategorie) {
                $query->where('id_category', $this->filtruCategorie);
            }
            if (! $this->arataInactive) {
                $query->where('activ', true);
            }
            $itemi = $query->orderBy('denumire')->paginate(15);
        }

        return view('livewire.setari.catalog', [
            'itemi' => $itemi,
            'categorii' => CostCategory::orderBy('denumire')->get(),
            'cote' => TvaModel::where('activ', true)->orderBy('valoare', 'desc')->get(),
        ]);
    }
}
