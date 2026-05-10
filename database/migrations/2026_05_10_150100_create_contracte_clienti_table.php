<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 6.2 — Tabel contracte_clienti (DOCUMENTATION.md §2.2 + §6.2).
 *
 * Snapshot HTML al contractului de prestari servicii per client. Inlocuieste
 * template-ul global unic din CI3 cu un contract individualizat per client:
 * - generat la prima accesare din template-ul global (substituire placeholdere
 *   cu datele clientului), apoi stocat aici;
 * - editabil ulterior din UI (TinyMCE in tab Contract pe Detalii client);
 * - PDF-ul se genereaza din `continut_html` la cerere (DomPDF).
 *
 * Relatie 1:1 cu clientul prin UNIQUE pe `id_client`. Cascade la stergere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracte_clienti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_client')->unique()
                  ->constrained('clienti')
                  ->cascadeOnDelete()
                  ->comment('Clientul proprietar — un singur contract per client');
            $table->longText('continut_html')->nullable()
                  ->comment('HTML-ul contractului cu datele clientului substituite');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracte_clienti');
    }
};
