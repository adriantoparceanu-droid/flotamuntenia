<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComandaRapidaProdus extends Model
{
    protected $table = 'comenzi_rapide_produse';

    protected $fillable = [
        'id_comanda_rapida',
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

    public function comandaRapida(): BelongsTo
    {
        return $this->belongsTo(ComandaRapida::class, 'id_comanda_rapida');
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
