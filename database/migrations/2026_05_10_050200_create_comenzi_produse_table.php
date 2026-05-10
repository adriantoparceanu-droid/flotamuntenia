<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Liniile unei comenzi — granularitate pe produs. Pretul e capturat la
// momentul comenzii (snapshot), pentru a fi imun la modificarile ulterioare
// din catalog.
//
// La stergerea unei comenzi (DELETE fizic, doar daca livrat=0) liniile
// dispar prin cascadeOnDelete.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comenzi_produse', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_comanda')
                ->constrained('comenzi')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('id_produs')
                ->constrained('cost_products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('cantitate');
            $table->decimal('pret', 10, 2)->default(0);

            $table->timestamps();

            $table->index('id_comanda');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comenzi_produse');
    }
};
