<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComandaProdus extends Model
{
    protected $table = 'comenzi_produse';

    protected $fillable = [
        'id_comanda',
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

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class, 'id_comanda');
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
