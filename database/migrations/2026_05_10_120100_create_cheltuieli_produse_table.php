<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 5.1 — Liniile facturilor de cheltuieli (vezi DOCUMENTATION.md §440).
//
// Pattern identic cu `comenzi_produse`: cascadeOnDelete pe id_cheltuiala
// (sterg liniile cand sterg factura), restrictOnDelete pe id_produs
// (admin nu poate sterge un produs din catalog daca are linii istorice).
//
// `cantitate` integer (nu decimal — bidoanele/dozatoarele sunt unitati intregi);
// `pret` decimal(10,2) = pret unitar de achizitie.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheltuieli_produse', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_cheltuiala')
                ->constrained('cheltuieli')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('id_produs')
                ->constrained('cost_products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('cantitate');
            $table->decimal('pret', 10, 2);

            $table->timestamps();

            $table->index('id_cheltuiala');
            $table->index('id_produs');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheltuieli_produse');
    }
};
