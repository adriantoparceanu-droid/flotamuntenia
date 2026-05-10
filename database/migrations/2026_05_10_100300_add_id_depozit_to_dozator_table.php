<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 4.1 — Adaug `id_depozit` pe tabela `dozator`.
//
// Schema legacy (analiza-app/) NU avea aceasta coloana, dar pentru a putea
// genera mişcari de stoc corect (CUSTODIE sau OUT) trebuie sa stim DEPOZITUL
// SURSA din care a plecat dozatorul. Fara aceasta info, miscarile de stoc
// sunt orfane si raportul de stoc per depozit nu poate fi calculat.
//
// nullable + nullOnDelete pentru a fi consistent cu pattern-ul de pe `comenzi`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dozator', function (Blueprint $table) {
            $table->foreignId('id_depozit')
                ->nullable()
                ->after('id_produs')
                ->constrained('deposits')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index('id_depozit');
        });
    }

    public function down(): void
    {
        Schema::table('dozator', function (Blueprint $table) {
            $table->dropForeign(['id_depozit']);
            $table->dropIndex(['id_depozit']);
            $table->dropColumn('id_depozit');
        });
    }
};
