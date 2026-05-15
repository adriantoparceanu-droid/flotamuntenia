<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrație de date one-time: fuzionează înregistrările duplicate din
 * clienti (același CIF), adresa_livrare (același client+oras+strada+nr)
 * și dozator (același client+adresa+produs).
 *
 * Idempotentă — dacă nu există duplicate, nu face nimic.
 * Strategia: se păstrează înregistrarea cu cel mai mic ID.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->fkOff();
        try {
            $this->deduplicareClienti();
            $this->deduplicareAdrese();
            $this->deduplicareDozatoare();
        } finally {
            $this->fkOn();
        }
    }

    public function down(): void {}

    // ─── Clienți ───────────────────────────────────────────────────────────────

    private function deduplicareClienti(): void
    {
        $grupuri = DB::table('clienti')
            ->select('cif', DB::raw('MIN(id) as id_keep'), DB::raw('GROUP_CONCAT(id ORDER BY id ASC) as toate_ids'))
            ->whereNotNull('cif')
            ->where('cif', '!=', '')
            ->groupBy('cif')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($grupuri as $grup) {
            $ids       = array_map('intval', explode(',', $grup->toate_ids));
            $idKeep    = $grup->id_keep;
            $idsDeSters = array_filter($ids, fn($id) => $id !== $idKeep);

            foreach ([
                'adresa_livrare' => 'id_client',
                'comenzi'        => 'id_client',
                'dozator'        => 'id_client',
                'produs'         => 'id_client',
                'recipienti'     => 'id_client',
                'users'          => 'id_client',
            ] as $tabel => $col) {
                DB::table($tabel)
                    ->whereIn($col, $idsDeSters)
                    ->update([$col => $idKeep]);
            }

            DB::table('clienti')->whereIn('id', $idsDeSters)->delete();
        }
    }

    // ─── Adrese livrare ────────────────────────────────────────────────────────

    private function deduplicareAdrese(): void
    {
        // Găsim perechile duplicate cu PHP (compatibil SQLite + MySQL)
        $toateAdresele = DB::table('adresa_livrare')
            ->select('id', 'id_client', 'oras', 'strada', 'nr')
            ->whereNotNull('oras')
            ->whereNotNull('strada')
            ->orderBy('id')
            ->get();

        // Grupăm după client + oras + strada + nr (normalizat)
        $grupuri = [];
        foreach ($toateAdresele as $a) {
            $cheie = $a->id_client . '|'
                . mb_strtolower(trim($a->oras ?? '')) . '|'
                . mb_strtolower(trim($a->strada ?? '')) . '|'
                . mb_strtolower(trim($a->nr ?? ''));
            $grupuri[$cheie][] = $a->id;
        }

        foreach ($grupuri as $ids) {
            if (count($ids) < 2) continue;

            $idKeep     = $ids[0]; // smallest ID
            $idsDeSters = array_slice($ids, 1);

            // comenzi, recipienti, dozator — redirect direct
            foreach (['comenzi' => 'id_adresa', 'recipienti' => 'id_adresa', 'dozator' => 'id_adresa'] as $tabel => $col) {
                DB::table($tabel)->whereIn($col, $idsDeSters)->update([$col => $idKeep]);
            }

            // produs are UNIQUE pe id_adresa — dacă există deja pentru keep, ștergem duplicatul
            foreach ($idsDeSters as $idDel) {
                $areKeep = DB::table('produs')->where('id_adresa', $idKeep)->exists();
                if ($areKeep) {
                    DB::table('produs')->where('id_adresa', $idDel)->delete();
                } else {
                    DB::table('produs')->where('id_adresa', $idDel)->update(['id_adresa' => $idKeep]);
                }
            }

            DB::table('adresa_livrare')->whereIn('id', $idsDeSters)->delete();
        }
    }

    // ─── Dozatoare ─────────────────────────────────────────────────────────────

    private function deduplicareDozatoare(): void
    {
        $toate = DB::table('dozator')
            ->select('id', 'id_client', 'id_adresa', 'id_produs')
            ->orderBy('id')
            ->get();

        $grupuri = [];
        foreach ($toate as $d) {
            $cheie = $d->id_client . '|' . $d->id_adresa . '|' . $d->id_produs;
            $grupuri[$cheie][] = $d->id;
        }

        foreach ($grupuri as $ids) {
            if (count($ids) < 2) continue;
            $idsDeSters = array_slice($ids, 1);
            DB::table('dozator')->whereIn('id', $idsDeSters)->delete();
        }
    }

    // ─── FK helpers ────────────────────────────────────────────────────────────

    private function fkOff(): void
    {
        DB::getDriverName() === 'mysql'
            ? DB::statement('SET FOREIGN_KEY_CHECKS=0')
            : DB::statement('PRAGMA foreign_keys = OFF');
    }

    private function fkOn(): void
    {
        DB::getDriverName() === 'mysql'
            ? DB::statement('SET FOREIGN_KEY_CHECKS=1')
            : DB::statement('PRAGMA foreign_keys = ON');
    }
};
