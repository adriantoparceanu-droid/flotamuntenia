<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 4.1 — Vizite igienizare la dozatoare cu BIDOANE
// (vezi DOCUMENTATION.md §2 tabel `vizite` + flux §4.2).
//
// Cand admin marcheaza o igienizare ca efectuata, se creeaza o intrare aici
// si se actualizeaza `dozator.perioada_igenizare` cu `data_urmatoare`.
// Vizita poate aparea ulterior in lista zilnica a soferului daca `livrat=0`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vizite', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_dozator')
                ->constrained('dozator')
                ->cascadeOnUpdate()
                ->cascadeOnDelete(); // stergerea dozatorului => sterge istoricul

            // Denormalizate pentru rapoarte rapide (acelasi pattern ca pe `comenzi`).
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

            $table->date('data_vizita');
            $table->date('data_urmatoare')->nullable(); // data calculata a urmatoarei igienizari (default +6 luni)
            $table->boolean('livrat')->default(false);  // efectuata
            $table->boolean('achitat')->default(false);
            $table->decimal('pret', 10, 2)->default(0);
            $table->text('observatii')->nullable();

            $table->timestamps();

            $table->index('id_dozator');
            $table->index('data_vizita');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vizite');
    }
};
