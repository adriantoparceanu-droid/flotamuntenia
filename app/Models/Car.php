<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Car extends Model
{
    protected $fillable = [
        'denumire',
        'nr_inmatriculare',
        'id_depozit',
        'culoare',
        'activ',
    ];

    protected function casts(): array
    {
        return [
            'activ' => 'boolean',
        ];
    }

    public function depozit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class, 'id_depozit');
    }
}
