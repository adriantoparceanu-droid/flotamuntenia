<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 6.1 — Tabel facturare_setari (DOCUMENTATION.md §2 + §7.4).
 *
 * Stocheaza credentiale criptate pentru furnizorii de facturare electronica
 * (Oblio, SmartBill, etc.). Coloana `setari` foloseste cast Eloquent
 * `encrypted:array` la model — JSON criptat in DB.
 *
 * Regula: un singur furnizor activ la un moment dat — enforced in UI
 * (componenta Setari\Facturare dezactiveaza ceilalti la activarea unuia).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturare_setari', function (Blueprint $table) {
            $table->id();
            $table->string('furnizor', 50)->unique()
                  ->comment('Cod furnizor: oblio, smartbill, etc.');
            $table->text('setari')->nullable()
                  ->comment('JSON criptat: api keys, CIF emitent, serie, etc.');
            $table->boolean('activ')->default(false)
                  ->comment('Un singur furnizor activ la un moment dat.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturare_setari');
    }
};
