<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabela centrala a operatiunilor — comenzile pentru clientii inregistrati.
// Vezi DOCUMENTATION.md §2 (`comenzi`) si §3.3 / §4.1 (fluxul de comanda).
//
// Reguli critice (§8):
//  - `luna_livrata` (YYYY/MM) este OBLIGATORIE pe `tip_comanda='abonament'` (validat la save)
//  - `status='In asteptare'` doar pentru comenzile portal (Faza 3.3) — admin creeaza cu status NULL
//  - Soferul (tip=5) vede DOAR comenzile cu id_masina = masina sa
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comenzi', function (Blueprint $table) {
            $table->id();

            // Client si adresa — obligatorii. Stergerea unui client/adresa
            // cu comenzi atasate e blocata (folosim flag-uri activ/reziliat).
            $table->foreignId('id_client')
                ->constrained('clienti')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('id_adresa')
                ->constrained('adresa_livrare')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Asignarea operationala — nullable pana cand admin asigneaza
            // mas/depozit din interfata zilnica (Faza 2.2). Daca masina sau
            // depozitul e sters, comanda ramane (id devine NULL).
            $table->foreignId('id_masina')->nullable()
                ->constrained('cars')->nullOnDelete();
            $table->foreignId('id_depozit')->nullable()
                ->constrained('deposits')->nullOnDelete();

            // Tip comanda: dicteaza semantica preturilor pe linii si campul luna_livrata.
            $table->enum('tip_comanda', ['abonament', 'consum suplimentar', 'fara abonament'])
                ->default('fara abonament');

            // Cantitati la nivel de comanda (denormalizate pentru filtrare/raport rapid).
            // Sursa de adevar pentru pretul total ramane suma comenzi_produse.
            $table->unsignedInteger('nr_recipienti')->default(0)->comment('Bidoane 19L');
            $table->unsignedInteger('nr_pahare')->default(0)->comment('Bidoane 11L');

            // 1=cash, 2=OP, 3=card, 4=alta
            $table->unsignedTinyInteger('id_modalitate_plata')->default(1);

            $table->date('data_livrare');
            $table->string('interval_livrare', 50)->nullable();

            $table->boolean('livrat')->default(false);
            $table->boolean('achitat')->default(false);

            // Facturare (Faza 6.1) — populate cand admin emite factura in Oblio.
            $table->boolean('invoice_generated')->default(false);
            $table->string('oblio_numar_factura', 50)->nullable();

            // Critic pentru raportul abonamente lipsa (§4.5).
            // Format YYYY/MM. Validat ca obligatoriu pe tip_comanda='abonament'.
            $table->string('luna_livrata', 7)->nullable();

            // 'In asteptare' (comanda portal) sau NULL (admin)
            $table->string('status', 50)->nullable();

            // Persoana de contact la livrare (override fata de client default).
            $table->string('nume', 255)->nullable();
            $table->string('telefon', 50)->nullable();

            $table->text('observatii')->nullable();

            // Setat de drag-and-drop in lista zilnica (Faza 2.2).
            $table->unsignedInteger('ordine_traseu')->default(0);

            $table->timestamps();

            // Indexuri pentru filtrele cele mai frecvente in interfata admin
            // (data zilnica + status + asignare).
            $table->index('data_livrare');
            $table->index('id_client');
            $table->index('id_masina');
            $table->index('status');
            $table->index('luna_livrata');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comenzi');
    }
};
