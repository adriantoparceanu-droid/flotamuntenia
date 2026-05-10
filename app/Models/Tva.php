<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tva extends Model
{
    protected $table = 'tva';

    protected $fillable = [
        'valoare',
        'denumire',
        'activ',
    ];

    protected function casts(): array
    {
        return [
            'valoare' => 'decimal:2',
            'activ' => 'boolean',
        ];
    }

    public function produse(): HasMany
    {
        return $this->hasMany(CostProduct::class, 'id_tva');
    }
}
