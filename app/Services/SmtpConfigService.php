<?php

namespace App\Services;

use App\Models\SetariSmtp;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Faza 6.9 — Aplicare config SMTP dinamic + buton de test.
 *
 * `aplicaConfigActiv()` suprascrie runtime config-ul mailer-ului 'smtp' cu
 * valorile din DB (host, port, credentiale, encryption, from). Apelat la
 * fiecare trimitere de email — overhead minim (cateva config()->set()).
 *
 * `trimiteTest($email)` valideaza configurarea prin trimiterea unui email
 * simplu. Returneaza ['ok' => bool, 'mesaj' => string] cu detalii pentru UI.
 */
class SmtpConfigService
{
    /**
     * Aplica configurarea SMTP activa peste config Laravel mailer.
     * Returneaza true daca s-a aplicat ceva, false daca nu exista config activ.
     */
    public function aplicaConfigActiv(): bool
    {
        $cfg = SetariSmtp::activ();
        if (! $cfg || ! $cfg->esteConfigurat()) {
            return false;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $cfg->host);
        Config::set('mail.mailers.smtp.port', $cfg->port);
        Config::set('mail.mailers.smtp.username', $cfg->username);
        Config::set('mail.mailers.smtp.password', $cfg->password);
        Config::set('mail.mailers.smtp.encryption',
            $cfg->encryption === SetariSmtp::ENCRYPTION_NONE ? null : $cfg->encryption
        );
        Config::set('mail.from.address', $cfg->from_email);
        Config::set('mail.from.name', $cfg->from_name);

        // Curatam mailer-ul deja construit (din container) ca sa preia config nou
        // — daca nu, primul apel din request foloseste config-ul vechi cached.
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');
        Mail::clearResolvedInstances();

        return true;
    }

    /**
     * Trimite un email de test pentru a valida configurarea.
     * Returneaza ['ok' => bool, 'mesaj' => string].
     */
    public function trimiteTest(string $emailDestinatar): array
    {
        if (! filter_var($emailDestinatar, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'mesaj' => 'Adresa de email destinatar nu este valida.'];
        }

        $aplicat = $this->aplicaConfigActiv();
        if (! $aplicat) {
            return ['ok' => false, 'mesaj' => 'Configurarea SMTP nu este activa sau e incompleta.'];
        }

        try {
            Mail::html(
                '<p>Acesta este un email de test trimis din <strong>FlotaMuntenia</strong>.</p>'
                . '<p>Daca primesti acest email, configurarea SMTP functioneaza corect.</p>',
                function ($m) use ($emailDestinatar) {
                    $m->to($emailDestinatar)->subject('Test SMTP FlotaMuntenia');
                }
            );

            return [
                'ok' => true,
                'mesaj' => "Email de test trimis cu succes catre {$emailDestinatar}.",
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'mesaj' => 'Eroare la trimitere: ' . $e->getMessage(),
            ];
        }
    }
}
