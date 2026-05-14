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

    // Module opționale — valoare '1' = activ, '0' = inactiv
    public const MODUL_PORTAL_CLIENT     = 'modul_portal_client';
    public const MODUL_COMENZI_RAPIDE   = 'modul_comenzi_rapide';
    public const MODUL_PROBLEME         = 'modul_probleme';
    public const MODUL_DOZATOARE        = 'modul_dozatoare';
    public const MODUL_RECIPIENTI       = 'modul_recipienti';
    public const MODUL_STOC             = 'modul_stoc';
    public const MODUL_FACTURARE        = 'modul_facturare';
    public const MODUL_CONTRACTE        = 'modul_contracte';
    public const MODUL_HARTI            = 'modul_harti';
    public const MODUL_RAPOARTE         = 'modul_rapoarte';
    public const MODUL_ANAF             = 'modul_anaf';
    public const MODUL_EMAIL            = 'modul_email';
    public const MODUL_CRON             = 'modul_cron';

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
