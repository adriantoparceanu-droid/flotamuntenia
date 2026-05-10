<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 6.7 — Documente atasate per client (DOCUMENTATION.md §3.2 + §6.20).
 *
 * Stocheaza metadata documentelor uploadate per client. Fisierul fizic e in
 * `storage/app/private/documente-clienti/{id_client}/{nume_stocat}` (disk
 * `local`, NU `public` — datele clientilor pot fi sensibile).
 *
 * `nume_fisier` = denumirea originala vazuta de user (ex: „Contract semnat.pdf")
 * `nume_stocat` = UUID + ext folosit pe filesystem (ex: „a1b2c3d4-...-...pdf")
 *
 * Decizii UI/business (Faza 6.7):
 *  - Fara categorie predefinita — doar denumire fisier + descriere libera
 *  - Orice tip de fisier acceptat la upload, max 10MB per fisier
 *  - cascadeOnDelete pe id_client: stergerea unui client sterge si documentele
 *    (boundary cleanup; in `Clienti\Documente` se va sterge si fisierul fizic)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documente_clienti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_client')
                ->constrained('clienti')
                ->cascadeOnDelete();
            $table->string('nume_fisier', 255)
                  ->comment('Denumirea originala (vizibila in UI)');
            $table->string('nume_stocat', 255)->unique()
                  ->comment('UUID + extensie folosit pe filesystem');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('marime_bytes')->default(0);
            $table->text('descriere')->nullable();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Audit: cine a incarcat documentul');
            $table->timestamps();

            $table->index('id_client');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documente_clienti');
    }
};
