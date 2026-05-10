<?php

namespace App\Services;

/**
 * Faza 6.5 — Servicii pentru template-uri email.
 *
 * Doua responsabilitati:
 *   1. inlocuiestePlaceholdere() — substitueste {CHEIE_UPPERCASE} cu valoarea
 *      din context. Conversie automata: contextul vine cu chei lowercase
 *      (ex: 'cod_comanda' => 123), template-ul foloseste {COD_COMANDA}.
 *   2. placeholderePerCheie() — returneaza lista documentata a placeholderelor
 *      disponibile per template, pentru afisare in editor UI.
 *
 * Substituirea e str_replace simpla — zero risc de XSS pentru ca contextul
 * provine din DB (denumiri client, sume, etc.) si NU din input user direct.
 * Eventual sanitizare suplimentara se poate adauga aici daca apare nevoie.
 */
class TemplateEmailService
{
    /**
     * Inlocuieste placeholderele {CHEIE_UPPERCASE} din $text cu valorile din $context.
     * Cheile contextului sunt lowercase; conversia la {UPPERCASE} se face automat.
     *
     * Valorile null/'' sunt inlocuite cu '—' (em dash) pentru a evita
     * placeholdere goale care arata ciudat in email.
     */
    public function inlocuiestePlaceholdere(string $text, array $context): string
    {
        $cautare = [];
        $inlocuire = [];

        foreach ($context as $cheie => $valoare) {
            $cautare[] = '{' . strtoupper((string) $cheie) . '}';
            $inlocuire[] = ($valoare === null || $valoare === '') ? '—' : (string) $valoare;
        }

        return str_replace($cautare, $inlocuire, $text);
    }

    /**
     * Placeholderele documentate per template. Folosit de editor pentru a afisa
     * lista in sidebar si pentru documentatie.
     *
     * Sursa de adevar — daca un apelant introduce un placeholder nou, trebuie
     * adaugat aici (altfel e invizibil pentru admin in editor).
     */
    public function placeholderePerCheie(string $cheie): array
    {
        return match ($cheie) {
            'cont_creat' => [
                '{NUME}' => 'Numele utilizatorului',
                '{LINK_ACTIVARE}' => 'URL absolut spre pagina de activare',
                '{EXPIRA}' => 'Data si ora cand expira linkul',
            ],
            'bun_venit_portal' => [
                '{NUME}' => 'Numele utilizatorului',
                '{LINK_PORTAL}' => 'URL absolut spre /portal/comenzi',
            ],
            'parola_noua' => [
                '{NUME}' => 'Numele utilizatorului',
                '{LINK_RESETARE}' => 'URL absolut spre pagina de resetare parola',
            ],
            'portal_invitatie' => [
                '{NUME}' => 'Numele utilizatorului',
                '{LINK}' => 'URL absolut spre pagina de activare cu token',
                '{EXPIRA}' => 'Data si ora cand expira linkul (format dd.mm.yyyy hh:mm)',
            ],
            'comanda_portal_noua' => [
                '{CLIENT}' => 'Denumirea clientului care a plasat',
                '{COD_COMANDA}' => 'ID-ul comenzii',
                '{DATA_LIVRARE}' => 'Data dorita (format dd.mm.yyyy)',
                '{TOTAL}' => 'Total estimativ in lei',
                '{PLASATA_DE}' => 'Numele utilizatorului portal care a plasat',
            ],
            'comanda_aprobata' => [
                '{CLIENT}' => 'Denumirea clientului',
                '{COD_COMANDA}' => 'ID-ul comenzii',
                '{DATA_LIVRARE}' => 'Data programata (format dd.mm.yyyy)',
                '{INTERVAL}' => 'Interval orar de livrare (text liber)',
                '{TOTAL}' => 'Total comanda in lei',
            ],
            'comanda_respinsa' => [
                '{CLIENT}' => 'Denumirea clientului',
                '{COD_COMANDA}' => 'ID-ul comenzii',
                '{MOTIV}' => 'Motivul respingerii (text liber)',
            ],
            'comanda_livrata' => [
                '{CLIENT}' => 'Denumirea clientului',
                '{COD_COMANDA}' => 'ID-ul comenzii',
                '{DATA_LIVRARE}' => 'Data efectiva (format dd.mm.yyyy)',
                '{TOTAL}' => 'Total comanda in lei',
                '{MOD_PLATA}' => 'Modalitate plata (Cash, OP, Card, Alta)',
            ],
            'factura_emisa' => [
                '{CLIENT}' => 'Denumirea clientului',
                '{COD_COMANDA}' => 'ID-ul comenzii',
                '{SERIE_FACTURA}' => 'Serie + numar factura (ex: WF12345)',
                '{TOTAL}' => 'Total factura in lei',
                '{LINK_FACTURA}' => 'URL spre PDF-ul facturii Oblio',
            ],
            'igienizare_reminder' => [
                '{CLIENT}' => 'Denumirea clientului',
                '{DATA_SCADENTA}' => 'Data scadenta igienizare (format dd.mm.yyyy)',
                '{ADRESA}' => 'Adresa unde e dozatorul',
            ],
            'mentenanta_filtru_reminder' => [
                '{CLIENT}' => 'Denumirea clientului',
                '{DATA_SCADENTA}' => 'Data scadenta schimb filtre (format dd.mm.yyyy)',
                '{ADRESA}' => 'Adresa unde e dozatorul',
            ],
            default => [],
        };
    }
}
