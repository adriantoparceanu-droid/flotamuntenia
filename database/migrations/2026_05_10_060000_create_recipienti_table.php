<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Jurnalul de miscari de recipienti (bidoane) la / de la clienti.
// Soldul curent = SUM(lasati) - SUM(recuperati) per adresa, calculat
// prin agregare (regula §8.2). Nu poate fi negativ.
//
// Coloanele 19L (lasati / recuperati) si 11L (lasati_11l / recuperati_11l)
// sunt gestionate independent pentru ca cele doua capacitati se
// urmaresc separat in interfata soferului.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipienti', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_client')
                ->constrained('clienti')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_adresa')
                ->constrained('adresa_livrare')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Cantitati 19L
            $table->unsignedInteger('lasati')->default(0);
            $table->unsignedInteger('recuperati')->default(0);

            // Cantitati 11L
            $table->unsignedInteger('lasati_11l')->default(0);
            $table->unsignedInteger('recuperati_11l')->default(0);

            $table->date('data');

            // Comanda asociata — opționala (admin poate inregistra miscari
            // izolate fara comanda asociata, ex: ridicare de revizuire).
            $table->foreignId('id_comanda')->nullable()
                ->constrained('comenzi')
                ->nullOnDelete();

            $table->text('observatii')->nullable();

            $table->timestamps();

            $table->index('id_adresa');
            $table->index('data');
            $table->index('id_comanda');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipienti');
    }
};
