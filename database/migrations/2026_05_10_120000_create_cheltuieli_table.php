<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 5.1 — Facturi de cheltuieli/achizitii (vezi DOCUMENTATION.md §425).
//
// Schema moderna (vs. CI3 care folosea `data_document/nr_document/suma`):
//   - `nr_factura` + `furnizor` (text liber, sugestii prin datalist din UI)
//   - `id_depozit` FK obligatoriu — destinatia mişcarilor de stoc IN
//   - `total` decimal(10,2) auto-calculat din liniile cheltuieli_produse
//     (sum cantitate × pret); persistat la salvare ca să fie disponibil
//     in liste fara JOIN-uri suplimentare
//   - `achitat` boolean simplu (in Faza 6 putem extinde cu data si mod plata)
//
// Mişcările de stoc se genereaza prin MiscariStocService (TIP_IN) — pattern
// revert+recreate identic cu Comenzi (regula §8.1 — soldul se calculeaza
// prin agregare din jurnal).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheltuieli', function (Blueprint $table) {
            $table->id();

            $table->string('nr_factura', 100);
            $table->string('furnizor', 255);

            $table->foreignId('id_depozit')
                ->constrained('deposits')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('data');
            $table->decimal('total', 10, 2)->default(0);
            $table->boolean('achitat')->default(false);
            $table->text('observatii')->nullable();

            $table->timestamps();

            $table->index('data');
            $table->index('furnizor');
            $table->index('achitat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheltuieli');
    }
};
