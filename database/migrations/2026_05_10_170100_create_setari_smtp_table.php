<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 6.9 — Tabel setari_smtp (DOCUMENTATION.md §2).
 *
 * Stocheaza configurarea SMTP folosita pentru trimitere email-uri reale.
 * Coloana `password` e criptata prin cast `encrypted:string` la model.
 *
 * Regula: un singur rand activ la un moment dat — pattern identic cu
 * facturare_setari. Daca `activ()` returneaza null, MailService trimite
 * fallback in log (nu propaga erori).
 *
 * Credentialele NU se pun in .env (regula §8.10) — sunt editabile din UI
 * de admin, deci .env-ul ar fi un dezavantaj operational pe cPanel shared.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setari_smtp', function (Blueprint $table) {
            $table->id();
            $table->string('host', 255);
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('username', 255)->nullable();
            $table->text('password')->nullable()
                  ->comment('Criptat la rest prin cast encrypted:string pe model');
            $table->string('encryption', 10)->default('tls')
                  ->comment('tls | ssl | none');
            $table->string('from_email', 255);
            $table->string('from_name', 255);
            $table->boolean('activ')->default(false)
                  ->comment('Un singur rand activ; daca lipseste, fallback la log.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setari_smtp');
    }
};
