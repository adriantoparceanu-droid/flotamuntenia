<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Faza 6.3 — Portal client self-service
// Token de activare pentru flow-ul „admin invita clientul" (link unic cu expirare).
// La invitare: admin genereaza UUID, seteaza activation_expires_at = now + 7 zile.
// La activare: clientul deschide /portal/activare/{token}, isi seteaza parola,
// confirmat=1, token + expirare resetate la null.
// Indexat pentru cautare rapida la accesarea linkului public.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('activation_token', 64)->nullable()->unique()->after('confirmat');
            $table->timestamp('activation_expires_at')->nullable()->after('activation_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['activation_token']);
            $table->dropColumn(['activation_token', 'activation_expires_at']);
        });
    }
};
