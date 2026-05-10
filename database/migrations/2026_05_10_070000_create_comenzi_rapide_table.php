<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Comenzi ad-hoc fara cont de client. Vezi DOCUMENTATION.md §2 (`comenzi_rapide`)
// si §3.6.
//
// Diferenta fata de schema legacy: GPS pe doua coloane DECIMAL (regula §8.9),
// nu varchar. UI-ul accepta input single-field "lat, lng" si parseaza la save.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comenzi_rapide', function (Blueprint $table) {
            $table->id();

            // Asignare optionala — comanda poate fi creata fara masina si
            // asignata ulterior prin drag in lista zilnica.
            $table->foreignId('id_masina')->nullable()
                ->constrained('cars')->nullOnDelete();
            $table->foreignId('id_depozit')->nullable()
                ->constrained('deposits')->nullOnDelete();

            // Date contact — text liber, fara legatura cu tabela clienti.
            $table->string('denumire', 255);
            $table->string('adresa', 500)->nullable();
            $table->string('telefon', 50)->nullable();

            // GPS pe doua coloane (regula §8.9), nullable.
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();

            $table->date('data_livrare');

            $table->boolean('livrat')->default(false);
            $table->boolean('achitat')->default(false);

            $table->unsignedInteger('ordine_traseu')->default(0);

            $table->text('observatii')->nullable();

            $table->timestamps();

            $table->index('data_livrare');
            $table->index('id_masina');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comenzi_rapide');
    }
};
