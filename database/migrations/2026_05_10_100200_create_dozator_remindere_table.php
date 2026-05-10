<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 4.1 — Jurnal reminder-e igienizare trimise pentru dozatoare cu BIDOANE.
//
// Tabela proprie (NU `notificari_mentenanta` din §2 — aceea e pentru
// `dozatoare_filtre` cu cele doua tipuri 30_zile/15_zile, Faza 4.3).
// Aici reminder-ul e simplu: admin a apasat "Trimite reminder" pe un dozator
// scadent, vrem audit (cand + cine).
//
// UI-ul foloseste asta pentru:
//   - Afisarea "Reminder trimis pe X" sub status-ul dozatorului
//   - Evitarea spam-ului: butonul devine "Re-trimite" dupa primul click
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dozator_remindere', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_dozator')
                ->constrained('dozator')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('trimis_de')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('trimis_la')->useCurrent();

            $table->index('id_dozator');
            $table->index('trimis_la');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dozator_remindere');
    }
};
