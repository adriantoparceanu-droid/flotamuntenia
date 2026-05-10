<?php

namespace App\Livewire\Portal;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Faza 6.3 — Pagina publica de activare cont portal client.
 *
 * Flux:
 *   1. Adminul invita clientul din /setari/utilizatori → genereaza UUID si
 *      seteaza activation_expires_at = now + 7 zile.
 *   2. Clientul primeste email cu link /portal/activare/{token}.
 *   3. Pagina cauta user-ul dupa token; daca tokenul e invalid sau expirat,
 *      afiseaza un mesaj cu link spre /portal/cere-invitatie.
 *   4. Daca valid, arata form parola + confirmare; la submit seteaza parola
 *      bcrypt (cast `hashed`), confirmat=1, sterge token + expirare, login auto
 *      si redirect la portal.comenzi.index.
 *
 * Securitate:
 *   - Tokenul e UNIQUE INDEX pe DB; cautare O(1).
 *   - Expirare verificata la fiecare acces (nu doar la setare parola).
 *   - Doar conturi tip=3 (CLIENT) pot fi activate prin acest flux.
 *   - Mesajul de eroare nu dezvaluie daca tokenul a existat vreodata.
 */
#[Layout('layouts.guest')]
class Activare extends Component
{
    public string $token = '';

    public ?User $utilizator = null;

    public bool $tokenValid = false;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $u = User::where('activation_token', $token)
            ->where('tip', User::TIP_CLIENT)
            ->first();

        if (! $u || ! $u->activation_expires_at || $u->activation_expires_at->isPast()) {
            $this->tokenValid = false;
            return;
        }

        $this->utilizator = $u;
        $this->tokenValid = true;
    }

    public function rules(): array
    {
        return [
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Parola este obligatorie.',
            'password.min' => 'Parola trebuie sa aiba minim 8 caractere.',
            'password.confirmed' => 'Confirmarea parolei nu corespunde.',
        ];
    }

    public function activeaza()
    {
        if (! $this->tokenValid || ! $this->utilizator) {
            return;
        }

        $this->validate();

        // Re-verificam expirarea la submit (token poate expira intre mount si submit
        // pe sesiuni lungi; nu vrem activare tarzie cu token expirat).
        $this->utilizator->refresh();
        if (! $this->utilizator->activation_expires_at || $this->utilizator->activation_expires_at->isPast()) {
            $this->tokenValid = false;
            return;
        }

        $this->utilizator->forceFill([
            'password' => $this->password, // cast `hashed` aplica bcrypt automat
            'confirmat' => true,
            'activation_token' => null,
            'activation_expires_at' => null,
        ])->save();

        Auth::login($this->utilizator);

        session()->regenerate();

        $this->redirectRoute('portal.comenzi.index', navigate: false);
    }

    public function render()
    {
        return view('livewire.portal.activare');
    }
}
