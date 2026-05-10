<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adauga FK nullable id_utilizator pe jurnalul de recipienti — audit
// (cine a operat miscarea: sofer la livrare sau admin la corectie manuala).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipienti', function (Blueprint $table) {
            $table->foreignId('id_utilizator')->nullable()->after('id_comanda')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recipienti', function (Blueprint $table) {
            $table->dropForeign(['id_utilizator']);
            $table->dropColumn('id_utilizator');
        });
    }
};
