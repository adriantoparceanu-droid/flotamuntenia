<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adresele de livrare (un client poate avea mai multe).
// GPS pe doua coloane DECIMAL conform regulii §8.9 din DOCUMENTATION.md
// (in CI3 era string "lat, lng" — nu replicam aici).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adresa_livrare', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_client')
                ->constrained('clienti')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Stergerea fizica a clientului e blocata; se foloseste flag-ul reziliat');

            $table->string('denumire', 255)->comment('Eticheta punctului de livrare (ex: "Sediu", "Magazin Cluj")');

            // Adresa fizica
            $table->string('oras', 100)->nullable();
            $table->string('strada', 255)->nullable();
            $table->string('nr', 50)->nullable();
            $table->string('bloc', 100)->nullable();
            $table->string('scara', 50)->nullable();
            $table->string('etaj', 50)->nullable();
            $table->string('apartament', 50)->nullable();
            $table->string('sector', 50)->nullable();
            $table->string('interfon', 50)->nullable();

            // GPS — doua coloane separate (regula §8.9). Nullable pentru ca nu toti clientii au GPS.
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();

            $table->boolean('activ')->default(true);
            $table->date('data_adaugare')->nullable();
            $table->timestamps();

            $table->index('activ');
            $table->index('id_client');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adresa_livrare');
    }
};
