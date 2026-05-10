<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComandaRapida extends Model
{
    protected $table = 'comenzi_rapide';

    protected $fillable = [
        'id_masina',
        'id_depozit',
        'denumire',
        'adresa',
        'telefon',
        'lat',
        'lng',
        'data_livrare',
        'livrat',
        'achitat',
        'ordine_traseu',
        'observatii',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'data_livrare' => 'date',
            'livrat' => 'boolean',
            'achitat' => 'boolean',
            'ordine_traseu' => 'integer',
        ];
    }

    public function masina(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'id_masina');
    }

    public function depozit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class, 'id_depozit');
    }

    public function produse(): HasMany
    {
        return $this->hasMany(ComandaRapidaProdus::class, 'id_comanda_rapida');
    }

    public function areCoordonateGps(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    public function total(): float
    {
        return (float) $this->produse->sum(fn ($l) => (float) $l->cantitate * (float) $l->pret);
    }
}
