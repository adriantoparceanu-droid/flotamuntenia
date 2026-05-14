<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Inserează valorile implicite (active = '1') pentru toate cele 13 module
 * opționale în tabelul setari_platforma.
 *
 * Folosim updateOrInsert în loc de insert pur — idempotentă, sigură de rulat
 * de mai multe ori (ex: pe un mediu care a rulat parțial migrațiile).
 * Modulele necunoscute anterior sunt create cu valoarea '1' (activ by default).
 */
return new class extends Migration
{
    private array $module = [
        'modul_portal_client',
        'modul_comenzi_rapide',
        'modul_probleme',
        'modul_dozatoare',
        'modul_recipienti',
        'modul_stoc',
        'modul_facturare',
        'modul_contracte',
        'modul_harti',
        'modul_rapoarte',
        'modul_anaf',
        'modul_email',
        'modul_cron',
    ];

    public function up(): void
    {
        $acum = now();
        foreach ($this->module as $cheie) {
            DB::table('setari_platforma')->updateOrInsert(
                ['cheie' => $cheie],
                ['valoare' => '1', 'updated_at' => $acum, 'created_at' => $acum],
            );
        }
    }

    public function down(): void
    {
        DB::table('setari_platforma')
            ->whereIn('cheie', $this->module)
            ->delete();
    }
};
