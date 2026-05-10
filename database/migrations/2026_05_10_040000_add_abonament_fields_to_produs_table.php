<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Extinde tabela `produs` cu campuri specifice abonamentului lunar fix:
// - denumire_abonament: nume comercial dat de admin (ex: "Pachet Standard 5x19L")
// - pret_suplimentar_19l/_11l: pret per bidon livrat in plus fata de cantitatea
//   inclusa in abonamentul lunar.
//
// Pentru abonamentul lunar nu mai folosim 'frecventa' (livrarea e strict lunara).
// Coloanele 'pret' si 'pret_11l' au semantici diferite intre tipuri:
//  - tip 1 (abonament lunar): 'pret' = pret fix abonament/luna; 'pret_11l' = neutilizat
//  - tip 0 (per bucata): 'pret' = pret/bidon 19L; 'pret_11l' = pret/bidon 11L
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produs', function (Blueprint $table) {
            $table->string('denumire_abonament', 255)->nullable()->after('abonament');
            $table->decimal('pret_suplimentar_19l', 10, 2)->nullable()->after('pret_11l');
            $table->decimal('pret_suplimentar_11l', 10, 2)->nullable()->after('pret_suplimentar_19l');
        });
    }

    public function down(): void
    {
        Schema::table('produs', function (Blueprint $table) {
            $table->dropColumn(['denumire_abonament', 'pret_suplimentar_19l', 'pret_suplimentar_11l']);
        });
    }
};
