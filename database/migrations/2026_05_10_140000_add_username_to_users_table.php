<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adauga coloana `username` pe users — alternativa la email pentru autentificare.
// Format permis: 3-50 caractere [a-z0-9._] (validat la nivel de aplicatie).
// UNIQUE pentru a nu permite duplicate; nullable ca sa fie optă la creare.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
