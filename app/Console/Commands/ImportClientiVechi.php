<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Import one-time clienti din baza de date veche CI3 (flotamun_clienti.clienti).
// Ruleaza: php artisan import:clienti-vechi /calea/catre/clienti.sql
//
// Campuri ignorate (nu exista in schema noua):
//   nr_inregistrare_firma, nr_contract, ci (GDPR), id_deposit
//   email si telefon (nu exista in tabelul vechi clienti — sunt pe adresa_livrare)
//
// Transformari:
//   data (YYYY.MM.DD sau DD.MM.YYYY) → data_adaugare (DATE)
//   cui (PJ) sau cnp (PF)           → cif (NULL daca '0000000000000' sau gol)
//   reg_comert                      → reg_com
//   reziliat_observatii             → observatii_reziliere
//   string gol / '-'                → NULL pe campurile nullable

class ImportClientiVechi extends Command
{
    protected $signature = 'import:clienti-vechi {fisier : Calea absoluta catre fisierul clienti.sql}';
    protected $description = 'Import one-time clienti din baza de date CI3 veche';

    public function handle(): int
    {
        $fisier = $this->argument('fisier');
        if (! file_exists($fisier)) {
            $this->error("Fisierul nu exista: {$fisier}");
            return 1;
        }

        $this->info("Citesc fisierul SQL...");
        $sql = file_get_contents($fisier);

        $randuri = $this->parseazaInsert($sql);
        $this->info("Randuri gasite in SQL: " . count($randuri));

        $existenti = DB::table('clienti')->pluck('id')->flip()->all();
        $codClientExistenti = DB::table('clienti')->pluck('cod_client')->flip()->all();

        $importati = 0;
        $sariti    = 0;
        $duplicate = [];

        $bar = $this->output->createProgressBar(count($randuri));
        $bar->start();

        foreach ($randuri as $r) {
            $bar->advance();

            if (isset($existenti[$r['id']])) {
                $sariti++;
                continue;
            }

            // Cod client trebuie sa fie unic
            if (isset($codClientExistenti[$r['cod_client']])) {
                $duplicate[] = "Client {$r['id']} ({$r['denumire']}): cod_client='{$r['cod_client']}' deja exista";
                continue;
            }

            DB::table('clienti')->insert($r);
            $codClientExistenti[$r['cod_client']] = true;
            $importati++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=== REZUMAT IMPORT CLIENTI ===");
        $this->line("  Randuri in SQL:           " . count($randuri));
        $this->line("  Importati acum:            {$importati}");
        $this->line("  Sariti (existenti):        {$sariti}");
        $this->line("  Sariti (cod_client dupl.): " . count($duplicate));

        if (! empty($duplicate)) {
            $this->newLine();
            $this->warn("Clienti nesalvati din cauza cod_client duplicat:");
            foreach ($duplicate as $msg) {
                $this->line("  - {$msg}");
            }
        }

        if ($importati > 0) {
            $maxId = DB::table('clienti')->max('id');
            $this->newLine();
            $this->line("  Max ID acum: {$maxId}");
            $this->line("  Pe MySQL: ALTER TABLE clienti AUTO_INCREMENT = " . ($maxId + 1) . ";");
        }

        return 0;
    }

    private function parseazaInsert(string $sql): array
    {
        $randuri = [];

        $segmente = explode('INSERT INTO `clienti`', $sql);

        foreach (array_slice($segmente, 1) as $segment) {
            $start = strpos($segment, "\n(");
            if ($start === false) {
                $start = strpos($segment, '(');
                if ($start === false) continue;
            }
            $bloc = substr($segment, $start);

            foreach ($this->extrageToate($bloc) as $v) {
                if (count($v) < 24 || (int) $v[0] <= 0) {
                    continue;
                }

                $id     = (int) $v[0];
                $client = (int) $v[5]; // 1=PJ, 2=PF
                $cui    = $this->nullSirat($v[7]);
                $cnp    = $this->nullSirat($v[9]);

                // CIF: PJ foloseste cui, PF foloseste cnp (ignoram '0000000000000')
                $cif = null;
                if ($client === 1) {
                    $cif = $cui;
                } elseif ($client === 2) {
                    $cif = ($cnp !== null && $cnp !== '0000000000000') ? $cnp : null;
                }

                $now = now();

                $randuri[] = [
                    'id'                   => $id,
                    'cod_client'           => $this->nullSirat($v[4]) ?? "auto-{$id}",
                    'client'               => $client,
                    'denumire'             => trim($v[6]) ?: '-',
                    'cif'                  => $cif,
                    'reg_com'              => $this->nullSirat($v[8]),
                    'oras'                 => $this->nullSirat($v[11]),
                    'strada'               => $this->nullSirat($v[12]),
                    'nr'                   => $this->nullSirat($v[13]),
                    'bloc'                 => $this->nullSirat($v[14]),
                    'scara'                => $this->nullSirat($v[15]),
                    'etaj'                 => $this->nullSirat($v[16]),
                    'apartament'           => $this->nullSirat($v[17]),
                    'sector'               => $this->nullSirat($v[18]),
                    'interfon'             => $this->nullSirat($v[19]),
                    'email'                => null,
                    'telefon'              => null,
                    'observatii'           => $this->nullSirat($v[21]),
                    'reziliat'             => (int) $v[22] === 1 ? 1 : 0,
                    'observatii_reziliere' => $this->nullSirat($v[23]),
                    'data_adaugare'        => $this->parseazaData($v[2]),
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }
        }

        return $randuri;
    }

    // Converteste date din formatele YYYY.MM.DD sau DD.MM.YYYY sau D.MM.YYYY in YYYY-MM-DD.
    // Returneaza NULL pentru date invalide sau goale.
    private function parseazaData(string $val): ?string
    {
        $val = trim($val);
        if ($val === '' || $val === '-') {
            return null;
        }

        $parts = explode('.', $val);
        if (count($parts) !== 3) {
            return null;
        }

        [$p1, $p2, $p3] = $parts;

        if (strlen($p1) === 4 && ctype_digit($p1) && ctype_digit($p2) && ctype_digit($p3)) {
            // YYYY.MM.DD
            $year = (int) $p1; $month = (int) $p2; $day = (int) $p3;
        } elseif (strlen($p3) === 4 && ctype_digit($p1) && ctype_digit($p2) && ctype_digit($p3)) {
            // DD.MM.YYYY sau D.MM.YYYY
            $day = (int) $p1; $month = (int) $p2; $year = (int) $p3;
        } else {
            return null;
        }

        if ($year < 2000 || $year > 2030) return null;
        if ($month < 1 || $month > 12) return null;
        if ($day < 1 || $day > 31) return null;

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    // Returneaza NULL daca valoarea e goala sau un placeholder fara sens ('-', '0000000000000').
    private function nullSirat(?string $val): ?string
    {
        if ($val === null) return null;
        $val = trim($val);
        if ($val === '' || $val === '-') return null;
        return $val;
    }

    private function extrageToate(string $bloc): array
    {
        $rezultat = [];
        $len = strlen($bloc);
        $i   = 0;

        while ($i < $len) {
            while ($i < $len && $bloc[$i] !== '(') {
                $i++;
            }
            if ($i >= $len) break;
            $i++;

            $valori   = [];
            $valoare  = '';
            $inString = false;

            while ($i < $len) {
                $c = $bloc[$i];

                if ($inString) {
                    if ($c === '\\' && $i + 1 < $len && $bloc[$i + 1] === "'") {
                        $valoare .= "'";
                        $i += 2;
                        continue;
                    }
                    if ($c === "'") {
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

                if ($c === "'") { $inString = true; $i++; continue; }
                if ($c === ',') { $valori[] = trim($valoare); $valoare = ''; $i++; continue; }
                if ($c === ')') { $valori[] = trim($valoare); $i++; break; }

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
