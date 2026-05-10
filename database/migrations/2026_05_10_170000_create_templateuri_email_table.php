<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 6.5 — Tabel templateuri_email (DOCUMENTATION.md §2).
 *
 * Stocheaza template-urile de email editabile din UI. Un template e identificat
 * de `cheie` (slug stabil folosit in cod, ex: 'comanda_aprobata') si contine:
 *   - subiect cu placeholdere {NUME}
 *   - continut_html cu placeholdere {NUME} (rendered cu TinyMCE WYSIWYG)
 *
 * Toggle `activ` permite dezactivarea unui template fara stergere — apelantii
 * MailService::send($cheie, ...) primesc false silent daca template-ul e
 * dezactivat (echivalent cu „nu exista").
 *
 * Sursa de adevar pentru lista placeholderelor disponibile per template ramane
 * in TemplateEmailService::placeholderePerCheie() — UI-ul foloseste asta pentru
 * a afisa lista in editor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templateuri_email', function (Blueprint $table) {
            $table->id();
            $table->string('cheie', 50)->unique()
                  ->comment('Slug stabil folosit in cod: comanda_aprobata, portal_invitatie, etc.');
            $table->string('denumire', 100)
                  ->comment('Eticheta umana pentru lista UI');
            $table->string('subiect', 255)
                  ->comment('Subiect email cu placeholdere {NUME}');
            $table->longText('continut_html')
                  ->comment('Body HTML cu placeholdere {NUME} (TinyMCE output)');
            $table->text('descriere_placeholdere')->nullable()
                  ->comment('Descriere libera pentru context (afisat in UI editor)');
            $table->boolean('activ')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templateuri_email');
    }
};
