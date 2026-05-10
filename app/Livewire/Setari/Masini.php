<?php

namespace App\Livewire\Setari;

use App\Models\Car;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Masini extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $cautare = '';

    #[Url(as: 'inactive')]
    public bool $arataInactive = false;

    // ===== Modal masina =====
    public bool $modalDeschis = false;

    public ?int $editandId = null;
    public string $denumire = '';
    public string $nr_inmatriculare = '';
    public ?int $id_depozit = null;
    public string $culoare = '#3b82f6';
    public bool $activ = true;

    // ===== Modal sofer (creare / editare) =====
    public bool $modalSoferDeschis = false;
    // ID-ul user-ului sofer in editare. Null = creare.
    public ?int $soferEditandId = null;
    // Mașina pre-selectată — fixata la deschiderea modalului din contextul masinii.
    public ?int $soferIdMasina = null;
    // Pre-fill cu denumirea mașinii pentru afișare în header (read-only).
    public string $soferDenumireMasina = '';
    public string $soferName = '';
    public string $soferEmail = '';
    public string $soferUsername = '';
    public string $soferPassword = '';
    public bool $soferConfirmat = true;

    protected function rules(): array
    {
        // Doar regulile pentru modalul activ — Livewire valideaza la submit.
        if ($this->modalSoferDeschis) {
            return [
                'soferName' => 'required|string|max:255',
                'soferEmail' => 'required|email|max:255|unique:users,email,' . ($this->soferEditandId ?? 'NULL'),
                'soferUsername' => [
                    'nullable',
                    'string',
                    'regex:/^[a-z0-9._]{3,50}$/',
                    'unique:users,username,' . ($this->soferEditandId ?? 'NULL'),
                ],
                'soferPassword' => $this->soferEditandId
                    ? 'nullable|string|min:6|max:255'
                    : 'required|string|min:6|max:255',
                'soferIdMasina' => 'required|exists:cars,id',
                'soferConfirmat' => 'boolean',
            ];
        }

        return [
            'denumire' => 'required|string|max:100',
            'nr_inmatriculare' => 'required|string|max:20|unique:cars,nr_inmatriculare,' . ($this->editandId ?? 'NULL'),
            'id_depozit' => 'nullable|exists:deposits,id',
            // Cod hex de 7 caractere (#RRGGBB).
            'culoare' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'activ' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'denumire.required' => 'Denumirea este obligatorie.',
            'nr_inmatriculare.required' => 'Numarul de inmatriculare este obligatoriu.',
            'nr_inmatriculare.unique' => 'Mai exista o masina cu acest numar de inmatriculare.',
            'culoare.regex' => 'Culoarea trebuie sa fie in format hex (ex: #3b82f6).',

            'soferName.required' => 'Numele soferului este obligatoriu.',
            'soferEmail.required' => 'Adresa de email este obligatorie.',
            'soferEmail.email' => 'Adresa de email nu este valida.',
            'soferEmail.unique' => 'Mai exista un utilizator cu aceasta adresa de email.',
            'soferUsername.regex' => 'Username-ul poate contine doar litere mici, cifre, punct si sublinie (3-50 caractere).',
            'soferUsername.unique' => 'Mai exista un utilizator cu acest username.',
            'soferPassword.required' => 'Parola este obligatorie la creare.',
            'soferPassword.min' => 'Parola trebuie sa aiba minim 6 caractere.',
            'soferIdMasina.required' => 'Soferul trebuie asociat cu o masina.',
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

    // ===== CRUD masina =====

    public function nou(): void
    {
        $this->resetForm();
        $this->modalDeschis = true;
    }

    public function editeaza(int $id): void
    {
        $masina = Car::findOrFail($id);
        $this->editandId = $masina->id;
        $this->denumire = $masina->denumire;
        $this->nr_inmatriculare = $masina->nr_inmatriculare;
        $this->id_depozit = $masina->id_depozit;
        $this->culoare = $masina->culoare ?: '#3b82f6';
        $this->activ = $masina->activ;
        $this->modalDeschis = true;
    }

    public function salveaza(): void
    {
        $date = $this->validate();

        Car::updateOrCreate(
            ['id' => $this->editandId],
            $date
        );

        $this->modalDeschis = false;
        $this->resetForm();
        session()->flash('mesaj', 'Masina salvata cu succes.');
    }

    public function comutaActiv(int $id): void
    {
        $masina = Car::findOrFail($id);
        $masina->activ = ! $masina->activ;
        $masina->save();
    }

    public function inchideModal(): void
    {
        $this->modalDeschis = false;
        $this->resetForm();
    }

    // ===== CRUD sofer (din contextul masinii) =====

    /**
     * Deschide modalul de creare sofer cu masina pre-selectata (sau fara
     * pre-selectie cand e apelat din butonul global "+ Adauga sofer").
     */
    public function adaugaSofer(?int $idMasina = null): void
    {
        $this->resetFormSofer();
        if ($idMasina) {
            $masina = Car::find($idMasina);
            if ($masina) {
                $this->soferIdMasina = $masina->id;
                $this->soferDenumireMasina = $masina->denumire . ' (' . $masina->nr_inmatriculare . ')';
            }
        }
        $this->modalSoferDeschis = true;
    }

    /**
     * Deschide modalul de editare a soferului asociat masinii. Daca masina
     * are deja un sofer asignat, popam datele lui; altfel deschidem in mod
     * "creare cu masina pre-selectata".
     */
    public function editeazaSofer(int $idMasina): void
    {
        $masina = Car::findOrFail($idMasina);

        // Cautam soferul (tip=5) asignat acestei masini.
        // Convetie: o masina poate avea cel mult un sofer activ; daca exista mai multi
        // (caz teoretic), il editam pe primul gasit (cel mai recent confirmat).
        $sofer = User::where('tip', User::TIP_SOFER)
            ->where('id_masina', $masina->id)
            ->orderByDesc('confirmat')
            ->orderByDesc('id')
            ->first();

        $this->resetFormSofer();
        $this->soferIdMasina = $masina->id;
        $this->soferDenumireMasina = $masina->denumire . ' (' . $masina->nr_inmatriculare . ')';

        if ($sofer) {
            $this->soferEditandId = $sofer->id;
            $this->soferName = $sofer->name;
            $this->soferEmail = $sofer->email;
            $this->soferUsername = $sofer->username ?? '';
            $this->soferConfirmat = (bool) $sofer->confirmat;
        }

        $this->modalSoferDeschis = true;
    }

    public function salveazaSofer(): void
    {
        $date = $this->validate();

        $atribute = [
            'name' => $date['soferName'],
            'email' => $date['soferEmail'],
            'username' => ! empty($date['soferUsername']) ? $date['soferUsername'] : null,
            'tip' => User::TIP_SOFER,
            'id_masina' => $date['soferIdMasina'],
            'id_client' => null,
            'confirmat' => $this->soferConfirmat,
        ];

        if (! empty($date['soferPassword'])) {
            $atribute['password'] = Hash::make($date['soferPassword']);
        }

        User::updateOrCreate(['id' => $this->soferEditandId], $atribute);

        $this->modalSoferDeschis = false;
        $this->resetFormSofer();
        session()->flash('mesaj', 'Sofer salvat cu succes.');
    }

    public function inchideModalSofer(): void
    {
        $this->modalSoferDeschis = false;
        $this->resetFormSofer();
    }

    // ===== Helpers =====

    private function resetForm(): void
    {
        $this->editandId = null;
        $this->denumire = '';
        $this->nr_inmatriculare = '';
        $this->id_depozit = null;
        $this->culoare = '#3b82f6';
        $this->activ = true;
        $this->resetErrorBag();
    }

    private function resetFormSofer(): void
    {
        $this->soferEditandId = null;
        $this->soferIdMasina = null;
        $this->soferDenumireMasina = '';
        $this->soferName = '';
        $this->soferEmail = '';
        $this->soferUsername = '';
        $this->soferPassword = '';
        $this->soferConfirmat = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Car::query()->with('depozit');

        if ($this->cautare !== '') {
            $query->where(function ($q) {
                $q->where('denumire', 'like', "%{$this->cautare}%")
                  ->orWhere('nr_inmatriculare', 'like', "%{$this->cautare}%");
            });
        }

        if (! $this->arataInactive) {
            $query->where('activ', true);
        }

        $masini = $query->orderBy('denumire')->paginate(15);

        // Pre-load soferii asociati masinilor afisate, ca sa le aratam in tabel.
        $idMasini = $masini->pluck('id');
        $soferiPerMasina = User::where('tip', User::TIP_SOFER)
            ->whereIn('id_masina', $idMasini)
            ->orderByDesc('confirmat')
            ->orderByDesc('id')
            ->get()
            ->keyBy('id_masina'); // primul sofer per masina (sortat descendent dupa confirmat)

        return view('livewire.setari.masini', [
            'masini' => $masini,
            'depozite' => Deposit::where('activ', true)->orderBy('denumire')->get(),
            'soferiPerMasina' => $soferiPerMasina,
            'masiniPentruSelect' => Car::where('activ', true)->orderBy('denumire')->get(),
        ]);
    }
}
