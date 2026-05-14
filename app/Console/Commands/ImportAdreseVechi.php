<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Import one-time adrese_livrare din baza CI3 (flotamun_clienti.adresa_livrare).
// Ruleaza: php artisan import:adrese-vechi /calea/catre/adresa_livrare.sql
//
// Campuri ignorate (nu exista in schema noua):
//   telefon, email, observatii (din adresa_livrare veche — nu au corespondent)
//
// Transformari:
//   persoana_contact    → denumire (eticheta punctului de livrare)
//   numar               → nr
//   coordonate (string) → lat + lng (DECIMAL, conform regulii §8.9)
//   data_prima_comanda  → data_adaugare (YYYY/MM → YYYY-MM-01, sau NULL)
//   string gol / '-'    → NULL pe campurile nullable
//   activ               → true (default, toate adresele vechi sunt active)

class ImportAdreseVechi extends Command
{
    protected $signature = 'import:adrese-vechi {fisier : Calea absoluta catre fisierul adresa_livrare.sql}';
    protected $description = 'Import one-time adrese_livrare din baza de date CI3 veche';

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

        $existente        = DB::table('adresa_livrare')->pluck('id')->flip()->all();
        $clientiExistenti = DB::table('clienti')->pluck('id')->flip()->all();

        $importate   = 0;
        $sarite      = 0;
        $lipsa_client = [];

        $bar = $this->output->createProgressBar(count($randuri));
        $bar->start();

        foreach ($randuri as $r) {
            $bar->advance();

            if (isset($existente[$r['id']])) {
                $sarite++;
                continue;
            }

            if (! isset($clientiExistenti[$r['id_client']])) {
                $lipsa_client[] = "Adresa {$r['id']}: id_client={$r['id_client']} nu exista in DB";
                continue;
            }

            DB::table('adresa_livrare')->insert($r);
            $importate++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=== REZUMAT IMPORT ADRESE ===");
        $this->line("  Randuri in SQL:          " . count($randuri));
        $this->line("  Importate acum:           {$importate}");
        $this->line("  Sarite (existente):       {$sarite}");
        $this->line("  Sarite (client lipsa):    " . count($lipsa_client));

        if (! empty($lipsa_client)) {
            $this->newLine();
            $this->warn("Adrese nesalvate — importa clientii mai intai:");
            foreach ($lipsa_client as $msg) {
                $this->line("  - {$msg}");
            }
        }

        if ($importate > 0) {
            $maxId = DB::table('adresa_livrare')->max('id');
            $this->newLine();
            $this->line("  Max ID acum: {$maxId}");
            $this->line("  Pe MySQL: ALTER TABLE adresa_livrare AUTO_INCREMENT = " . ($maxId + 1) . ";");
        }

        return 0;
    }

    private function parseazaInsert(string $sql): array
    {
        $randuri = [];

        // phpMyAdmin imparte exportul in blocuri de ~280 randuri, fiecare cu propriul INSERT INTO.
        // Separam pe keyword si procesam fiecare bucata individual.
        $segmente = explode('INSERT INTO `adresa_livrare`', $sql);

        $now = now();

        // Primul segment e header-ul SQL (CREATE TABLE etc.) — il sarim
        foreach (array_slice($segmente, 1) as $segment) {
            // Gasim prima '(' din date (dupa lista de coloane si VALUES)
            $start = strpos($segment, "\n(");
            if ($start === false) {
                $start = strpos($segment, '(');
                if ($start === false) continue;
            }
            $bloc = substr($segment, $start);

            foreach ($this->extrageToate($bloc) as $v) {
                if (count($v) < 17 || (int) $v[0] <= 0 || (int) $v[1] <= 0) {
                    continue;
                }

                [$lat, $lng] = $this->parseazaCoordonate($v[10]);

                // denumire: persoana_contact daca e semnificativa, altfel oras - strada
                $denumire = $this->nullSirat($v[12]);
                if ($denumire === null) {
                    $oras   = $this->nullSirat($v[2]) ?? '';
                    $strada = $this->nullSirat($v[3]) ?? '';
                    $denumire = trim($oras . ' ' . $strada) ?: 'Adresa';
                }

                $randuri[] = [
                    'id'             => (int) $v[0],
                    'id_client'      => (int) $v[1],
                    'denumire'       => $denumire,
                    'oras'           => $this->nullSirat($v[2]),
                    'strada'         => $this->nullSirat($v[3]),
                    'nr'             => $this->nullSirat($v[4]),
                    'bloc'           => $this->nullSirat($v[5]),
                    'scara'          => $this->nullSirat($v[6]),
                    'etaj'           => $this->nullSirat($v[7]),
                    'apartament'     => $this->nullSirat($v[8]),
                    'sector'         => $this->nullSirat($v[9]),
                    'interfon'       => $this->nullSirat($v[11]),
                    'lat'            => $lat,
                    'lng'            => $lng,
                    'activ'          => 1,
                    'data_adaugare'  => $this->parseazaDataPrimaComanda($v[16]),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        return $randuri;
    }

    // Parseaza "lat, lng" in doua valori DECIMAL. Returneaza [null, null] daca invalid.
    private function parseazaCoordonate(string $val): array
    {
        $val = trim($val);
        if ($val === '' || $val === '-' || ! str_contains($val, ',')) {
            return [null, null];
        }

        $parts = explode(',', $val, 2);
        $lat   = trim($parts[0]);
        $lng   = trim($parts[1]);

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return [null, null];
        }

        $latF = (float) $lat;
        $lngF = (float) $lng;

        // Validare range Romania
        if ($latF < 43.0 || $latF > 48.5 || $lngF < 20.0 || $lngF > 30.0) {
            return [null, null];
        }

        return [$latF, $lngF];
    }

    // Converteste YYYY/MM in YYYY-MM-01 (prima zi a lunii). Returneaza NULL daca gol.
    private function parseazaDataPrimaComanda(string $val): ?string
    {
        $val = trim($val);
        if ($val === '') return null;

        if (preg_match('/^(\d{4})\/(\d{2})$/', $val, $m)) {
            $year  = (int) $m[1];
            $month = (int) $m[2];
            if ($year >= 2015 && $year <= 2030 && $month >= 1 && $month <= 12) {
                return sprintf('%04d-%02d-01', $year, $month);
            }
        }

        return null;
    }

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
                    // Backslash-escape: \' in string (phpMyAdmin il poate folosi)
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
