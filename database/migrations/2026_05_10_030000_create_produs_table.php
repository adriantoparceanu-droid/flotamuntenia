<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Configurarea de livrare per adresa (relatie 1:1 cu adresa_livrare).
// Numele 'produs' e pastrat din schema legacy desi denumirea ar fi mai potrivita
// "configurare_livrare" — pastram pentru compatibilitate cu importul datelor CI3.
//
// Diferenta fata de schema legacy: zi_livrare e DATE (nu VARCHAR). Fuzionam
// 'zi_livrare' si 'data_inceput' din legacy intr-o singura coloana — user-ul
// alege o data calendaristica specifica iar livrarile recurente se calculeaza
// din ea + 'frecventa'.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produs', function (Blueprint $table) {
            $table->id();

            // 1:1 cu adresa_livrare — fortat prin UNIQUE + cascade pe stergere.
            // Daca cineva sterge adresa (foarte rar — pentru ca preferam toggle activ),
            // configuratia ei dispare automat. Stergerea clientului e blocata in alta parte.
            $table->foreignId('id_adresa')
                ->unique()
                ->constrained('adresa_livrare')
                ->cascadeOnDelete();

            // Stocam si id_client redundant cu adresa, pentru a evita JOIN-uri repetate
            // in rapoarte (raportul abonamente lipsa scaneaza mii de produse).
            $table->foreignId('id_client')
                ->constrained('clienti')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // 0=per bucata, 1=abonament lunar, 2=filtre, 3=aparate
            $table->unsignedTinyInteger('abonament')->default(0);

            // Cantitati per livrare (relevante pentru abonament=1)
            $table->unsignedInteger('nr_bidoane')->default(0)->comment('Bidoane 19L per livrare');
            $table->unsignedInteger('nr_bidoane_11l')->default(0)->comment('Bidoane 11L per livrare');

            // Pret per unitate sau per abonament
            $table->decimal('pret', 10, 2)->default(0)->comment('Pret 19L sau abonament');
            $table->decimal('pret_11l', 10, 2)->default(0)->comment('Pret 11L');

            // Recurenta (relevanta doar pentru abonament=1)
            $table->unsignedInteger('frecventa')->nullable()->comment('Zile intre livrari, ex: 7 = saptamanal');
            $table->date('zi_livrare')->nullable()->comment('Data primei livrari (anchor pentru recurenta)');

            // Default-uri operationale (mereu utile)
            $table->foreignId('id_masina')->nullable()->constrained('cars')->nullOnDelete();
            $table->foreignId('id_depozit')->nullable()->constrained('deposits')->nullOnDelete();

            $table->text('observatii')->nullable();

            $table->timestamps();

            $table->index('abonament');
            $table->index('id_client');
            $table->index('zi_livrare');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produs');
    }
};
