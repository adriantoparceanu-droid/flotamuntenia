<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 4.3 — Jurnal notificari mentenanta (vezi DOCUMENTATION.md §2 tabel
// `notificari_mentenanta` + regula §8.6: notificarile sunt MANUALE, NU automate).
//
// Diferente fata de `dozator_remindere` (Faza 4.1, simplu):
//   - Aici tinem `tip_notificare` enum 30_zile/15_zile (admin trimite explicit
//     unul din cele doua sau il alege auto-detectat de UI)
//   - Nullable `id_dozator_filtre` la cascade — daca admin sterge dozatorul,
//     pastram totusi audit-ul cu trimiterea (set null)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificari_mentenanta', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_dozator_filtre')
                ->nullable()
                ->constrained('dozatoare_filtre')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('id_client')
                ->constrained('clienti')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('tip_notificare', ['30_zile', '15_zile']);

            $table->timestamp('data_trimitere')->useCurrent();

            $table->foreignId('trimis_de')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index('id_dozator_filtre');
            $table->index('data_trimitere');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificari_mentenanta');
    }
};
