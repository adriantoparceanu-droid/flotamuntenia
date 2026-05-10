<?php

namespace App\Livewire\Setari;

use App\Models\SetariSmtp;
use App\Services\SmtpConfigService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Faza 6.9 — UI configurare SMTP din DB.
 *
 * Un singur record activ la un moment dat (pattern facturare_setari).
 * Form cu 7 campuri + buton „Trimite email de test" care valideaza setarile
 * fara sa declanseze un flow real (comanda, etc.).
 *
 * Parola NU e pre-completata in form la edit (ramane goala) pentru a evita
 * leak in DOM/sources. Daca adminul lasa parola goala la save, parola
 * existenta e pastrata. Pattern identic cu cel din Setari\Utilizatori.
 */
#[Layout('layouts.app')]
class Smtp extends Component
{
    public ?int $editandId = null;

    public string $host = '';
    public int $port = 587;
    public string $username = '';
    public string $password = '';
    public string $encryption = SetariSmtp::ENCRYPTION_TLS;
    public string $fromEmail = '';
    public string $fromName = 'FlotaMuntenia';
    public bool $activ = true;

    public string $emailTest = '';

    public ?string $mesaj = null;
    public ?string $eroare = null;
    public ?string $rezultatTest = null;
    public ?bool $rezultatTestOk = null;

    public function mount(): void
    {
        $cfg = SetariSmtp::activ() ?? SetariSmtp::orderByDesc('id')->first();
        if ($cfg) {
            $this->editandId = $cfg->id;
            $this->host = $cfg->host;
            $this->port = $cfg->port;
            $this->username = $cfg->username ?? '';
            // Parola NU se pre-completeaza — campul ramane gol la edit.
            $this->encryption = $cfg->encryption;
            $this->fromEmail = $cfg->from_email;
            $this->fromName = $cfg->from_name;
            $this->activ = (bool) $cfg->activ;

            // Pre-completez emailul de test cu emailul adminului curent (UX)
            $this->emailTest = auth()->user()->email ?? '';
        }
    }

    public function salveaza(): void
    {
        $reguli = [
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'encryption' => 'required|in:tls,ssl,none',
            'fromEmail' => 'required|email|max:255',
            'fromName' => 'required|string|max:255',
            'activ' => 'boolean',
        ];

        // Parola obligatorie doar la creare; la edit poate fi goala (pastreaza vechea)
        if (! $this->editandId) {
            $reguli['password'] = 'nullable|string|max:255';
        } else {
            $reguli['password'] = 'nullable|string|max:255';
        }

        $this->validate($reguli, [
            'host.required' => 'Host SMTP este obligatoriu.',
            'port.required' => 'Port SMTP este obligatoriu.',
            'port.min' => 'Port invalid.',
            'fromEmail.required' => 'Adresa „de la" este obligatorie.',
            'fromEmail.email' => 'Adresa „de la" nu este valida.',
            'fromName.required' => 'Numele expeditor este obligatoriu.',
        ]);

        $atribute = [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username ?: null,
            'encryption' => $this->encryption,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName,
            'activ' => $this->activ,
        ];

        // Doar daca admin a tastat parola noua o salvam (cast `encrypted:string` o cripteaza automat)
        if (! empty($this->password)) {
            $atribute['password'] = $this->password;
        }

        if ($this->editandId) {
            $cfg = SetariSmtp::findOrFail($this->editandId);
            $cfg->update($atribute);
        } else {
            $cfg = SetariSmtp::create($atribute);
            $this->editandId = $cfg->id;
        }

        // Daca acest record devine activ, dezactivam toate celelalte (un singur activ)
        if ($this->activ) {
            SetariSmtp::where('id', '!=', $cfg->id)->update(['activ' => false]);
        }

        // Resetam parola din form ca sa nu ramana in DOM dupa save
        $this->password = '';

        $this->mesaj = 'Setari SMTP salvate cu succes.';
        $this->eroare = null;
    }

    public function trimiteEmailDeTest(SmtpConfigService $svc): void
    {
        $this->validate([
            'emailTest' => 'required|email',
        ], [
            'emailTest.required' => 'Adresa de test este obligatorie.',
            'emailTest.email' => 'Adresa de test nu este valida.',
        ]);

        $rezultat = $svc->trimiteTest($this->emailTest);

        $this->rezultatTest = $rezultat['mesaj'];
        $this->rezultatTestOk = $rezultat['ok'];
    }

    public function render()
    {
        $cfgActiv = SetariSmtp::activ();

        return view('livewire.setari.smtp', [
            'cfgActiv' => $cfgActiv,
        ]);
    }
}
