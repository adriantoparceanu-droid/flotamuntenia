<?php

namespace App\Livewire\Setari;

use App\Models\Car;
use App\Models\Client;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Faza 1.5 — Administrare utilizatori (extensie a Fazei 1.1).
 *
 * Modul standalone /setari/utilizatori cu CRUD pe `users`:
 *   - Creare cont nou cu rol selectabil + parola setata manual
 *   - Editare nume, email, rol, parola (parola optionala la edit, lasa gol pentru pastrare)
 *   - Asociere șofer ↔ mașină (tip=5 obliga selectarea masinii — required pentru
 *     a putea filtra comenzile in Sofer\Traseu)
 *   - Toggle `confirmat` (echivalent „dezactivare" — utilizatorul nu se mai poate
 *     autentifica fara confirmat=1)
 *   - Filtru pe rol + search dupa nume/email + flag arataNeconfirmati
 *
 * Securitate:
 *   - Adminul curent NU se poate dezactiva pe sine (toggle confirmat blocat
 *     pentru auth()->id() == $id)
 *   - Adminul curent NU isi poate schimba rolul propriu (block in salveaza())
 *
 * NOTA: gestiune ↔ depozit ramane TODO (necesita coloana `id_depozit` pe `users`,
 * nu exista inca). Daca apare nevoie, adaugam o migratie + extindem formularul.
 */
#[Layout('layouts.app')]
class Utilizatori extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $cautare = '';

    #[Url(as: 'rol')]
    public ?int $filtruRol = null;

    #[Url(as: 'neconfirmati')]
    public bool $arataNeconfirmati = false;

    public bool $modalDeschis = false;

    public ?int $editandId = null;
    public string $name = '';
    public string $email = '';
    public string $username = '';
    public ?int $tip = null;
    public string $password = '';
    public ?int $idMasina = null;
    public ?int $idClient = null;
    public bool $confirmat = true;

    /**
     * Lista de roluri disponibile in UI (cheie = constanta tip, valoare = eticheta RO).
     */
    public function roluriDisponibile(): array
    {
        return [
            User::TIP_ADMIN => 'Administrator',
            User::TIP_SOFER => 'Sofer',
            User::TIP_GESTIUNE => 'Gestiune',
            User::TIP_CLIENT => 'Client portal',
            User::TIP_SUPERADMIN => 'Superadmin platforma',
        ];
    }

    public function etichetaRol(?int $tip): string
    {
        return $this->roluriDisponibile()[$tip] ?? 'Nedefinit';
    }

    public function culoareRol(?int $tip): string
    {
        return match ($tip) {
            User::TIP_ADMIN => 'bg-indigo-100 text-indigo-700',
            User::TIP_SUPERADMIN => 'bg-fuchsia-100 text-fuchsia-700',
            User::TIP_SOFER => 'bg-emerald-100 text-emerald-700',
            User::TIP_GESTIUNE => 'bg-amber-100 text-amber-700',
            User::TIP_CLIENT => 'bg-sky-100 text-sky-700',
            default => 'bg-gray-100 text-gray-500',
        };
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . ($this->editandId ?? 'NULL'),
            // Username: optional, format restrictiv 3-50 caractere [a-z0-9._],
            // unic la nivel global. Pentru a evita confuzia cu email-ul,
            // interzicem caracterul @.
            'username' => [
                'nullable',
                'string',
                'regex:/^[a-z0-9._]{3,50}$/',
                'unique:users,username,' . ($this->editandId ?? 'NULL'),
            ],
            'tip' => 'required|integer|in:' . implode(',', array_keys($this->roluriDisponibile())),
            // Parola: obligatorie la creare, optionala la edit (lasa gol pentru pastrare)
            'password' => $this->editandId ? 'nullable|string|min:6|max:255' : 'required|string|min:6|max:255',
            // id_masina obligatoriu doar pentru sofer
            'idMasina' => $this->tip === User::TIP_SOFER
                ? 'required|exists:cars,id'
                : 'nullable|exists:cars,id',
            // id_client obligatoriu doar pentru client portal
            'idClient' => $this->tip === User::TIP_CLIENT
                ? 'required|exists:clienti,id'
                : 'nullable|exists:clienti,id',
            'confirmat' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Numele este obligatoriu.',
            'email.required' => 'Adresa de email este obligatorie.',
            'email.email' => 'Adresa de email nu este valida.',
            'email.unique' => 'Mai exista un utilizator cu aceasta adresa de email.',
            'username.regex' => 'Username-ul poate contine doar litere mici, cifre, punct si sublinie (3-50 caractere).',
            'username.unique' => 'Mai exista un utilizator cu acest username.',
            'tip.required' => 'Selectati un rol.',
            'tip.in' => 'Rolul selectat nu este valid.',
            'password.required' => 'Parola este obligatorie la creare.',
            'password.min' => 'Parola trebuie sa aiba minim 6 caractere.',
            'idMasina.required' => 'Soferul trebuie asociat cu o masina.',
            'idMasina.exists' => 'Masina selectata nu exista.',
            'idClient.required' => 'Clientul portal trebuie asociat cu un client.',
            'idClient.exists' => 'Clientul selectat nu exista.',
        ];
    }

    public function updatingCautare(): void
    {
        $this->resetPage();
    }

    public function updatingFiltruRol(): void
    {
        $this->resetPage();
    }

    public function updatingArataNeconfirmati(): void
    {
        $this->resetPage();
    }

    /**
     * Cand admin schimba rolul in modal, resetam id_masina / id_client daca
     * nu mai sunt aplicabile pentru noul rol (evita date orfan in DB).
     */
    public function updatedTip(): void
    {
        if ($this->tip !== User::TIP_SOFER) {
            $this->idMasina = null;
        }
        if ($this->tip !== User::TIP_CLIENT) {
            $this->idClient = null;
        }
    }

    public function nou(): void
    {
        $this->resetForm();
        $this->modalDeschis = true;
    }

    public function editeaza(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editandId = $u->id;
        $this->name = $u->name;
        $this->email = $u->email;
        $this->username = $u->username ?? '';
        $this->tip = $u->tip;
        $this->password = '';
        $this->idMasina = $u->id_masina;
        $this->idClient = $u->id_client;
        $this->confirmat = (bool) $u->confirmat;
        $this->modalDeschis = true;
    }

    public function salveaza(): void
    {
        $date = $this->validate();

        // Securitate: adminul curent nu-si poate schimba propriul rol
        if ($this->editandId && $this->editandId === auth()->id()) {
            $userCurent = User::find($this->editandId);
            if ($userCurent && $userCurent->tip !== $this->tip) {
                $this->addError('tip', 'Nu va puteti schimba propriul rol. Cereti unui alt admin sa o faca.');
                return;
            }
            // Nici nu se poate dezactiva pe sine
            if (! $this->confirmat) {
                $this->addError('confirmat', 'Nu va puteti dezactiva propriul cont.');
                return;
            }
        }

        $atribute = [
            'name' => $date['name'],
            'email' => $date['email'],
            // Username: stocam null cand e gol (NU "") pentru a respecta UNIQUE
            // care permite multiple NULL dar nu multiple "".
            'username' => ! empty($date['username']) ? $date['username'] : null,
            'tip' => $date['tip'],
            'id_masina' => $date['idMasina'] ?? null,
            'id_client' => $date['idClient'] ?? null,
            'confirmat' => $this->confirmat,
        ];

        // Parola: doar daca e completata (la creare e obligatorie via rules; la
        // edit sare daca e goala — pastrand parola existenta).
        if (! empty($date['password'])) {
            $atribute['password'] = Hash::make($date['password']);
        }

        User::updateOrCreate(['id' => $this->editandId], $atribute);

        $this->modalDeschis = false;
        $this->resetForm();
        session()->flash('mesaj', 'Utilizator salvat cu succes.');
    }

    /**
     * Faza 6.3 — Genereaza token de activare pentru un client portal (tip=3)
     * si trimite linkul prin email via MailService stub.
     *
     * Re-rulabila: daca tokenul exista deja sau a expirat, se regenereaza un altul nou.
     * Expirare: 7 zile de la generare.
     *
     * Securitate: doar pentru useri tip=3 (CLIENT). Refuza pentru alte tipuri
     * pentru a evita confuzia sau eroarea operator.
     */
    public function trimiteInvitatie(int $id): void
    {
        $u = User::findOrFail($id);

        if ($u->tip !== User::TIP_CLIENT) {
            session()->flash('eroare', 'Invitatiile portal sunt doar pentru conturile de tip Client portal.');
            return;
        }

        if (empty($u->email)) {
            session()->flash('eroare', 'Acest cont nu are email configurat — nu se poate trimite invitatie.');
            return;
        }

        $u->forceFill([
            'activation_token' => (string) Str::uuid(),
            'activation_expires_at' => Carbon::now()->addDays(7),
        ])->save();

        $link = route('portal.activare', ['token' => $u->activation_token]);

        MailService::send('portal_invitatie', $u->email, [
            'nume' => $u->name,
            'link' => $link,
            'expira' => $u->activation_expires_at->format('d.m.Y H:i'),
        ]);

        session()->flash('mesaj', "Invitatie trimisa catre {$u->email}. Expira la {$u->activation_expires_at->format('d.m.Y H:i')}.");
    }

    /**
     * Toggle pe `confirmat`. Adminul curent nu se poate dezactiva pe sine.
     */
    public function comutaConfirmat(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('eroare', 'Nu va puteti dezactiva propriul cont.');
            return;
        }
        $u = User::findOrFail($id);
        $u->confirmat = ! $u->confirmat;
        $u->save();
        session()->flash('mesaj', $u->confirmat ? 'Cont activat.' : 'Cont dezactivat.');
    }

    public function inchideModal(): void
    {
        $this->modalDeschis = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editandId = null;
        $this->name = '';
        $this->email = '';
        $this->username = '';
        $this->tip = null;
        $this->password = '';
        $this->idMasina = null;
        $this->idClient = null;
        $this->confirmat = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = User::query()->with(['masina', 'client']);

        if ($this->cautare !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->cautare}%")
                  ->orWhere('email', 'like', "%{$this->cautare}%")
                  ->orWhere('username', 'like', "%{$this->cautare}%");
            });
        }

        if ($this->filtruRol !== null) {
            $query->where('tip', $this->filtruRol);
        }

        if (! $this->arataNeconfirmati) {
            $query->where('confirmat', true);
        }

        return view('livewire.setari.utilizatori', [
            'utilizatori' => $query->orderBy('tip')->orderBy('name')->paginate(15),
            'masini' => Car::where('activ', true)->orderBy('denumire')->get(),
            'clienti' => Client::where('reziliat', false)->orderBy('denumire')->get(),
            'roluri' => $this->roluriDisponibile(),
            'userCurentId' => auth()->id(),
        ]);
    }
}
