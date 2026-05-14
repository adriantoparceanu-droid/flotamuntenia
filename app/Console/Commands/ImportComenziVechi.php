<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Import one-time din baza de date veche CI3 (flotamun_clienti.comenzi).
// Ruleaza: php artisan import:comenzi-vechi /calea/catre/comenzi.sql
//
// Ce face:
//  - Parseaza INSERT-urile din SQL-ul exportat cu phpMyAdmin
//  - Sare comenzile deja existente (idempotent)
//  - Transforma: data_livrare YYYY/MM/DD→YYYY-MM-DD, id_masina 0→NULL,
//    status 'Aprobata'→NULL, string gol→NULL pe interval/luna
//  - Sare comenzile cu id_client sau id_adresa inexistent si le raporteaza
//  - La final afiseaza un rezumat complet

class ImportComenziVechi extends Command
{
    protected $signature = 'import:comenzi-vechi {fisier : Calea absoluta catre fisierul comenzi.sql}';
    protected $description = 'Import one-time comenzi din baza de date CI3 veche';

    public function handle(): int
    {
        $fisier = $this->argument('fisier');

        if (! file_exists($fisier)) {
            $this->error("Fisierul nu exista: {$fisier}");
            return 1;
        }

        $this->info("Citesc fisierul SQL...");
        $continut = file_get_contents($fisier);

        $randuri = $this->parseazaInsert($continut);
        $this->info("Randuri gasite in SQL: " . count($randuri));

        // Pre-incarca ID-urile existente pentru verificari rapide
        $comenziExistente = DB::table('comenzi')->pluck('id')->flip()->all();
        $clientiExistenti = DB::table('clienti')->pluck('id')->flip()->all();
        $adreseExistente  = DB::table('adresa_livrare')->pluck('id')->flip()->all();
        $masiniExistente  = DB::table('cars')->pluck('id')->flip()->all();

        $importate  = 0;
        $sarite     = 0;  // deja existente
        $lipsa_fk   = []; // id_client sau id_adresa inexistent

        $this->info("Importez...");
        $bar = $this->output->createProgressBar(count($randuri));
        $bar->start();

        foreach ($randuri as $r) {
            $bar->advance();

            // Sare daca exista deja
            if (isset($comenziExistente[$r['id']])) {
                $sarite++;
                continue;
            }

            // Verifica FK client
            if (! isset($clientiExistenti[$r['id_client']])) {
                $lipsa_fk[] = "Comanda {$r['id']}: id_client={$r['id_client']} nu exista in DB";
                continue;
            }

            // Verifica FK adresa
            if (! isset($adreseExistente[$r['id_adresa']])) {
                $lipsa_fk[] = "Comanda {$r['id']}: id_adresa={$r['id_adresa']} nu exista in DB";
                continue;
            }

            // Transforma id_masina: 0 => NULL, verifica existenta
            $idMasina = null;
            if ($r['id_masina'] !== 0 && $r['id_masina'] !== null) {
                $idMasina = isset($masiniExistente[$r['id_masina']]) ? $r['id_masina'] : null;
            }

            // Transforma data_livrare: YYYY/MM/DD => YYYY-MM-DD
            $dataLivrare = str_replace('/', '-', $r['data_livrare']);

            // luna_livrata: string gol => NULL (format YYYY/MM pastrat)
            $lunaLivrata = ($r['luna_livrata'] === '' || $r['luna_livrata'] === null)
                ? null
                : $r['luna_livrata'];

            // interval_livrare: string gol => NULL
            $intervalLivrare = ($r['interval_livrare'] === '' || $r['interval_livrare'] === null)
                ? null
                : trim($r['interval_livrare']);

            // status: 'Aprobata' => NULL (in schema noua NULL = aprobat de admin)
            $status = null;

            // observatii: string gol => NULL
            $observatii = ($r['observatii'] === '' || $r['observatii'] === null)
                ? null
                : trim($r['observatii']);

            // nume/telefon: string gol => NULL
            $nume    = ($r['nume'] === '' || $r['nume'] === null)   ? null : trim($r['nume']);
            $telefon = ($r['telefon'] === '' || $r['telefon'] === null) ? null : trim($r['telefon']);

            DB::table('comenzi')->insert([
                'id'                  => $r['id'],
                'id_client'           => $r['id_client'],
                'id_adresa'           => $r['id_adresa'],
                'id_masina'           => $idMasina,
                'id_depozit'          => null,
                'tip_comanda'         => $r['tip_comanda'],
                'nr_recipienti'       => 0,
                'nr_pahare'           => 0,
                'id_modalitate_plata' => $r['id_modalitate_plata'],
                'data_livrare'        => $dataLivrare,
                'interval_livrare'    => $intervalLivrare,
                'livrat'              => $r['livrat'],
                'achitat'             => $r['achitat'],
                'invoice_generated'   => $r['invoice_generated'],
                'factura_serie'       => null,
                'factura_numar'       => null,
                'factura_link'        => null,
                'factura_furnizor'    => null,
                'luna_livrata'        => $lunaLivrata,
                'status'              => $status,
                'motiv_respingere'    => null,
                'data_respingere'     => null,
                'aprobat_de'          => null,
                'id_utilizator'       => null,
                'nume'                => $nume,
                'telefon'             => $telefon,
                'observatii'          => $observatii,
                'ordine_traseu'       => $r['ordine_traseu'],
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $importate++;
        }

        $bar->finish();
        $this->newLine(2);

        // Rezumat
        $this->info("=== REZUMAT IMPORT ===");
        $this->line("  Randuri in SQL:      " . count($randuri));
        $this->line("  Importate acum:      {$importate}");
        $this->line("  Sarite (existente):  {$sarite}");
        $this->line("  Sarite (FK lipsa):   " . count($lipsa_fk));

        if (! empty($lipsa_fk)) {
            $this->newLine();
            $this->warn("Comenzi nesalvate din cauza FK lipsa (importa clientii/adresele mai intai):");
            foreach ($lipsa_fk as $msg) {
                $this->line("  - {$msg}");
            }
        }

        if ($importate > 0) {
            $this->newLine();
            $this->info("Actualizeaza AUTO_INCREMENT:");
            $maxId = DB::table('comenzi')->max('id');
            $this->line("  MAX id curent in DB: {$maxId}");
            // SQLite nu are AUTO_INCREMENT; pe MySQL/MariaDB ruleaza manual:
            $this->line("  Pe MySQL/MariaDB ruleaza: ALTER TABLE comenzi AUTO_INCREMENT = " . ($maxId + 1) . ";");
        }

        return 0;
    }

    // Parseaza toate randurile din blocurile INSERT ale fisierului SQL phpMyAdmin.
    // Schema veche: id, id_client, id_adresa, tip_comanda, modalitate_plata (ignorat),
    //   id_modalitate_plata, achitat, suma_incasata (ignorat), data_livrare,
    //   luna_livrata, interval_livrare, nume, telefon, status (ignorat—toate 'Aprobata'),
    //   observatii, id_masina, ordine_traseu, livrat, invoice_generated
    //
    // Abordare: parseaza VALUES tuple caracter cu caracter pentru a suporta
    // strings cu newline-uri, ghilimele escapate ('') etc.
    private function parseazaInsert(string $sql): array
    {
        $randuri = [];

        $segmente = explode('INSERT INTO `comenzi`', $sql);

        foreach (array_slice($segmente, 1) as $segment) {
            $start = strpos($segment, "\n(");
            if ($start === false) {
                $start = strpos($segment, '(');
                if ($start === false) continue;
            }
            $bloc = substr($segment, $start);
            $tuples = $this->extrageToate($bloc);
            foreach ($tuples as $valori) {
                if (count($valori) < 19) {
                    continue;
                }

                // Sare randurile false (artefacte din ALTER TABLE, PRIMARY KEY etc.)
                if ((int) $valori[0] <= 0 || (int) $valori[1] <= 0 || (int) $valori[2] <= 0) {
                    continue;
                }

                $randuri[] = [
                    'id'                  => (int) $valori[0],
                    'id_client'           => (int) $valori[1],
                    'id_adresa'           => (int) $valori[2],
                    'tip_comanda'         => $valori[3],
                    // $valori[4] = modalitate_plata (string) — ignorat
                    'id_modalitate_plata' => (int) $valori[5],
                    'achitat'             => (int) $valori[6],
                    // $valori[7] = suma_incasata — ignorat
                    'data_livrare'        => $valori[8],
                    'luna_livrata'        => $valori[9],
                    'interval_livrare'    => $valori[10],
                    'nume'                => $valori[11],
                    'telefon'             => $valori[12],
                    // $valori[13] = status — ignorat (toate 'Aprobata')
                    'observatii'          => $valori[14],
                    'id_masina'           => (int) $valori[15],
                    'ordine_traseu'       => (int) $valori[16],
                    'livrat'              => (int) $valori[17],
                    'invoice_generated'   => (int) $valori[18],
                ];
            }
        }

        return $randuri;
    }

    // Extrage toate tuple-urile (...) dintr-un bloc VALUES si returneaza
    // un array de array-uri de valori SQL (string sau int, fara ghilimele).
    private function extrageToate(string $bloc): array
    {
        $rezultat = [];
        $len = strlen($bloc);
        $i = 0;

        while ($i < $len) {
            // Cauta inceputul unui tuple '('
            while ($i < $len && $bloc[$i] !== '(') {
                $i++;
            }
            if ($i >= $len) {
                break;
            }
            $i++; // sare peste '('

            $valori = [];
            $valoare = '';
            $inString = false;

            while ($i < $len) {
                $c = $bloc[$i];

                if ($inString) {
                    // Backslash-escape: \' (phpMyAdmin il poate folosi)
                    if ($c === '\\' && $i + 1 < $len && $bloc[$i + 1] === "'") {
                        $valoare .= "'";
                        $i += 2;
                        continue;
                    }
                    if ($c === "'") {
                        // Doubled quote escape: ''
                        if ($i + 1 < $len && $bloc[$i + 1] === "'") {
                            $valoare .= "'";
                            $i += 2;
                            continue;
                        }
                        $inString = false;
                        $i++;
                        continue;
                    }
                    $valoare .= $c;
                    $i++;
                    continue;
                }

                if ($c === "'") {
                    $inString = true;
                    $i++;
                    continue;
                }

                if ($c === ',') {
                    $valori[] = trim($valoare);
                    $valoare = '';
                    $i++;
                    continue;
                }

                if ($c === ')') {
                    $valori[] = trim($valoare);
                    $i++;
                    break;
                }

                $valoare .= $c;
                $i++;
            }

            if (! empty($valori)) {
                $rezultat[] = $valori;
            }
        }

        return $rezultat;
    }
}
