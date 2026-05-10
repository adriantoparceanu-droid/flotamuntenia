<?php

namespace App\Livewire\Portal;

use App\Models\User;
use App\Services\MailService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Faza 6.3 — Pagina publica „Cere o noua invitatie".
 *
 * Form simplu cu adresa de email. Daca exista user tip=3 cu acel email,
 * regenereaza tokenul (UUID + 7 zile expirare) si trimite email via MailService.
 *
 * Anti-enumerare: mesajul afisat e generic indiferent daca emailul a fost
 * gasit sau nu — nu vrem sa expunem ce conturi exista.
 *
 * Rate-limit: pe pagina publica e bun-simt sa avem un throttle. Folosim
 * limiterul Laravel default per-IP — vezi RouteServiceProvider daca devine
 * spam (deocamdata e suficient ca e o pagina cu friction redus).
 */
#[Layout('layouts.guest')]
class CereInvitatie extends Component
{
    public string $email = '';

    public bool $trimis = false;

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Adresa de email este obligatorie.',
            'email.email' => 'Adresa de email nu este valida.',
        ];
    }

    public function trimite(): void
    {
        $this->validate();

        $u = User::where('email', $this->email)
            ->where('tip', User::TIP_CLIENT)
            ->first();

        if ($u) {
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
        }

        // Mesaj generic indiferent de rezultat (anti-enumerare).
        $this->trimis = true;
    }

    public function render()
    {
        return view('livewire.portal.cere-invitatie');
    }
}
