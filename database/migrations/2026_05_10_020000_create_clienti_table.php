<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela centrala a clientilor (PJ + PF).
// Pastram numele 'clienti' (nu 'clients') pentru consistenta cu DOCUMENTATION.md
// si pentru compatibilitate la importul datelor din baza CI3 existenta.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clienti', function (Blueprint $table) {
            $table->id();
            $table->string('cod_client', 50)->unique()->comment('Folosit la autentificare portal client');
            $table->unsignedTinyInteger('client')->comment('1 = PJ (firma), 2 = PF');
            $table->string('denumire', 255)->comment('Denumire firma sau nume complet PF');

            // Date juridice (relevante pentru PJ)
            $table->string('cif', 100)->nullable()->comment('CIF pentru PJ, CNP pentru PF');
            $table->string('reg_com', 100)->nullable();

            // Adresa sediu / domiciliu
            $table->string('oras', 100)->nullable();
            $table->string('strada', 255)->nullable();
            $table->string('nr', 50)->nullable();
            $table->string('bloc', 100)->nullable();
            $table->string('scara', 50)->nullable();
            $table->string('etaj', 50)->nullable();
            $table->string('apartament', 50)->nullable();
            $table->string('sector', 50)->nullable();
            $table->string('interfon', 50)->nullable();

            // Contact
            $table->string('email', 255)->nullable();
            $table->string('telefon', 100)->nullable();

            $table->text('observatii')->nullable();

            // Reziliere — flag + motiv optional (vezi modal de confirmare)
            $table->boolean('reziliat')->default(false);
            $table->text('observatii_reziliere')->nullable();

            // Data adaugarii (pastram din legacy pentru compatibilitate import).
            // Eloquent timestamps acopera modificarile interne.
            $table->date('data_adaugare')->nullable();

            $table->timestamps();

            $table->index('reziliat');
            $table->index('client');
            $table->index('email');
            $table->index('cif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clienti');
    }
};
