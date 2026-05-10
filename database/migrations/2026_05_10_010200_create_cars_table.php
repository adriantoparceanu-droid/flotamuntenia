<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Vehiculele (flota) folosite la livrari.
// Culoarea hex e folosita pentru identificare vizuala pe harta Google Maps (markeri SVG colorati).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('denumire', 100)->comment('Numele/codul intern al masinii');
            $table->string('nr_inmatriculare', 20)->unique();
            $table->foreignId('id_depozit')->nullable()->constrained('deposits')->nullOnDelete();
            $table->char('culoare', 7)->default('#3b82f6')->comment('Cod hex pentru marker harta');
            $table->boolean('activ')->default(true);
            $table->timestamps();

            $table->index('activ');
        });

        // Activam acum FK-ul pe users.id_masina (a fost lasat orfan in faza 1.1).
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('id_masina')->references('id')->on('cars')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_masina']);
        });

        Schema::dropIfExists('cars');
    }
};
