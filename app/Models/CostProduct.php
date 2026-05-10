<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostProduct extends Model
{
    protected $fillable = [
        'id_category',
        'id_tva',
        'denumire',
        'pret',
        'activ',
    ];

    protected function casts(): array
    {
        return [
            'pret' => 'decimal:2',
            'activ' => 'boolean',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class, 'id_category');
    }

    public function tva(): BelongsTo
    {
        return $this->belongsTo(Tva::class, 'id_tva');
    }
}
