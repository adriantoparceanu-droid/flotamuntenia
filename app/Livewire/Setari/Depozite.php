<?php

namespace App\Livewire\Setari;

use App\Models\Deposit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Depozite extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $cautare = '';

    #[Url(as: 'inactive')]
    public bool $arataInactive = false;

    public bool $modalDeschis = false;

    public ?int $editandId = null;
    public string $denumire = '';
    public string $adresa = '';
    public bool $activ = true;

    protected function rules(): array
    {
        return [
            'denumire' => 'required|string|max:255',
            'adresa' => 'nullable|string|max:500',
            'activ' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'denumire.required' => 'Denumirea depozitului este obligatorie.',
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
        $depozit = Deposit::findOrFail($id);
        $this->editandId = $depozit->id;
        $this->denumire = $depozit->denumire;
        $this->adresa = $depozit->adresa ?? '';
        $this->activ = $depozit->activ;
        $this->modalDeschis = true;
    }

    public function salveaza(): void
    {
        $date = $this->validate();

        Deposit::updateOrCreate(
            ['id' => $this->editandId],
            $date
        );

        $this->modalDeschis = false;
        $this->resetForm();
        session()->flash('mesaj', 'Depozit salvat cu succes.');
    }

    public function comutaActiv(int $id): void
    {
        $depozit = Deposit::findOrFail($id);
        $depozit->activ = ! $depozit->activ;
        $depozit->save();
    }

    public function inchideModal(): void
    {
        $this->modalDeschis = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editandId = null;
        $this->denumire = '';
        $this->adresa = '';
        $this->activ = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Deposit::query();

        if ($this->cautare !== '') {
            $query->where(function ($q) {
                $q->where('denumire', 'like', "%{$this->cautare}%")
                  ->orWhere('adresa', 'like', "%{$this->cautare}%");
            });
        }

        if (! $this->arataInactive) {
            $query->where('activ', true);
        }

        return view('livewire.setari.depozite', [
            'depozite' => $query->orderBy('denumire')->paginate(15),
        ]);
    }
}
