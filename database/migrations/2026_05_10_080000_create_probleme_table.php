<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Probleme/intervenții la adresele de livrare (vezi DOCUMENTATION.md §3.9 + tabel `probleme` §2).
//
// Diferență față de schema legacy:
//  - `descriere` TEXT (nu varchar) — userul a confirmat un singur câmp text
//    obligatoriu (în CI3 era confuz: `observatii` ținea descrierea problemei)
//  - `data_livrare` (nu `data`) pentru consistență cu `comenzi.data_livrare`
//  - `interval_livrare` (nu `interval`) — același motiv
//  - FK explicite cu cascade rules; numele coloanelor în engleză păstrate (`id_*`)
//    pentru compatibilitate cu pattern-ul Comenzi.
//
// Apare în Lista zilnică (admin) și Traseul șoferului (Faza 2.3) împreună cu
// comenzile clasice și rapide — vezi `ListaZilnica::render()`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('probleme', function (Blueprint $table) {
            $table->id();

            // Client + adresă obligatorii. Stergerea unui client/adresă cu
            // probleme atasate e blocata (folosim flag-uri activ/reziliat).
            $table->foreignId('id_client')
                ->constrained('clienti')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('id_adresa')
                ->constrained('adresa_livrare')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Asignare operațională (nullable — admin asignează ulterior).
            $table->foreignId('id_masina')->nullable()
                ->constrained('cars')->nullOnDelete();
            $table->foreignId('id_depozit')->nullable()
                ->constrained('deposits')->nullOnDelete();

            // Conținut intervenție.
            $table->text('descriere')->comment('Descrierea problemei — obligatorie');
            $table->decimal('suma', 10, 2)->default(0)->comment('Suma de încasat');
            $table->unsignedTinyInteger('id_modalitate_plata')->default(1)
                ->comment('1=cash, 2=OP, 3=card, 4=alta');

            // Programare.
            $table->date('data_livrare');
            $table->string('interval_livrare', 50)->nullable();

            // Contact override (la adresa). Util când persoana de contact
            // pentru intervenție e diferită de contactul clientului.
            $table->string('nume', 255)->nullable();
            $table->string('telefon', 50)->nullable();

            // Status.
            $table->boolean('livrat')->default(false)->comment('Rezolvată');
            $table->boolean('achitat')->default(false);

            // Setat de drag-and-drop / alocare în lista zilnică.
            $table->unsignedInteger('ordine_traseu')->default(0);

            $table->timestamps();

            $table->index('data_livrare');
            $table->index('id_client');
            $table->index('id_masina');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('probleme');
    }
};
