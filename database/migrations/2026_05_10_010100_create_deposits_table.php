<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Depozitele/punctele de lucru de la care pleaca livrarile.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->string('denumire', 255);
            $table->string('adresa', 500)->nullable();
            $table->boolean('activ')->default(true);
            $table->timestamps();

            $table->index('activ');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
