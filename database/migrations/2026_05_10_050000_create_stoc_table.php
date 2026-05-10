<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Jurnalul de miscari de stoc — sursa unica de adevar (regula §8.1).
// Stocul curent per (produs, depozit) se calculeaza prin agregare:
//   SUM(IF tip='IN', cantitate, -cantitate)
// Niciodata nu stocam soldul direct in tabela cost_products.
//
// `id_referinta` + `tip_referinta` formeaza o referinta polimorfica spre
// entitatea care a generat miscarea: 'comanda' / 'comanda_rapida' /
// 'cheltuiala' / 'dozator' / 'manual'.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stoc', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_produs')
                ->constrained('cost_products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_depozit')
                ->constrained('deposits')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('cantitate');

            // IN  = intrare (achizitie, retur)
            // OUT = iesire (livrare la client, consum)
            // CUSTODIE = dozator dat in custodie (iese fizic, dar urmaribil)
            $table->enum('tip', ['IN', 'OUT', 'CUSTODIE']);

            // Referinta polimorfica spre entitatea care a generat miscarea.
            // Permite stergerea curata cand revertam o comanda (DELETE WHERE
            // tip_referinta='comanda' AND id_referinta=X).
            $table->unsignedBigInteger('id_referinta')->nullable();
            $table->string('tip_referinta', 30)->nullable();

            $table->date('data');
            $table->text('observatii')->nullable();

            $table->timestamps();

            $table->index(['id_produs', 'id_depozit'], 'stoc_produs_depozit_idx');
            $table->index('data');
            $table->index(['tip_referinta', 'id_referinta'], 'stoc_referinta_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stoc');
    }
};
