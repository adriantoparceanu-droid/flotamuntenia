<?php

namespace App\Services;

use App\Models\TemplateEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Singurul punct de trimitere email-uri din aplicatie (regula §8.11 din DOCUMENTATION.md).
 *
 * Faza 3.3 — interfata stub (logheaza in storage/logs/email-pending.log).
 * Faza 6.5 — implementare reala cu template-uri din DB + placeholdere.
 * Faza 6.9 — config SMTP dinamic din DB.
 *
 * Comportament Faza 6.5+6.9:
 *   1. Daca email destinatar lipseste → false silent.
 *   2. Cauta template activ dupa cheie. Daca nu exista/dezactivat → log fallback + return false.
 *   3. Aplica config SMTP din DB. Daca lipseste → log fallback + return true (apelantul nu trebuie sa stie).
 *   4. Inlocuieste placeholderele in subiect + body.
 *   5. Trimite cu Mail::html() in try/catch. Pe excepție → log + return false.
 *   6. Pe success → log audit + return true.
 *
 * APELANTII NU SE SCHIMBA — interfata send() ramane identica cu Faza 3.3.
 *
 * Apel:
 *   MailService::send('comanda_aprobata', $client->email, [
 *       'client' => $client->denumire,
 *       'data_livrare' => '2026-05-12',
 *       'cod_comanda' => 123,
 *   ]);
 */
class MailService
{
    /**
     * Trimite un email folosind un template inregistrat.
     *
     * @param  string  $template  Cheie template (ex: 'comanda_aprobata', 'portal_invitatie')
     * @param  string|null  $emailDestinatar  Adresa destinatar; null/empty = skip silent
     * @param  array  $context  Variabile pentru substituirea placeholderelor (chei lowercase)
     * @return bool true daca a plecat (sau a fost loggat ca fallback); false la eroare hard
     */
    public static function send(string $template, ?string $emailDestinatar, array $context = []): bool
    {
        if (empty($emailDestinatar)) {
            return false;
        }

        $tpl = TemplateEmail::gasestePeCheie($template);
        if (! $tpl) {
            Log::channel('email_pending')->warning(
                "Template lipsa sau dezactivat: [{$template}] -> {$emailDestinatar}",
                $context
            );
            return false;
        }

        /** @var TemplateEmailService $tplService */
        $tplService = app(TemplateEmailService::class);

        $subiect = $tplService->inlocuiestePlaceholdere($tpl->subiect, $context);
        $body = $tplService->inlocuiestePlaceholdere($tpl->continut_html, $context);

        /** @var SmtpConfigService $smtpService */
        $smtpService = app(SmtpConfigService::class);
        $smtpAplicat = $smtpService->aplicaConfigActiv();

        if (! $smtpAplicat) {
            // Fallback: SMTP neconfigurat → log payload-ul pentru audit/debug.
            // Returnam true pentru ca apelantul nu trebuie sa stie diferenta —
            // comportament identic cu stub-ul din Faza 3.3.
            Log::channel('email_pending')->info(
                "[FALLBACK SMTP-OFF] [{$template}] -> {$emailDestinatar} | Subiect: {$subiect}",
                $context
            );
            return true;
        }

        try {
            Mail::html($body, function ($m) use ($emailDestinatar, $subiect) {
                $m->to($emailDestinatar)->subject($subiect);
            });

            Log::channel('email_pending')->info(
                "[OK] [{$template}] -> {$emailDestinatar} | Subiect: {$subiect}"
            );

            return true;
        } catch (Throwable $e) {
            Log::channel('email_pending')->error(
                "[EROARE] [{$template}] -> {$emailDestinatar} | {$e->getMessage()}",
                $context
            );
            return false;
        }
    }
}
