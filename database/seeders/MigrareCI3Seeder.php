<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Importă datele din baza CI3 (flotamun_clienti.sql) în schema Laravel nouă.
 *
 * Workflow:
 *   php artisan migrate:fresh
 *   php artisan db:seed --class=MigrareCI3Seeder
 *
 * Fișierul SQL trebuie să existe la: analiza-app/flotamun_clienti.sql
 * (relativ față de rădăcina monorepo-ului, nu față de app-noua/)
 */
class MigrareCI3Seeder extends Seeder
{
    private string $sqlPath;

    /** @var array<string, list<array<string, mixed>>> */
    private array $date = [];

    public function run(): void
    {
        // Căutăm SQL-ul în mai multe locații (local vs. producție)
        $candidati = [
            storage_path('app/flotamun_clienti.sql'),          // producție: upload în storage/app/
            base_path('../analiza-app/flotamun_clienti.sql'),   // local: monorepo
        ];
        $this->sqlPath = '';
        foreach ($candidati as $cale) {
            if (file_exists($cale)) { $this->sqlPath = $cale; break; }
        }

        if (!$this->sqlPath) {
            $this->command->error('Fișierul flotamun_clienti.sql nu a fost găsit.');
            $this->command->error('Pe producție: încărcați-l în storage/app/flotamun_clienti.sql');
            $this->command->error('Local: asigurați-vă că analiza-app/flotamun_clienti.sql există.');
            return;
        }
        $this->command->line("SQL: {$this->sqlPath}");

        $this->command->info('─── Parsare fișier SQL ───');
        $this->parseSql();

        $this->command->info('─── Dezactivare FK checks ───');
        $this->fkOff();

        try {
            $this->curata();
            $this->migTva();
            $this->migDeposit();
            $this->migCostCategories();
            $this->migCostProducts();
            $this->migCars();
            $this->migClienti();
            $this->migAdreseLivrare();
            $this->migProdusCfg();
            $this->migUtilizatori();
            $this->migComenzi();
            $this->migComenziProduse();
            $this->migStoc();
            $this->migRecipienti();
            $this->migDozator();
        } finally {
            $this->fkOn();
        }

        $this->command->info('');
        $this->command->info('✅  Migrare CI3 → Laravel completă!');
        $this->command->warn('⚠   Parola temporară pentru TOȚI utilizatorii: Flotamuntenia2026!');
        $this->command->warn('    Schimbați parolele imediat după prima autentificare.');
    }

    // ─── Parser SQL ────────────────────────────────────────────────────────────

    private function parseSql(): void
    {
        $continut = file_get_contents($this->sqlPath);

        // Găsim toate blocurile INSERT [IGNORE] INTO `tabel` (cols) VALUES ...;
        preg_match_all(
            '/INSERT(?:\s+IGNORE)?\s+INTO\s+`(\w+)`\s+\(([^)]+)\)\s+VALUES\s*([\s\S]+?);(?=\s*(?:--|\/\*|$))/m',
            $continut,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $m) {
            $tabel = $m[1];
            $cols  = array_map(fn($c) => trim($c, ' `'), explode(',', $m[2]));
            $randuri = $this->parseValues($m[3]);

            foreach ($randuri as $rand) {
                if (count($rand) !== count($cols)) continue;
                $this->date[$tabel][] = array_combine($cols, $rand);
            }
        }

        $this->command->info('Tabele găsite în SQL:');
        foreach ($this->date as $tabel => $randuri) {
            $this->command->line(sprintf('  %-30s %d rânduri', $tabel, count($randuri)));
        }
    }

    /** Tokenizer pentru VALUES — gestionează string-uri, NULL, escape-uri MySQL */
    private function parseValues(string $valuesStr): array
    {
        $randuri = [];
        $curent  = [];
        $valoare = '';
        $inStr   = false;
        $adancime = 0;
        $i = 0;
        $len = strlen($valuesStr);

        while ($i < $len) {
            $c = $valuesStr[$i];

            if (!$inStr) {
                if ($c === '(' && $adancime === 0) {
                    $adancime = 1;
                    $curent   = [];
                    $valoare  = '';
                    $i++;
                    continue;
                }

                if ($adancime > 0) {
                    if ($c === '(') {
                        $adancime++;
                        $valoare .= $c;
                        $i++;
                        continue;
                    }
                    if ($c === ')') {
                        $adancime--;
                        if ($adancime === 0) {
                            $curent[] = $this->parseValoare($valoare);
                            $randuri[] = $curent;
                            $valoare = '';
                            $i++;
                            continue;
                        }
                        $valoare .= $c;
                        $i++;
                        continue;
                    }
                    if ($c === ',' && $adancime === 1) {
                        $curent[] = $this->parseValoare($valoare);
                        $valoare  = '';
                        $i++;
                        continue;
                    }
                    if ($c === "'") {
                        $inStr   = true;
                        $valoare .= $c;
                        $i++;
                        continue;
                    }
                }
            } else {
                if ($c === '\\' && $i + 1 < $len) {
                    $valoare .= $c . $valuesStr[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($c === "'") {
                    $inStr   = false;
                    $valoare .= $c;
                    $i++;
                    continue;
                }
            }

            if ($adancime > 0) $valoare .= $c;
            $i++;
        }

        return $randuri;
    }

    private function parseValoare(string $raw): mixed
    {
        $raw = trim($raw);
        if ($raw === 'NULL') return null;
        if (str_starts_with($raw, "'") && str_ends_with($raw, "'")) {
            $interior = substr($raw, 1, -1);
            return strtr($interior, [
                "\\'" => "'", '\\"' => '"', '\\\\' => '\\',
                '\\n' => "\n", '\\r' => "\r", '\\t' => "\t", '\\0' => "\0",
            ]);
        }
        if (is_numeric($raw)) return $raw + 0;
        return $raw;
    }

    // ─── FK helpers ────────────────────────────────────────────────────────────

    private function fkOff(): void
    {
        if ($this->esteMySQL()) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA foreign_keys = OFF');
        }
    }

    private function fkOn(): void
    {
        if ($this->esteMySQL()) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function esteMySQL(): bool
    {
        return DB::getDriverName() === 'mysql';
    }

    // ─── Curățare ──────────────────────────────────────────────────────────────

    private function curata(): void
    {
        $this->command->info('─── Curățare tabele existente (ordine inversă FK) ───');
        $tabele = [
            'stoc', 'recipienti', 'dozator', 'comenzi_produse', 'comenzi',
            'produs', 'adresa_livrare', 'users', 'clienti', 'cars',
            'cost_products', 'cost_categories', 'deposits', 'tva',
        ];
        foreach ($tabele as $tabel) {
            $n = DB::table($tabel)->delete();
            $this->command->line("  DELETE {$tabel}: {$n} rânduri");
        }
    }

    // ─── Migrare tabele de referință ───────────────────────────────────────────

    private function migTva(): void
    {
        $this->command->info('─── TVA ───');
        $insert = [];
        foreach ($this->date['tva'] ?? [] as $r) {
            $valoare = (float) $r['tva'];
            $denumire = trim($r['observatii'] ?? '');
            if (empty($denumire)) $denumire = $valoare . '%';

            $insert[] = [
                'id'         => $r['id'],
                'valoare'    => $valoare,
                'denumire'   => $denumire,
                'activ'      => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $this->inserteaza('tva', $insert);
    }

    private function migDeposit(): void
    {
        $this->command->info('─── Depozite ───');
        $insert = [];
        foreach ($this->date['deposit'] ?? [] as $r) {
            $insert[] = [
                'id'         => $r['id'],
                'denumire'   => $r['nume_deposit'],
                'adresa'     => $r['address'] ?? '',
                'activ'      => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $this->inserteaza('deposits', $insert);
    }

    private function migCostCategories(): void
    {
        $this->command->info('─── Categorii produse ───');
        $insert = [];
        foreach ($this->date['cost_categories'] ?? [] as $r) {
            $insert[] = [
                'id'         => $r['id'],
                'denumire'   => $r['nume'],
                'activ'      => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $this->inserteaza('cost_categories', $insert);
    }

    private function migCostProducts(): void
    {
        $this->command->info('─── Produse catalog ───');

        // Harta valoare_tva → id_tva (din ce am inserat mai sus)
        $hartaTva = DB::table('tva')->pluck('id', 'valoare')->toArray();
        // Exemplu: [0 => 1, 11 => 2, 21 => 3]

        $insert = [];
        foreach ($this->date['cost_products'] ?? [] as $r) {
            $tvaProcent = (int) $r['tva'];
            $idTva = $hartaTva[$tvaProcent] ?? null;

            $insert[] = [
                'id'          => $r['id'],
                'id_category' => $r['id_categorie'],
                'id_tva'      => $idTva,
                'denumire'    => $r['nume'],
                'pret'        => 0.00,
                'activ'       => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }
        $this->inserteaza('cost_products', $insert);
    }

    private function migCars(): void
    {
        $this->command->info('─── Mașini ───');
        $depoziteIds = DB::table('deposits')->pluck('id')->toArray();
        $insert = [];

        foreach ($this->date['car'] ?? [] as $r) {
            $idDepozit = in_array((int) $r['id_deposit'], $depoziteIds) ? (int) $r['id_deposit'] : null;
            $culoare   = trim($r['color'] ?? '');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $culoare)) $culoare = '#3b82f6';

            $insert[] = [
                'id'               => $r['id'],
                'denumire'         => trim($r['nume']),
                'nr_inmatriculare' => $this->nullIfEmpty($r['car_number'] ?? null) ?? ('N/A-' . $r['id']),
                'id_depozit'       => $idDepozit,
                'culoare'          => $culoare,
                'activ'            => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }
        $this->inserteaza('cars', $insert);
    }

    // ─── Migrare date operaționale ─────────────────────────────────────────────

    private function migClienti(): void
    {
        $this->command->info('─── Clienți ───');

        // Extragem telefon/email din prima adresă a fiecărui client
        $telAdresa = [];
        $emailAdresa = [];
        foreach ($this->date['adresa_livrare'] ?? [] as $a) {
            $cid = (int) $a['id_client'];
            $tel = trim($a['telefon'] ?? '');
            $em  = trim($a['email'] ?? '');
            if (empty($telAdresa[$cid]) && $tel && $tel !== '-') {
                $telAdresa[$cid] = $tel;
            }
            if (empty($emailAdresa[$cid]) && $em && $em !== '-') {
                $emailAdresa[$cid] = $em;
            }
        }

        $insert = [];
        $now    = now();

        foreach ($this->date['clienti'] ?? [] as $r) {
            $id = (int) $r['id'];
            $codClient = trim($r['cod_client'] ?? '');
            if (empty($codClient)) {
                $codClient = 'C-' . str_pad($id, 6, '0', STR_PAD_LEFT);
            }

            $insert[] = [
                'id'                  => $id,
                'cod_client'          => $codClient,
                'client'              => (int) $r['client'],   // 1=PJ, 2=PF
                'denumire'            => trim($r['nume'] ?? '') ?: '(fără denumire)',
                'cif'                 => $this->nullIfEmpty($r['cui'] ?? null),
                'reg_com'             => $this->nullIfEmpty($r['reg_comert'] ?? null),
                'oras'                => $this->nullIfEmpty($r['oras'] ?? null),
                'strada'              => $this->nullIfEmpty($r['strada'] ?? null),
                'nr'                  => $this->nullIfEmpty($r['nr'] ?? null),
                'bloc'                => $this->nullIfEmpty($r['bloc'] ?? null),
                'scara'               => $this->nullIfEmpty($r['scara'] ?? null),
                'etaj'                => $this->nullIfEmpty($r['etaj'] ?? null),
                'apartament'          => $this->nullIfEmpty($r['apartament'] ?? null),
                'sector'              => $this->nullIfEmpty($r['sector'] ?? null),
                'interfon'            => $this->nullIfEmpty($r['interfon'] ?? null),
                'email'               => $emailAdresa[$id] ?? null,
                'telefon'             => $telAdresa[$id] ?? null,
                'observatii'          => $this->nullIfEmpty($r['observatii'] ?? null),
                'reziliat'            => (bool) ($r['reziliat'] ?? 0),
                'observatii_reziliere'=> $this->nullIfEmpty($r['reziliat_observatii'] ?? null),
                'data_adaugare'       => $this->parseData($r['data'] ?? null),
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        }

        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table('clienti')->insert($chunk);
        }
        $this->command->line('  clienti: ' . count($insert) . ' rânduri');
    }

    private function migAdreseLivrare(): void
    {
        $this->command->info('─── Adrese livrare ───');
        $insert = [];
        $now    = now();

        foreach ($this->date['adresa_livrare'] ?? [] as $r) {
            [$lat, $lng] = $this->parseCoordonate($r['coordonate'] ?? '');

            // Generăm denumire din persoana_contact sau oras+strada
            $denumire = trim($r['persoana_contact'] ?? '');
            if (empty($denumire) || $denumire === '-') {
                $oras   = trim($r['oras'] ?? '');
                $strada = trim($r['strada'] ?? '');
                $denumire = trim($oras . ($strada ? ' - ' . $strada : ''));
            }
            if (empty($denumire)) {
                $denumire = 'Adresă #' . $r['id'];
            }

            // data_prima_comanda: "2026/02" → "2026-02-01"
            $dataAdaugare = null;
            $dp = trim($r['data_prima_comanda'] ?? '');
            if ($dp) {
                $p = explode('/', $dp);
                if (count($p) >= 2) {
                    $dataAdaugare = $p[0] . '-' . str_pad($p[1], 2, '0', STR_PAD_LEFT) . '-01';
                }
            }

            $insert[] = [
                'id'            => $r['id'],
                'id_client'     => $r['id_client'],
                'denumire'      => $denumire,
                'oras'          => $this->nullIfEmpty($r['oras'] ?? null),
                'strada'        => $this->nullIfEmpty($r['strada'] ?? null),
                'nr'            => $this->nullIfEmpty($r['numar'] ?? null),
                'bloc'          => $this->nullIfEmpty($r['bloc'] ?? null),
                'scara'         => $this->nullIfEmpty($r['scara'] ?? null),
                'etaj'          => $this->nullIfEmpty($r['etaj'] ?? null),
                'apartament'    => $this->nullIfEmpty($r['apartament'] ?? null),
                'sector'        => $this->nullIfEmpty($r['sector'] ?? null),
                'interfon'      => $this->nullIfEmpty($r['interfon'] ?? null),
                'lat'           => $lat,
                'lng'           => $lng,
                'activ'         => 1,
                'data_adaugare' => $dataAdaugare,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table('adresa_livrare')->insert($chunk);
        }
        $this->command->line('  adresa_livrare: ' . count($insert) . ' rânduri');
    }

    private function migProdusCfg(): void
    {
        $this->command->info('─── Configurare livrare (produs) ───');

        $adreseIds  = DB::table('adresa_livrare')->pluck('id')->flip()->toArray();
        $clientiIds = DB::table('clienti')->pluck('id')->flip()->toArray();
        $insert     = [];
        $now        = now();
        $adreseVazute = []; // UNIQUE pe id_adresa

        foreach ($this->date['produs'] ?? [] as $r) {
            $idAdresa = (int) $r['id_adresa'];
            $idClient = (int) $r['id_client'];

            if (!isset($adreseIds[$idAdresa]) || !isset($clientiIds[$idClient])) continue;
            if (isset($adreseVazute[$idAdresa])) continue;
            $adreseVazute[$idAdresa] = true;

            $abonament = (int) $r['abonament']; // 0=per_bucata, 1=abonament
            $tipAb     = trim($r['tip_abonament'] ?? '');
            $cantitate = (int) ($r['cantitate'] ?? 0);

            // Extragem nr bidoane 19L din "A/3" → 3 dacă cantitate e 0
            $nrBidoane = $cantitate;
            if ($nrBidoane === 0 && preg_match('/\/(\d+)/', $tipAb, $pm)) {
                $nrBidoane = (int) $pm[1];
            }

            $pret = 0.0;
            if ($abonament === 1) {
                $pret = (float) ($r['pret_abonament'] ?? 0);
            } else {
                $pret = (float) ($r['pret_buc'] ?? 0);
            }

            $pretSuplimentar = null;
            $cs = trim($r['consum_suplimentar'] ?? '');
            if ($cs !== '' && is_numeric($cs)) {
                $pretSuplimentar = (float) $cs;
            }

            $insert[] = [
                'id'                   => $r['id'],
                'id_adresa'            => $idAdresa,
                'id_client'            => $idClient,
                'abonament'            => $abonament,
                'denumire_abonament'   => $tipAb ?: null,
                'nr_bidoane'           => $nrBidoane,
                'nr_bidoane_11l'       => 0,
                'pret'                 => $pret,
                'pret_11l'             => 0.0,
                'pret_suplimentar_19l' => $pretSuplimentar,
                'pret_suplimentar_11l' => null,
                'frecventa'            => null,
                'zi_livrare'           => $this->parseData($r['data_livrare'] ?? null, '/'),
                'id_masina'            => null,
                'id_depozit'           => null,
                'observatii'           => null,
                'created_at'           => $now,
                'updated_at'           => $now,
            ];
        }

        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table('produs')->insert($chunk);
        }
        $this->command->line('  produs: ' . count($insert) . ' rânduri');
    }

    private function migUtilizatori(): void
    {
        $this->command->info('─── Utilizatori ───');

        $carIds     = DB::table('cars')->pluck('id')->toArray();
        $clientiIds = DB::table('clienti')->pluck('id')->toArray();
        $insert     = [];
        $now        = now();
        $emailVazute    = [];
        $usernameVazute = [];

        foreach ($this->date['utilizatori'] ?? [] as $r) {
            $email = trim($r['email'] ?? '');
            // Email-uri invalide (fără @, sau cu spații)
            if (!$email || !str_contains($email, '@') || str_contains($email, ' ')) {
                $email = 'user' . $r['id'] . '@flotamuntenia.ro';
            }
            // Duplicate email
            if (isset($emailVazute[$email])) {
                $email = 'user' . $r['id'] . '@flotamuntenia.ro';
            }
            $emailVazute[$email] = true;

            // Username unic — multi șoferi au 'masina' ca username generic
            $username = trim($r['utilizator'] ?? '') ?: $email;
            if (isset($usernameVazute[$username])) {
                $username = $username . '_' . $r['id'];
            }
            $usernameVazute[$username] = true;

            $idMasina = (int) ($r['id_masina'] ?? 0);
            $idMasina = ($idMasina && in_array($idMasina, $carIds)) ? $idMasina : null;

            $idClient = $r['id_client'] ? (int) $r['id_client'] : null;
            $idClient = ($idClient && in_array($idClient, $clientiIds)) ? $idClient : null;

            $insert[] = [
                'id'                 => $r['id'],
                'name'               => trim($r['nume'] ?? '') ?: 'Utilizator',
                'email'              => $email,
                'username'           => $username,
                'password'           => Hash::make('Flotamuntenia2026!'),
                'tip'                => (int) $r['tip'],
                'confirmat'          => (int) ($r['confirmat'] ?? 1),
                'id_masina'          => $idMasina,
                'id_client'          => $idClient,
                'email_verified_at'  => $now,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        $this->inserteaza('users', $insert);
    }

    private function migComenzi(): void
    {
        $this->command->info('─── Comenzi ───');

        $adreseIds  = DB::table('adresa_livrare')->pluck('id')->flip()->toArray();
        $clientiIds = DB::table('clienti')->pluck('id')->flip()->toArray();
        $carIds     = DB::table('cars')->pluck('id')->flip()->toArray();
        $insert     = [];
        $now        = now();
        $skiped     = 0;

        foreach ($this->date['comenzi'] ?? [] as $r) {
            $idAdresa = (int) $r['id_adresa'];
            $idClient = (int) $r['id_client'];

            if (!isset($adreseIds[$idAdresa]) || !isset($clientiIds[$idClient])) {
                $skiped++;
                continue;
            }

            $tipComanda = $r['tip_comanda'] ?? 'fara abonament';
            if (!in_array($tipComanda, ['abonament', 'consum suplimentar', 'fara abonament'])) {
                $tipComanda = 'fara abonament';
            }

            $idMasina = (int) ($r['id_masina'] ?? 0);
            $idMasina = ($idMasina && isset($carIds[$idMasina])) ? $idMasina : null;

            $dataLivrare = $this->parseData($r['data_livrare'] ?? null, '/');
            if (!$dataLivrare) $dataLivrare = date('Y-m-d'); // fallback la azi

            $insert[] = [
                'id'                  => $r['id'],
                'id_client'           => $idClient,
                'id_adresa'           => $idAdresa,
                'id_masina'           => $idMasina,
                'id_depozit'          => null,
                'tip_comanda'         => $tipComanda,
                'nr_recipienti'       => 0,
                'nr_pahare'           => 0,
                'id_modalitate_plata' => (int) ($r['id_modalitate_plata'] ?? 1),
                'data_livrare'        => $dataLivrare,
                'interval_livrare'    => $this->nullIfEmpty($r['interval_livrare'] ?? null),
                'livrat'              => (bool) ($r['livrat'] ?? 0),
                'achitat'             => (bool) ($r['achitat'] ?? 0),
                'invoice_generated'   => (bool) ($r['invoice_generated'] ?? 0),
                'luna_livrata'        => $this->nullIfEmpty($r['luna_livrata'] ?? null),
                'status'              => $this->nullIfEmpty($r['status'] ?? null),
                'motiv_respingere'    => null,
                'data_respingere'     => null,
                'aprobat_de'          => null,
                'id_utilizator'       => null,
                'nume'                => $this->nullIfEmpty($r['nume'] ?? null),
                'telefon'             => $this->nullIfEmpty($r['telefon'] ?? null),
                'observatii'          => $this->nullIfEmpty($r['observatii'] ?? null),
                'ordine_traseu'       => (int) ($r['ordine_traseu'] ?? 0),
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        }

        foreach (array_chunk($insert, 200) as $chunk) {
            DB::table('comenzi')->insert($chunk);
        }
        $this->command->line('  comenzi: ' . count($insert) . ' rânduri (' . $skiped . ' sărite FK lipsă)');
    }

    private function migComenziProduse(): void
    {
        $this->command->info('─── Linii comenzi ───');

        $comenziIds = DB::table('comenzi')->pluck('id')->flip()->toArray();
        $produseIds = DB::table('cost_products')->pluck('id')->flip()->toArray();
        $insert     = [];
        $now        = now();
        $skiped     = 0;

        foreach ($this->date['comenzi_produse'] ?? [] as $r) {
            $idComanda = (int) $r['id_comanda'];
            $idProdus  = (int) $r['id_produs'];

            if (!isset($comenziIds[$idComanda]) || !isset($produseIds[$idProdus])) {
                $skiped++;
                continue;
            }

            $insert[] = [
                'id'         => $r['id'],
                'id_comanda' => $idComanda,
                'id_produs'  => $idProdus,
                'cantitate'  => (int) ($r['nr_bucati'] ?? 1),
                'pret'       => (float) ($r['pret_bucata'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table('comenzi_produse')->insert($chunk);
        }
        $this->command->line('  comenzi_produse: ' . count($insert) . ' rânduri (' . $skiped . ' sărite)');
    }

    private function migStoc(): void
    {
        $this->command->info('─── Stoc ───');

        $produseIds  = DB::table('cost_products')->pluck('id')->flip()->toArray();
        $idDepozit   = DB::table('deposits')->value('id');

        if (!$idDepozit) {
            $this->command->warn('  stoc: niciun depozit găsit, skip.');
            return;
        }

        $insert  = [];
        $now     = now();
        $skiped  = 0;

        foreach ($this->date['stoc'] ?? [] as $r) {
            $idProdus = (int) $r['id_produs'];
            if (!isset($produseIds[$idProdus])) {
                $skiped++;
                continue;
            }

            $actiune = strtoupper(trim($r['actiune'] ?? 'OUT'));
            $tip = match ($actiune) {
                'IN'       => 'IN',
                'CUSTODIE' => 'CUSTODIE',
                default    => 'OUT',
            };

            // Referință polimorfică
            $idRef  = null;
            $tipRef = null;
            if (!empty($r['id_comanda'])) {
                $idRef  = (int) $r['id_comanda'];
                $tipRef = 'comanda';
            } elseif (!empty($r['id_comanda_rapida'])) {
                $idRef  = (int) $r['id_comanda_rapida'];
                $tipRef = 'comanda_rapida';
            }

            $insert[] = [
                'id'            => $r['id'],
                'id_produs'     => $idProdus,
                'id_depozit'    => $idDepozit,
                'cantitate'     => (int) ($r['cantitate'] ?? 0),
                'tip'           => $tip,
                'id_referinta'  => $idRef,
                'tip_referinta' => $tipRef,
                'data'          => '2026-01-01', // lipsă în schema CI3 — estimat
                'observatii'    => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table('stoc')->insert($chunk);
        }
        $this->command->line('  stoc: ' . count($insert) . ' rânduri (' . $skiped . ' sărite)');
        $this->command->warn('  ⚠ Coloana `data` în stoc nu exista în CI3 — setată 2026-01-01 (estimat). Verificați manual.');
    }

    private function migRecipienti(): void
    {
        $this->command->info('─── Recipienți ───');

        $adreseIds  = DB::table('adresa_livrare')->pluck('id')->flip()->toArray();
        $clientiIds = DB::table('clienti')->pluck('id')->flip()->toArray();
        $insert     = [];
        $now        = now();
        $skiped     = 0;

        foreach ($this->date['recipienti'] ?? [] as $r) {
            $idAdresa = (int) $r['id_adresa'];
            $idClient = (int) $r['id_client'];

            if (!isset($adreseIds[$idAdresa]) || !isset($clientiIds[$idClient])) {
                $skiped++;
                continue;
            }

            $insert[] = [
                'id'             => $r['id'],
                'id_client'      => $idClient,
                'id_adresa'      => $idAdresa,
                'lasati'         => (int) ($r['nr_recipienti'] ?? 0),
                'recuperati'     => (int) ($r['recuperati'] ?? 0),
                'lasati_11l'     => (int) ($r['nr_recipienti_11L'] ?? 0),
                'recuperati_11l' => (int) ($r['recuperati_11L'] ?? 0),
                'data'           => $r['data'] ?? date('Y-m-d'),
                'id_comanda'     => null,
                'observatii'     => $this->nullIfEmpty($r['observatii'] ?? null),
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table('recipienti')->insert($chunk);
        }
        $this->command->line('  recipienti: ' . count($insert) . ' rânduri (' . $skiped . ' sărite)');
    }

    private function migDozator(): void
    {
        $this->command->info('─── Dozatoare ───');

        $adreseIds  = DB::table('adresa_livrare')->pluck('id')->flip()->toArray();
        $clientiIds = DB::table('clienti')->pluck('id')->flip()->toArray();
        $carIds     = DB::table('cars')->pluck('id')->flip()->toArray();
        $produseIds = DB::table('cost_products')->pluck('id')->flip()->toArray();
        $insert     = [];
        $now        = now();
        $skiped     = 0;

        foreach ($this->date['dozator'] ?? [] as $r) {
            $idAdresa = (int) $r['id_adresa_livrare'];
            $idClient = (int) $r['id_client'];
            $idProdus = (int) $r['produs'];

            if (!isset($adreseIds[$idAdresa]) || !isset($clientiIds[$idClient])) {
                $skiped++;
                continue;
            }
            if (!isset($produseIds[$idProdus])) {
                // Produs invalid — folosim 52 (DOZATOR CUSTODIE) ca fallback
                if (!isset($produseIds[52])) { $skiped++; continue; }
                $idProdus = 52;
            }

            $idMasina = (int) ($r['id_masina'] ?? 0);
            $idMasina = ($idMasina && isset($carIds[$idMasina])) ? $idMasina : null;

            $tranzactie = strtolower(trim($r['tranzactie'] ?? 'custodie'));
            if (!in_array($tranzactie, ['custodie', 'cumparat'])) $tranzactie = 'custodie';

            $insert[] = [
                'id'                 => $r['id'],
                'id_client'          => $idClient,
                'id_adresa'          => $idAdresa,
                'id_masina'          => $idMasina,
                'id_produs'          => $idProdus,
                'serie'              => $this->nullIfEmpty($r['nr_inventar'] ?? null),
                'tranzactie'         => $tranzactie,
                'data_instalare'     => $r['data_lasare'] ?? date('Y-m-d'),
                'comanda'            => (bool) ($r['comanda'] ?? 0),
                'activ'              => (int) ($r['status'] ?? 1) === 1,
                'perioada_igenizare' => $this->parseData($r['perioada_igenizare'] ?? null, '/'),
                'observatii'         => $this->nullIfEmpty($r['observatii'] ?? null),
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        $this->inserteaza('dozator', $insert);
        if ($skiped) {
            $this->command->warn("  dozator: {$skiped} rânduri sărite (FK lipsă)");
        }
    }

    // ─── Utilitare ─────────────────────────────────────────────────────────────

    private function inserteaza(string $tabel, array $randuri, int $chunk = 500): void
    {
        if (empty($randuri)) {
            $this->command->warn("  {$tabel}: 0 rânduri de inserat");
            return;
        }
        foreach (array_chunk($randuri, $chunk) as $bloc) {
            DB::table($tabel)->insert($bloc);
        }
        $this->command->line("  {$tabel}: " . count($randuri) . ' rânduri');
    }

    /** Parsează date din format variabil (cu separator / sau -) → "Y-m-d" sau null */
    private function parseData(?string $raw, string $separator = '-'): ?string
    {
        if (empty($raw) || $raw === '-') return null;
        $normalized = str_replace($separator, '-', trim($raw));
        try {
            return Carbon::parse($normalized)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Parsează "44.40, 26.05" → [44.40, 26.05] (sau [null, null]) */
    private function parseCoordonate(string $raw): array
    {
        $raw = trim($raw);
        if (empty($raw)) return [null, null];
        $p = explode(',', $raw, 2);
        if (count($p) !== 2) return [null, null];
        $lat = (float) trim($p[0]);
        $lng = (float) trim($p[1]);
        return [$lat ?: null, $lng ?: null];
    }

    /** Returnează null dacă string-ul e gol sau doar "-" */
    private function nullIfEmpty(?string $val): ?string
    {
        if ($val === null) return null;
        $v = trim($val);
        return ($v === '' || $v === '-') ? null : $v;
    }
}
