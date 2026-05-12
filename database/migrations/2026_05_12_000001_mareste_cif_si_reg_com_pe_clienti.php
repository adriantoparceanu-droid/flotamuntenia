<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Datele CI3 au campuri cu text liber (pana la 100+ chars). Marim coloanele
// pentru a permite importul fara trunchiere.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clienti', function (Blueprint $table) {
            $table->string('cif', 100)->nullable()->change();
            $table->string('reg_com', 100)->nullable()->change();
            $table->string('telefon', 50)->nullable()->change();
            $table->string('nr', 50)->nullable()->change();
            $table->string('bloc', 50)->nullable()->change();
            $table->string('sector', 50)->nullable()->change();
            $table->string('interfon', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clienti', function (Blueprint $table) {
            $table->string('cif', 20)->nullable()->change();
            $table->string('reg_com', 50)->nullable()->change();
            $table->string('telefon', 20)->nullable()->change();
            $table->string('nr', 20)->nullable()->change();
            $table->string('bloc', 20)->nullable()->change();
            $table->string('sector', 20)->nullable()->change();
            $table->string('interfon', 20)->nullable()->change();
        });
    }
};
