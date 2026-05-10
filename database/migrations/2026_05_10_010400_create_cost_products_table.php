<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nomenclatorul de produse (apa imbuteliata, dozatoare, consumabile etc.).
// IDs cheie folosite in business logic (vezi DOCUMENTATION.md §2):
//   45 = APA PLATA 19L,  46 = APA PLATA 11L,
//   47 = DOZATOR PODEA,  52 = DOZATOR CUSTODIE,  55 = DOZATOR CU FILTRE - Custodie
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_category')->constrained('cost_categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_tva')->nullable()->constrained('tva')->nullOnDelete();
            $table->string('denumire', 255);
            $table->decimal('pret', 10, 2)->default(0);
            $table->boolean('activ')->default(true);
            $table->timestamps();

            $table->index('activ');
            $table->index('id_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_products');
    }
};
