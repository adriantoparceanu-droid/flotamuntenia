<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 4.3 — Dozatoare cu FILTRE (vezi DOCUMENTATION.md §2 tabel `dozatoare_filtre`).
//
// Entitate complet separata fata de `dozator` (Faza 4.1, bidoane). Diferente de
// fond — logica de mentenanta NU se mai bazeaza pe vizite de igienizare fizica
// ci pe intervale calendaristice (schimb filtre la 12 luni standard) si pe
// notificari manuale 30/15 zile (regula §8.6 — NU automat).
//
// Decizii cheie (validate in plan cu user-ul):
//   - `id_depozit` inclus din start (lecție din Faza 4.1 unde a trebuit migratie
//     separata); fara el miscarile CUSTODIE/OUT raman orfane
//   - `data_urmatoare_mentenanta` e camp critic (filtru status, raport scadente);
//     index pentru query-uri rapide
//   - `status` enum `activ`/`retras` (in loc de boolean `activ` ca la Bidoane —
//     respectam vocabularul din §2)
//   - `suma_garantie` decimal(10,2) — necesar pentru raportul financiar Faza 5
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dozatoare_filtre', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_client')
                ->constrained('clienti')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_adresa')
                ->constrained('adresa_livrare')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_masina')
                ->nullable()
                ->constrained('cars')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('id_produs')
                ->constrained('cost_products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Depozit-sursa pentru miscarea CUSTODIE/OUT. Nullable — admin
            // poate lasa gol la creare; daca lipseste, MiscariStocService
            // doar revertaza miscarile vechi (idem Bidoane).
            $table->foreignId('id_depozit')
                ->nullable()
                ->constrained('deposits')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('serie', 100)->nullable();

            $table->enum('tranzactie', ['custodie', 'cumparat'])->default('custodie');

            $table->date('data_instalare');

            // Data ultimei interventii de mentenanta efectuate (NULL la
            // instalare initiala — fara intervetie inca).
            $table->date('data_ultima_mentenanta')->nullable();

            // Camp CRITIC — scadenta mentenantei urmatoare. Sursa pentru:
            //   - status colorat (la_zi/scadent_30/scadent_15/expirat)
            //   - raport scadente
            //   - selector pentru reminder-e manuale
            $table->date('data_urmatoare_mentenanta')->nullable();

            // Vocabular din §2 — `activ`/`retras` (nu `activ` boolean ca la
            // Bidoane). `retras` = dozatorul a fost recuperat de la client.
            $table->enum('status', ['activ', 'retras'])->default('activ');

            $table->decimal('suma_garantie', 10, 2)->default(0);

            $table->text('observatii')->nullable();

            $table->timestamps();

            $table->index('id_client');
            $table->index('id_adresa');
            $table->index('id_masina');
            $table->index('status');
            $table->index('data_urmatoare_mentenanta'); // pt query-uri scadenta
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dozatoare_filtre');
    }
};
