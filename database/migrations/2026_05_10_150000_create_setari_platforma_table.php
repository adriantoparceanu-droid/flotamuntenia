<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faza 6.2 — Tabel setari_platforma (DOCUMENTATION.md §2.2 + §6.2).
 *
 * Tabel generic key-value pentru setari globale ale platformei.
 * Folosit initial pentru `contract_template_html` (template HTML al contractului
 * de prestari servicii, editabil din /setari/contract-template); va sustine si
 * `cron_token` (UUID pentru securizarea endpoint-urilor cron) in Faza 6.8.
 *
 * Convenție: cheia e UNIQUE; valoarea e text (longtext echivalent in MySQL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setari_platforma', function (Blueprint $table) {
            $table->id();
            $table->string('cheie', 100)->unique()
                  ->comment('Cheia setarii (ex: contract_template_html, cron_token)');
            $table->longText('valoare')->nullable()
                  ->comment('Valoarea setarii — text liber (poate contine HTML, JSON, UUID etc.)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setari_platforma');
    }
};
