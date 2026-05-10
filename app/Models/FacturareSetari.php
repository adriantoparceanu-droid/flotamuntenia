<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Faza 6.1 — Setari pentru un furnizor de facturare electronica.
 *
 * Coloana `setari` e stocata criptat (cast `encrypted:array`); contine cheile
 * API + CIF-ul emitentului + seria de factura, in functie de furnizor:
 *   - Oblio: ['client_id' => email, 'client_secret' => token, 'cif' => 'RO...', 'seriesName' => 'WF', 'language' => 'RO', 'currency' => 'RON', 'dueDateOffsetDays' => 15]
 *   - SmartBill: ['username' => email, 'token' => api_token, 'companyVatCode' => 'RO...', 'seriesName' => 'WF', 'currency' => 'RON', 'dueDateOffsetDays' => 15, 'isDraft' => false]
 *
 * Doar un singur furnizor poate fi activ. Helper `activ()` returneaza
 * inregistrarea curenta sau null.
 */
class FacturareSetari extends Model
{
    protected $table = 'facturare_setari';

    public const FURNIZOR_OBLIO = 'oblio';
    public const FURNIZOR_SMARTBILL = 'smartbill';

    protected $fillable = [
        'furnizor',
        'setari',
        'activ',
    ];

    protected function casts(): array
    {
        return [
            // JSON criptat in DB; in cod e array PHP normal.
            'setari' => 'encrypted:array',
            'activ' => 'boolean',
        ];
    }

    /**
     * Returneaza configurarea furnizorului activ sau null daca niciunul.
     */
    public static function activ(): ?self
    {
        return static::where('activ', true)->first();
    }

    /**
     * Eticheta umana pentru afisare in UI.
     */
    public function eticheta(): string
    {
        return match ($this->furnizor) {
            self::FURNIZOR_OBLIO => 'Oblio.eu',
            self::FURNIZOR_SMARTBILL => 'SmartBill',
            default => ucfirst($this->furnizor),
        };
    }

    /**
     * Verifica daca toate cheile minime cerute sunt prezente in setari.
     */
    public function esteConfigurat(): bool
    {
        $s = $this->setari ?? [];
        return match ($this->furnizor) {
            self::FURNIZOR_OBLIO => ! empty($s['client_id'])
                && ! empty($s['client_secret'])
                && ! empty($s['cif'])
                && ! empty($s['seriesName']),
            self::FURNIZOR_SMARTBILL => ! empty($s['username'])
                && ! empty($s['token'])
                && ! empty($s['companyVatCode'])
                && ! empty($s['seriesName']),
            default => false,
        };
    }
}
