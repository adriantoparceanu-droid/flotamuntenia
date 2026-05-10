<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Liniile unei comenzi rapide. Pretul e snapshot la momentul comenzii.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comenzi_rapide_produse', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_comanda_rapida')
                ->constrained('comenzi_rapide')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('id_produs')
                ->constrained('cost_products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('cantitate');
            $table->decimal('pret', 10, 2)->default(0);

            $table->timestamps();

            $table->index('id_comanda_rapida');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comenzi_rapide_produse');
    }
};
