<?php

namespace Database\Seeders;

use App\Models\TemplateEmail;
use Illuminate\Database\Seeder;

/**
 * Faza 6.5 — Seedere implicite pentru cele 11 template-uri de email.
 *
 * Idempotent prin updateOrCreate pe `cheie`. Adminul poate edita orice
 * template din UI dupa seed; rerularea seederului NU suprascrie modificarile
 * (verifica daca cheia exista — daca da, doar updateaza descriere/denumire,
 * NU subiect/continut_html). Asta evita „pierderea editarilor manuale".
 *
 * Template-urile folosesc HTML minimalist cu inline-styles (clientii email
 * nu suporta CSS extern); placeholderele sunt {NUME_VARIABILA}.
 */
class TemplateuriEmailSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templateuriImplicite() as $tpl) {
            $existent = TemplateEmail::where('cheie', $tpl['cheie'])->first();

            if ($existent) {
                // Actualizeaza doar metadatele (denumire, descriere) — NU subiect/continut
                // pentru a nu suprascrie editarile facute din UI de admin.
                $existent->update([
                    'denumire' => $tpl['denumire'],
                    'descriere_placeholdere' => $tpl['descriere_placeholdere'],
                ]);
            } else {
                TemplateEmail::create($tpl);
            }
        }

        $this->command?->info('OK: ' . count($this->templateuriImplicite()) . ' template-uri email seedate (idempotent).');
    }

    /**
     * Lista celor 11 template-uri implicite. Sursa de adevar pentru chei.
     * Vezi TemplateEmailService::placeholderePerCheie() pentru lista placeholderelor
     * disponibile per template (folosita in editor UI).
     */
    private function templateuriImplicite(): array
    {
        $signature = $this->signatureHtml();

        return [
            // ===== Cont + autentificare =====
            [
                'cheie' => 'cont_creat',
                'denumire' => 'Cont creat',
                'subiect' => 'Bun venit, {NUME}! Contul tau a fost creat',
                'continut_html' => $this->wrap('Cont creat', "
                    <p>Buna, <strong>{NUME}</strong>,</p>
                    <p>Contul tau pentru portalul FlotaMuntenia a fost creat de operatorul nostru.</p>
                    <p>Pentru a-l activa si seta o parola, te rugam sa accesezi linkul de mai jos:</p>
                    <p style='margin: 20px 0;'><a href='{LINK_ACTIVARE}' style='background: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Activeaza contul</a></p>
                    <p style='color: #6b7280; font-size: 13px;'>Linkul expira la {EXPIRA}.</p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis la crearea unui cont nou de admin.',
                'activ' => true,
            ],
            [
                'cheie' => 'bun_venit_portal',
                'denumire' => 'Bun venit dupa activare portal',
                'subiect' => 'Contul tau este activ — bun venit pe portal!',
                'continut_html' => $this->wrap('Bun venit pe portal', "
                    <p>Buna, <strong>{NUME}</strong>,</p>
                    <p>Contul tau a fost activat cu succes. Acum poti:</p>
                    <ul>
                        <li>Plasa comenzi noi din contul tau</li>
                        <li>Vedea istoricul comenzilor</li>
                        <li>Verifica datele de contact si adresele de livrare</li>
                    </ul>
                    <p style='margin: 20px 0;'><a href='{LINK_PORTAL}' style='background: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Acceseaza portalul</a></p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis automat dupa activarea contului prin link.',
                'activ' => true,
            ],
            [
                'cheie' => 'parola_noua',
                'denumire' => 'Parola noua (forgot password)',
                'subiect' => 'Resetare parola FlotaMuntenia',
                'continut_html' => $this->wrap('Resetare parola', "
                    <p>Buna, <strong>{NUME}</strong>,</p>
                    <p>Ai cerut resetarea parolei pentru contul tau FlotaMuntenia.</p>
                    <p>Acceseaza linkul de mai jos pentru a seta o parola noua:</p>
                    <p style='margin: 20px 0;'><a href='{LINK_RESETARE}' style='background: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Reseteaza parola</a></p>
                    <p style='color: #6b7280; font-size: 13px;'>Daca nu ai cerut tu acest reset, ignora emailul — parola ramane nemodificata.</p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis cand utilizatorul cere reset parola.',
                'activ' => true,
            ],
            [
                'cheie' => 'portal_invitatie',
                'denumire' => 'Invitatie activare portal client',
                'subiect' => 'Invitatie activare cont portal FlotaMuntenia',
                'continut_html' => $this->wrap('Invitatie activare', "
                    <p>Buna, <strong>{NUME}</strong>,</p>
                    <p>Operatorul FlotaMuntenia ti-a creat un cont pe portalul nostru.</p>
                    <p>Pentru a-l activa si seta o parola, acceseaza linkul de mai jos:</p>
                    <p style='margin: 20px 0;'><a href='{LINK}' style='background: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Activeaza contul</a></p>
                    <p style='color: #6b7280; font-size: 13px;'>Linkul expira la {EXPIRA}. Daca a expirat, poti cere o noua invitatie de pe pagina de autentificare.</p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis cand admin invita un client portal (Faza 6.3).',
                'activ' => true,
            ],

            // ===== Comenzi =====
            [
                'cheie' => 'comanda_portal_noua',
                'denumire' => 'Notificare admin: comanda noua portal',
                'subiect' => 'Comanda noua portal #{COD_COMANDA} de la {CLIENT}',
                'continut_html' => $this->wrap('Comanda noua portal', "
                    <p>Salut,</p>
                    <p>Clientul <strong>{CLIENT}</strong> a plasat o comanda noua prin portal:</p>
                    <table style='border-collapse: collapse; width: 100%;'>
                        <tr><td style='padding: 6px; border-bottom: 1px solid #e5e7eb;'><strong>Cod comanda:</strong></td><td style='padding: 6px; border-bottom: 1px solid #e5e7eb;'>#{COD_COMANDA}</td></tr>
                        <tr><td style='padding: 6px; border-bottom: 1px solid #e5e7eb;'><strong>Data dorita:</strong></td><td style='padding: 6px; border-bottom: 1px solid #e5e7eb;'>{DATA_LIVRARE}</td></tr>
                        <tr><td style='padding: 6px; border-bottom: 1px solid #e5e7eb;'><strong>Total estimativ:</strong></td><td style='padding: 6px; border-bottom: 1px solid #e5e7eb;'>{TOTAL} lei</td></tr>
                        <tr><td style='padding: 6px; border-bottom: 1px solid #e5e7eb;'><strong>Plasata de:</strong></td><td style='padding: 6px; border-bottom: 1px solid #e5e7eb;'>{PLASATA_DE}</td></tr>
                    </table>
                    <p style='margin-top: 20px;'>Comanda asteapta aprobarea ta in interfata admin.</p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis catre toti adminii activi cand un client plaseaza comanda din portal.',
                'activ' => true,
            ],
            [
                'cheie' => 'comanda_aprobata',
                'denumire' => 'Comanda aprobata',
                'subiect' => 'Comanda #{COD_COMANDA} a fost aprobata',
                'continut_html' => $this->wrap('Comanda aprobata', "
                    <p>Buna, <strong>{CLIENT}</strong>,</p>
                    <p>Comanda ta <strong>#{COD_COMANDA}</strong> a fost aprobata si va fi livrata pe data de <strong>{DATA_LIVRARE}</strong> (interval: {INTERVAL}).</p>
                    <p>Total: <strong>{TOTAL} lei</strong></p>
                    <p style='color: #6b7280; font-size: 13px;'>Iti vom trimite o confirmare cand comanda e livrata.</p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis cand admin aproba o comanda portal.',
                'activ' => true,
            ],
            [
                'cheie' => 'comanda_respinsa',
                'denumire' => 'Comanda respinsa',
                'subiect' => 'Comanda #{COD_COMANDA} a fost respinsa',
                'continut_html' => $this->wrap('Comanda respinsa', "
                    <p>Buna, <strong>{CLIENT}</strong>,</p>
                    <p>Comanda ta <strong>#{COD_COMANDA}</strong> nu a putut fi aprobata.</p>
                    <p><strong>Motiv:</strong></p>
                    <blockquote style='border-left: 3px solid #dc2626; padding: 8px 12px; background: #fef2f2; margin: 12px 0;'>{MOTIV}</blockquote>
                    <p>Pentru detalii sau o comanda noua, te rugam sa ne contactezi.</p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis cand admin respinge o comanda portal cu motiv.',
                'activ' => true,
            ],
            [
                'cheie' => 'comanda_livrata',
                'denumire' => 'Comanda livrata',
                'subiect' => 'Comanda #{COD_COMANDA} a fost livrata',
                'continut_html' => $this->wrap('Comanda livrata', "
                    <p>Buna, <strong>{CLIENT}</strong>,</p>
                    <p>Comanda ta <strong>#{COD_COMANDA}</strong> a fost livrata cu succes pe data de <strong>{DATA_LIVRARE}</strong>.</p>
                    <p>Total: <strong>{TOTAL} lei</strong> — modalitate plata: {MOD_PLATA}</p>
                    <p>Iti multumim ca ai ales FlotaMuntenia!</p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis cand soferul marcheaza comanda ca livrata. (TODO hook)',
                'activ' => true,
            ],
            [
                'cheie' => 'factura_emisa',
                'denumire' => 'Factura emisa',
                'subiect' => 'Factura {SERIE_FACTURA} — comanda #{COD_COMANDA}',
                'continut_html' => $this->wrap('Factura emisa', "
                    <p>Buna, <strong>{CLIENT}</strong>,</p>
                    <p>A fost emisa factura <strong>{SERIE_FACTURA}</strong> pentru comanda <strong>#{COD_COMANDA}</strong>.</p>
                    <p>Total: <strong>{TOTAL} lei</strong></p>
                    <p style='margin: 20px 0;'><a href='{LINK_FACTURA}' style='background: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Descarca factura</a></p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis cand admin emite factura prin Oblio (Faza 6.1). (TODO hook)',
                'activ' => true,
            ],

            // ===== Mentenanta dozatoare =====
            [
                'cheie' => 'igienizare_reminder',
                'denumire' => 'Reminder igienizare dozator (bidoane)',
                'subiect' => 'Reminder: igienizare dozator scadenta',
                'continut_html' => $this->wrap('Reminder igienizare', "
                    <p>Buna, <strong>{CLIENT}</strong>,</p>
                    <p>Te informam ca dozatorul tau este programat pentru igienizare la data de <strong>{DATA_SCADENTA}</strong>.</p>
                    <p>Locatie: {ADRESA}</p>
                    <p>Te vom contacta in curand pentru a stabili o ora convenabila.</p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis manual de admin din /dozatoare cand un dozator e scadent (Faza 4.1).',
                'activ' => true,
            ],
            [
                'cheie' => 'mentenanta_filtru_reminder',
                'denumire' => 'Reminder schimbare filtre dozator',
                'subiect' => 'Reminder: schimbare filtre dozator',
                'continut_html' => $this->wrap('Reminder mentenanta filtre', "
                    <p>Buna, <strong>{CLIENT}</strong>,</p>
                    <p>Filtrele dozatorului tau sunt programate pentru schimbare la data de <strong>{DATA_SCADENTA}</strong>.</p>
                    <p>Locatie: {ADRESA}</p>
                    <p>Te vom contacta in curand pentru a stabili o ora convenabila.</p>
                ", $signature),
                'descriere_placeholdere' => 'Trimis manual de admin din /dozatoare?tip=filtre cand un filtru e scadent (Faza 4.3).',
                'activ' => true,
            ],
        ];
    }

    /**
     * Wrapper HTML standard pentru toate template-urile (header, footer cu signature).
     * Inline-styles pentru ca clientii email (Outlook in special) nu suporta CSS extern.
     */
    private function wrap(string $titlu, string $body, string $signature): string
    {
        return <<<HTML
<table style='width: 100%; max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; color: #1f2937;'>
    <tr>
        <td style='background: #0c4a6e; color: white; padding: 16px 20px; border-radius: 6px 6px 0 0;'>
            <h2 style='margin: 0; font-size: 18px;'>FlotaMuntenia</h2>
        </td>
    </tr>
    <tr>
        <td style='padding: 24px 20px; background: #ffffff; border: 1px solid #e5e7eb; border-top: 0;'>
            $body
        </td>
    </tr>
    <tr>
        <td style='padding: 16px 20px; background: #f9fafb; border: 1px solid #e5e7eb; border-top: 0; border-radius: 0 0 6px 6px;'>
            $signature
        </td>
    </tr>
</table>
HTML;
    }

    private function signatureHtml(): string
    {
        return "<p style='margin: 0; font-size: 12px; color: #6b7280;'>
            <strong>FlotaMuntenia</strong> — Livrare apa imbuteliata<br>
            <a href='mailto:contact@flotamuntenia.ro' style='color: #0284c7;'>contact@flotamuntenia.ro</a>
        </p>";
    }
}
