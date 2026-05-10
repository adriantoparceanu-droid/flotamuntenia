<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tip utilizator: 1=admin, 3=client portal, 5=sofer, 10=gestiune, 100=superadmin
            $table->unsignedTinyInteger('tip')->default(1)->after('email');

            // Cont confirmat de admin (relevant pentru clientii portal)
            $table->boolean('confirmat')->default(true)->after('tip');

            // FK-uri opționale — adăugate ca nullable; constrainturile FK reale
            // se vor activa cand vom crea tabelele clienti si car (Faza 1.2 / 1.3)
            $table->unsignedBigInteger('id_client')->nullable()->after('confirmat');
            $table->unsignedBigInteger('id_masina')->nullable()->after('id_client');

            $table->index('tip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tip']);
            $table->dropColumn(['tip', 'confirmat', 'id_client', 'id_masina']);
        });
    }
};
