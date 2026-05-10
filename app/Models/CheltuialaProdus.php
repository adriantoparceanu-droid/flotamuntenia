<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Linie de factura de cheltuieli (Faza 5.1).
 * Pattern identic cu `ComandaProdus`.
 */
class CheltuialaProdus extends Model
{
    protected $table = 'cheltuieli_produse';

    protected $fillable = [
        'id_cheltuiala',
        'id_produs',
        'cantitate',
        'pret',
    ];

    protected function casts(): array
    {
        return [
            'cantitate' => 'integer',
            'pret' => 'decimal:2',
        ];
    }

    public function cheltuiala(): BelongsTo
    {
        return $this->belongsTo(Cheltuiala::class, 'id_cheltuiala');
    }

    public function produs(): BelongsTo
    {
        return $this->belongsTo(CostProduct::class, 'id_produs');
    }

    public function subtotal(): float
    {
        return (float) $this->cantitate * (float) $this->pret;
    }
}
