<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 4.1 — Dozatoare cu BIDOANE (vezi DOCUMENTATION.md §2 tabel `dozator`).
//
// Diferente fata de schema legacy:
//   - NU includem `data_schimb_filtre` aici — apartine de `dozatoare_filtre` (Faza 4.3),
//     entitate separata cu logica de mentenanta diferita (regula §8.5)
//   - `perioada_igenizare` e DATE NULL — admin poate lasa gol la creare; de obicei
//     auto-prefill la `data_instalare + 6 luni`
//   - FK explicite cu cascade rules; `id_produs` (CostProduct) e tipul de dozator
//     din catalog (ID-uri 47/52 fixate din Faza 1.2)
//
// Mișcările de stoc se genereaza prin MiscariStocService (CUSTODIE pentru
// `tranzactie='custodie'` sau OUT pentru `'cumparat'`).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dozator', function (Blueprint $table) {
            $table->id();

            // Client + adresa obligatorii. Stergerea unui client/adresa cu
            // dozator atasat e blocata (folosim flag-ul `activ`).
            $table->foreignId('id_client')
                ->constrained('clienti')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_adresa')
                ->constrained('adresa_livrare')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Masina si produs - nullable (admin poate lasa fara masina;
            // produsul cu cost_products restrictOnDelete).
            $table->foreignId('id_masina')
                ->nullable()
                ->constrained('cars')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('id_produs')
                ->constrained('cost_products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('serie', 100)->nullable();

            // 'custodie' = imprumutat (mişcare CUSTODIE pe stoc)
            // 'cumparat' = vandut (mişcare OUT pe stoc)
            $table->enum('tranzactie', ['custodie', 'cumparat'])->default('custodie');

            $table->date('data_instalare');
            $table->boolean('comanda')->default(false); // 1 = programat pentru livrare/ridicare la urmatoarea cursa
            $table->boolean('activ')->default(true);    // 1 = activ la client; 0 = recuperat/dezactivat
            $table->date('perioada_igenizare')->nullable(); // data urmatoarei igienizari programate
            $table->text('observatii')->nullable();

            $table->timestamps();

            $table->index('id_client');
            $table->index('id_adresa');
            $table->index('id_masina');
            $table->index('activ');
            $table->index('perioada_igenizare'); // pt query-uri de scadenta
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dozator');
    }
};
