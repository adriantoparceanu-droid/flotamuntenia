<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 6.1 — Redenumire coloana legacy oblio_numar_factura -> coloane generice
 * pentru a suporta multi-furnizor (Oblio, SmartBill, etc.).
 *
 * Adauga: factura_serie, factura_numar, factura_link, factura_furnizor.
 * Drop:   oblio_numar_factura (nume specific furnizor; nu mai are sens).
 *
 * NOTA: in Faza 1 nu existau date persistate in oblio_numar_factura — drop e
 * sigur fara migrare de continut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comenzi', function (Blueprint $table) {
            $table->dropColumn('oblio_numar_factura');
        });

        Schema::table('comenzi', function (Blueprint $table) {
            $table->string('factura_serie', 20)->nullable()
                  ->after('invoice_generated')
                  ->comment('Seria facturii (ex: WF, FCT) — generic per furnizor.');
            $table->string('factura_numar', 50)->nullable()
                  ->after('factura_serie')
                  ->comment('Numarul facturii returnat de furnizor (ex: 0053).');
            $table->string('factura_link', 500)->nullable()
                  ->after('factura_numar')
                  ->comment('Link spre PDF-ul facturii la furnizor.');
            $table->string('factura_furnizor', 50)->nullable()
                  ->after('factura_link')
                  ->comment('Cod furnizor care a emis: oblio, smartbill.');
        });
    }

    public function down(): void
    {
        Schema::table('comenzi', function (Blueprint $table) {
            $table->dropColumn(['factura_serie', 'factura_numar', 'factura_link', 'factura_furnizor']);
        });

        Schema::table('comenzi', function (Blueprint $table) {
            $table->string('oblio_numar_factura', 50)->nullable()->after('invoice_generated');
        });
    }
};
