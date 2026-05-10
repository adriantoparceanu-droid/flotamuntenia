<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Faza 6.2 — Setari globale ale platformei (key-value).
 *
 * Tabel generic pentru valori globale care nu apartin unui modul specific:
 *   - `contract_template_html` — HTML-ul template-ului de contract
 *     (editabil din /setari/contract-template)
 *   - `cron_token` — UUID pentru securizarea endpoint-urilor cron (Faza 6.8)
 *
 * Folosire:
 *   SetariPlatforma::get('contract_template_html', '<p>default</p>');
 *   SetariPlatforma::set('contract_template_html', $html);
 */
class SetariPlatforma extends Model
{
    protected $table = 'setari_platforma';

    public const CHEIE_CONTRACT_TEMPLATE = 'contract_template_html';
    public const CHEIE_CRON_TOKEN = 'cron_token';

    protected $fillable = [
        'cheie',
        'valoare',
    ];

    /**
     * Returneaza valoarea unei setari sau valoarea implicita.
     */
    public static function get(string $cheie, ?string $implicit = null): ?string
    {
        $rand = static::where('cheie', $cheie)->first();
        return $rand?->valoare ?? $implicit;
    }

    /**
     * Seteaza (creeaza sau actualizeaza) valoarea unei setari.
     */
    public static function set(string $cheie, ?string $valoare): self
    {
        return static::updateOrCreate(
            ['cheie' => $cheie],
            ['valoare' => $valoare],
        );
    }

    /**
     * Faza 6.8 — Returneaza tokenul UUID folosit pentru securizarea endpoint-urilor
     * cron. Daca lipseste, il genereaza si persisteaza (idempotent — apelantii
     * care nu vor sa stie despre lazy-init pot folosi liberal).
     */
    public static function obtineCronToken(): string
    {
        $token = static::get(self::CHEIE_CRON_TOKEN);
        if ($token) {
            return $token;
        }

        $nou = (string) \Illuminate\Support\Str::uuid();
        static::set(self::CHEIE_CRON_TOKEN, $nou);
        return $nou;
    }

    /**
     * Faza 6.8 — Forteaza generarea unui token nou (regenerare). Tokenul vechi
     * devine invalid; cron-urile din cPanel cu URL vechi vor primi 404 pana la
     * actualizare. Apelat din UI cu wire:confirm.
     */
    public static function regenereazaCronToken(): string
    {
        $nou = (string) \Illuminate\Support\Str::uuid();
        static::set(self::CHEIE_CRON_TOKEN, $nou);
        return $nou;
    }
}
