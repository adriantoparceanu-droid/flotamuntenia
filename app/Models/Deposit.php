<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deposit extends Model
{
    protected $fillable = [
        'denumire',
        'adresa',
        'activ',
        'implicit',
    ];

    protected function casts(): array
    {
        return [
            'activ'    => 'boolean',
            'implicit' => 'boolean',
        ];
    }

    public static function implicit(): ?self
    {
        return self::where('implicit', true)->where('activ', true)->first();
    }

    public function masini(): HasMany
    {
        return $this->hasMany(Car::class, 'id_depozit');
    }
}
