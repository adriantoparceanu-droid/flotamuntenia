<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCategory extends Model
{
    protected $fillable = [
        'denumire',
        'activ',
    ];

    protected function casts(): array
    {
        return [
            'activ' => 'boolean',
        ];
    }

    public function produse(): HasMany
    {
        return $this->hasMany(CostProduct::class, 'id_category');
    }
}
