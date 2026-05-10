<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 6.3 — Portal client self-service.
// Coloana `id_utilizator` pe comenzi = audit pentru cine a plasat comanda din portal.
// Un client poate avea mai multi utilizatori tip=3 asociati (vezi DOCUMENTATION §3.1),
// deci id_client singur nu e suficient pentru atribuire.
// nullOnDelete: pastram comanda istorica chiar daca user-ul e sters.
// Comenzile create de admin (CRUD existente) lasa coloana NULL.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comenzi', function (Blueprint $table) {
            $table->foreignId('id_utilizator')
                ->nullable()
                ->after('aprobat_de')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comenzi', function (Blueprint $table) {
            $table->dropForeign(['id_utilizator']);
            $table->dropColumn('id_utilizator');
        });
    }
};
