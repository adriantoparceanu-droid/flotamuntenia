<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ContractClient;
use App\Models\SetariPlatforma;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Faza 6.2 — Service pentru gestionarea contractelor PDF per client.
 *
 * Workflow:
 *   1. Admin editeaza template-ul global din /setari/contract-template
 *      (TinyMCE + lista placeholdere disponibile). Stocat in
 *      `setari_platforma.contract_template_html`.
 *   2. La accesarea tab-ului Contract de pe Detalii client, daca clientul
 *      nu are contract, se genereaza din template (substituire placeholdere
 *      cu datele clientului) si se salveaza in `contracte_clienti.continut_html`.
 *   3. Admin poate edita contractul per client din TinyMCE — modificarile
 *      se persista pe coloana `continut_html`.
 *   4. Admin descarca PDF — DomPDF randeaza HTML-ul curent in PDF cu font
 *      DejaVu Sans (suport diacritice romanesti). PDF-ul NU e salvat fizic;
 *      se genereaza pe loc la fiecare cerere.
 */
class ContracteService
{
    /**
     * Lista placeholderelor disponibile cu descrieri umane.
     *
     * @return array<string, string> placeholder => descriere
     */
    public static function placeholdereDisponibile(): array
    {
        return [
            '{DENUMIRE}' => 'Denumirea clientului (firma sau nume complet PF)',
            '{TIP_CLIENT}' => 'Tipul clientului ("Persoana juridica" sau "Persoana fizica")',
            '{CIF_CNP}' => 'CIF (pentru PJ) sau CNP (pentru PF)',
            '{REG_COM}' => 'Registrul comertului (doar PJ — gol pentru PF)',
            '{COD_CLIENT}' => 'Codul intern al clientului (ex: C-000123)',
            '{ADRESA}' => 'Adresa sediu/domiciliu completa (strada, numar, bloc, sector, oras)',
            '{ORAS}' => 'Orasul clientului',
            '{TELEFON}' => 'Numarul de telefon al clientului',
            '{EMAIL}' => 'Adresa de email a clientului',
            '{DATA_CONTRACT}' => 'Data inregistrarii clientului in sistem',
            '{DATA_CURENTA}' => 'Data curenta (a generarii contractului)',
        ];
    }

    /**
     * Inlocuieste toate placeholderele intr-un sir HTML cu valorile reale ale clientului.
     */
    public static function inlocuiestePlaceholdere(string $html, Client $client): string
    {
        $valori = static::valoriPlaceholdere($client);

        return str_replace(array_keys($valori), array_values($valori), $html);
    }

    /**
     * Maparea placeholder => valoare reala pentru un client.
     *
     * @return array<string, string>
     */
    protected static function valoriPlaceholdere(Client $client): array
    {
        $estePJ = $client->isPJ();

        return [
            '{DENUMIRE}' => (string) ($estePJ ? $client->denumire : $client->denumire),
            '{TIP_CLIENT}' => $estePJ ? 'Persoana juridica' : 'Persoana fizica',
            '{CIF_CNP}' => (string) ($client->cif ?? ''),
            '{REG_COM}' => (string) ($estePJ ? ($client->reg_com ?? '') : ''),
            '{COD_CLIENT}' => (string) ($client->cod_client ?? ''),
            '{ADRESA}' => $client->adresaCompleta(),
            '{ORAS}' => (string) ($client->oras ?? ''),
            '{TELEFON}' => (string) ($client->telefon ?? ''),
            '{EMAIL}' => (string) ($client->email ?? ''),
            '{DATA_CONTRACT}' => static::formateazaData($client->data_adaugare),
            '{DATA_CURENTA}' => static::formateazaData(now()),
        ];
    }

    /**
     * Formateaza o data in stil romanesc compact (ex: "10 mai 2026").
     */
    protected static function formateazaData(Carbon|string|null $data): string
    {
        if (empty($data)) {
            return '';
        }
        $c = $data instanceof Carbon ? $data : Carbon::parse($data);
        $luni = ['ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie',
                 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];
        return $c->day . ' ' . $luni[$c->month - 1] . ' ' . $c->year;
    }

    /**
     * Returneaza template-ul global stocat sau cel implicit daca nu e setat.
     */
    public static function templateGlobal(): string
    {
        return SetariPlatforma::get(
            SetariPlatforma::CHEIE_CONTRACT_TEMPLATE,
            static::templateImplicit()
        );
    }

    /**
     * Salveaza un template global nou.
     */
    public static function salveazaTemplateGlobal(string $html): void
    {
        SetariPlatforma::set(SetariPlatforma::CHEIE_CONTRACT_TEMPLATE, $html);
    }

    /**
     * Obtine contractul unui client. Daca nu exista, il genereaza din template
     * si il persista. Returneaza modelul ContractClient.
     */
    public static function obtineContract(Client $client): ContractClient
    {
        $contract = $client->contract;

        if (! $contract) {
            $html = static::inlocuiestePlaceholdere(static::templateGlobal(), $client);
            $contract = ContractClient::create([
                'id_client' => $client->id,
                'continut_html' => $html,
            ]);
        }

        return $contract;
    }

    /**
     * Regenereaza contractul unui client din template-ul global curent (suprascrie
     * orice editare manuala).
     */
    public static function regenereazaDinTemplate(Client $client): ContractClient
    {
        $html = static::inlocuiestePlaceholdere(static::templateGlobal(), $client);
        $contract = static::obtineContract($client);
        $contract->update(['continut_html' => $html]);
        return $contract->refresh();
    }

    /**
     * Genereaza un PDF din HTML-ul dat folosind DomPDF cu font DejaVu Sans
     * (necesar pentru diacritice romanesti).
     *
     * Returneaza binary PDF (string) — apelantul decide cum il livreaza
     * (download / inline / storage).
     */
    public static function genereazaPdf(string $html, string $titlu = 'Contract'): string
    {
        $optiuni = new Options();
        $optiuni->set('defaultFont', 'DejaVu Sans');
        $optiuni->set('isRemoteEnabled', false);
        $optiuni->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($optiuni);

        // Wrapping intr-un document HTML complet cu CSS pentru DejaVu Sans pe body
        // (necesar ca DomPDF sa foloseasca fontul cu suport diacritice peste tot).
        $htmlComplet = static::ambalealHtmlComplet($html, $titlu);

        $dompdf->loadHtml($htmlComplet, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Ambaleaza un fragment HTML intr-un document complet cu CSS de baza.
     */
    protected static function ambalealHtmlComplet(string $continut, string $titlu): string
    {
        $titluEscape = htmlspecialchars($titlu, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>{$titluEscape}</title>
<style>
    @page { margin: 2cm 1.8cm; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 11pt; line-height: 1.5; color: #111; }
    h1, h2, h3 { font-family: "DejaVu Sans", sans-serif; }
    h1 { font-size: 16pt; text-align: center; margin: 0 0 4pt; }
    h2 { font-size: 13pt; margin: 14pt 0 6pt; }
    h3 { font-size: 11pt; margin: 10pt 0 4pt; }
    p { margin: 0 0 6pt; }
    table { border-collapse: collapse; width: 100%; }
    table td, table th { padding: 4pt 6pt; vertical-align: top; }
    .bordered td, .bordered th { border: 0.5pt solid #333; }
    .center { text-align: center; }
    .right { text-align: right; }
    .small { font-size: 9pt; }
    .muted { color: #555; }
    .signature { margin-top: 32pt; }
    .signature td { width: 50%; padding-top: 36pt; border-top: 0.5pt solid #333; }
</style>
</head>
<body>
{$continut}
</body>
</html>
HTML;
    }

    /**
     * Template HTML implicit pentru contractul de prestari servicii.
     * Folosit la prima initializare in /setari/contract-template (admin il
     * poate edita ulterior) si ca fallback daca tabelul setari_platforma e gol.
     *
     * Contine placeholdere pentru substituire automata; sectiunile sunt
     * generice si functioneaza pentru ambele tipuri de clienti (PJ + PF).
     */
    public static function templateImplicit(): string
    {
        return <<<HTML
<h1>CONTRACT DE PRESTARI SERVICII</h1>
<p class="center muted small">Nr. {COD_CLIENT} / {DATA_CONTRACT}</p>

<h2>1. PARTILE CONTRACTANTE</h2>

<p><strong>1.1. PRESTATOR:</strong> S.C. FLOTA MUNTENIA S.R.L., cu sediul in Bucuresti, inregistrata la Registrul Comertului sub nr. J40/XXXX/XXXX, CUI ROXXXXXXXX, cont bancar ROXX BTRL XXXX XXXX XXXX XXXX deschis la Banca Transilvania, reprezentata legal prin Administrator, denumita in continuare <em>Prestator</em>.</p>

<p><strong>1.2. BENEFICIAR:</strong> {DENUMIRE} ({TIP_CLIENT}), cu CIF/CNP <strong>{CIF_CNP}</strong>, {REG_COM} cu adresa in {ADRESA}, telefon {TELEFON}, email {EMAIL}, denumit in continuare <em>Beneficiar</em>.</p>

<h2>2. OBIECTUL CONTRACTULUI</h2>

<p>2.1. Obiectul prezentului contract il constituie prestarea de catre Prestator catre Beneficiar a serviciilor de livrare apa potabila imbuteliata in bidoane (19L si/sau 11L), precum si — dupa caz — punerea la dispozitie in custodie a dozatoarelor de apa si schimbarea filtrelor.</p>

<p>2.2. Cantitatile, frecventa si pretul livrarilor sunt stabilite per adresa de livrare conform configurarilor agreate (abonament lunar / livrare per bucata).</p>

<h2>3. DURATA CONTRACTULUI</h2>

<p>3.1. Prezentul contract se incheie pe o perioada nedeterminata, incepand cu data de <strong>{DATA_CONTRACT}</strong>.</p>

<p>3.2. Oricare dintre parti poate denunta unilateral contractul cu un preaviz scris de 30 de zile.</p>

<h2>4. PRETURI SI MODALITATI DE PLATA</h2>

<p>4.1. Preturile produselor si serviciilor sunt cele din oferta in vigoare la data livrarii. Pentru clientii cu abonament, pretul este cel agreat la configurarea abonamentului.</p>

<p>4.2. Plata se efectueaza in numerar la livrare, prin ordin de plata sau cu cardul, in baza facturii fiscale emise de Prestator. Termenul de plata pentru facturile cu OP este de 15 zile de la emitere.</p>

<h2>5. OBLIGATIILE PARTILOR</h2>

<p><strong>5.1. Prestatorul se obliga:</strong></p>
<ul>
    <li>sa livreze produsele la adresa indicata de Beneficiar in conditii de igiena si calitate;</li>
    <li>sa respecte programul de livrare agreat;</li>
    <li>sa puna la dispozitie dozatoare in custodie (acolo unde s-a agreat) si sa efectueze igienizarea / schimbul de filtre conform programului;</li>
    <li>sa emita factura fiscala pentru fiecare livrare sau lunar (dupa caz).</li>
</ul>

<p><strong>5.2. Beneficiarul se obliga:</strong></p>
<ul>
    <li>sa achite contravaloarea produselor si serviciilor in termenele agreate;</li>
    <li>sa returneze recipientele goale (bidoanele) la livrarile urmatoare;</li>
    <li>sa pastreze in conditii corespunzatoare dozatoarele primite in custodie;</li>
    <li>sa anunte Prestatorul cu cel putin 24 de ore inainte daca o livrare urmeaza sa fie reprogramata.</li>
</ul>

<h2>6. RECIPIENTE SI DOZATOARE IN CUSTODIE</h2>

<p>6.1. Bidoanele de apa sunt proprietatea Prestatorului si se acorda in custodie. Beneficiarul este responsabil de retururnarea lor — soldul de bidoane se urmareste in jurnalul de recipienti per adresa.</p>

<p>6.2. Dozatoarele acordate in custodie raman proprietatea Prestatorului. La incetarea contractului, Beneficiarul se obliga sa restituie dozatoarele in starea in care le-a primit (mai putin uzura normala).</p>

<h2>7. CLAUZE FINALE</h2>

<p>7.1. Orice litigiu decurgand din prezentul contract va fi solutionat pe cale amiabila; in caz contrar, va fi de competenta instantelor judecatoresti din Bucuresti.</p>

<p>7.2. Prezentul contract a fost incheiat astazi, <strong>{DATA_CURENTA}</strong>, in 2 (doua) exemplare originale, cate unul pentru fiecare parte.</p>

<table class="signature">
<tr>
    <td class="center"><strong>PRESTATOR</strong><br>S.C. FLOTA MUNTENIA S.R.L.<br>Administrator</td>
    <td class="center"><strong>BENEFICIAR</strong><br>{DENUMIRE}<br>{TIP_CLIENT}</td>
</tr>
</table>
HTML;
    }
}
