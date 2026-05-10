<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cotele de TVA utilizate la facturare si in catalog produse.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tva', function (Blueprint $table) {
            $table->id();
            $table->decimal('valoare', 5, 2)->comment('Cota TVA exprimata in procente, ex: 19.00');
            $table->string('denumire', 50)->comment('Eticheta, ex: 19% standard');
            $table->boolean('activ')->default(true);
            $table->timestamps();

            $table->index('activ');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tva');
    }
};
