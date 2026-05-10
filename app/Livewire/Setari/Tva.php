<?php

namespace App\Livewire\Setari;

use App\Models\Tva as TvaModel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Tva extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $cautare = '';

    #[Url(as: 'inactive')]
    public bool $arataInactive = false;

    public bool $modalDeschis = false;

    public ?int $editandId = null;
    public string $valoare = '';
    public string $denumire = '';
    public bool $activ = true;

    protected function rules(): array
    {
        return [
            'valoare' => 'required|numeric|min:0|max:100',
            'denumire' => 'required|string|max:50',
            'activ' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'valoare.required' => 'Cota este obligatorie.',
            'valoare.numeric' => 'Cota trebuie sa fie un numar.',
            'denumire.required' => 'Denumirea este obligatorie.',
        ];
    }

    public function updatingCautare(): void
    {
        $this->resetPage();
    }

    public function updatingArataInactive(): void
    {
        $this->resetPage();
    }

    public function nou(): void
    {
        $this->resetForm();
        $this->modalDeschis = true;
    }

    public function editeaza(int $id): void
    {
        $tva = TvaModel::findOrFail($id);
        $this->editandId = $tva->id;
        $this->valoare = (string) $tva->valoare;
        $this->denumire = $tva->denumire;
        $this->activ = $tva->activ;
        $this->modalDeschis = true;
    }

    public function salveaza(): void
    {
        $date = $this->validate();

        TvaModel::updateOrCreate(
            ['id' => $this->editandId],
            $date
        );

        $this->modalDeschis = false;
        $this->resetForm();
        session()->flash('mesaj', 'Cota TVA salvata cu succes.');
    }

    public function comutaActiv(int $id): void
    {
        $tva = TvaModel::findOrFail($id);
        $tva->activ = ! $tva->activ;
        $tva->save();
    }

    public function inchideModal(): void
    {
        $this->modalDeschis = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editandId = null;
        $this->valoare = '';
        $this->denumire = '';
        $this->activ = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = TvaModel::query();

        if ($this->cautare !== '') {
            $query->where(function ($q) {
                $q->where('denumire', 'like', "%{$this->cautare}%")
                  ->orWhere('valoare', 'like', "%{$this->cautare}%");
            });
        }

        if (! $this->arataInactive) {
            $query->where('activ', true);
        }

        return view('livewire.setari.tva', [
            'cote' => $query->orderBy('valoare', 'desc')->paginate(15),
        ]);
    }
}
