<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 4.3 — Istoricul interventiilor de mentenanta per dozator cu filtre
// (vezi DOCUMENTATION.md §2 tabel `dozatoare_filtre_istoric`).
//
// Pattern: identic cu `vizite` de pe Bidoane — istoric persistent + sursa de
// adevar pentru ultima interventie. Cascade pe stergere ca sa pastram baza
// curata daca admin sterge fizic dozatorul.
//
// `id_client/id_masina` denormalizate pentru rapoarte rapide (consistent cu
// pattern-ul din `comenzi` si `vizite`).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dozatoare_filtre_istoric', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_dozator_filtre')
                ->constrained('dozatoare_filtre')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('id_client')
                ->constrained('clienti')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_masina')
                ->nullable()
                ->constrained('cars')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->date('data_interventie');
            $table->date('data_urmatoare')->nullable();
            $table->decimal('pret', 10, 2)->default(0);
            $table->text('observatii')->nullable();

            $table->timestamps();

            $table->index('id_dozator_filtre');
            $table->index('data_interventie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dozatoare_filtre_istoric');
    }
};
