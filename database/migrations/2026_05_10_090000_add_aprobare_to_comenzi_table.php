<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 3.3 — Aprobare comenzi portal client.
//
// Coloana `status` exista deja (vezi 2026_05_10_050100_create_comenzi_table)
// si tine 'In asteptare' pentru comenzile plasate de clientii portal (tip=3).
// Aici adaugam doar:
//   - motiv_respingere TEXT NULL — text liber introdus de admin la respingere
//   - data_respingere TIMESTAMP NULL — cand a fost respinsa (audit)
//   - aprobat_de FK NULL → users.id — cine a aprobat (audit, nullOnDelete)
//
// Comenzile cu status='In asteptare' sunt deja ascunse din ListaZilnica si
// Traseul soferului (filter `whereNull('status')`); cele 'Respins' raman
// ascunse din aceleasi locuri (regula §8.3).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comenzi', function (Blueprint $table) {
            $table->text('motiv_respingere')->nullable()->after('status');
            $table->timestamp('data_respingere')->nullable()->after('motiv_respingere');
            $table->foreignId('aprobat_de')
                ->nullable()
                ->after('data_respingere')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comenzi', function (Blueprint $table) {
            $table->dropForeign(['aprobat_de']);
            $table->dropColumn(['motiv_respingere', 'data_respingere', 'aprobat_de']);
        });
    }
};
