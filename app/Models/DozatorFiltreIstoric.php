<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Istoric interventii mentenanta pentru un dozator cu filtre.
 * Sursa de adevar pentru "cand s-a facut ultima interventie" + setul tuturor
 * interventiilor pe un dozator. Update-ul `data_ultima_mentenanta` /
 * `data_urmatoare_mentenanta` pe DozatorFiltre se face la creare-istoric.
 */
class DozatorFiltreIstoric extends Model
{
    protected $table = 'dozatoare_filtre_istoric';

    protected $fillable = [
        'id_dozator_filtre',
        'id_client',
        'id_masina',
        'data_interventie',
        'data_urmatoare',
        'pret',
        'observatii',
    ];

    protected function casts(): array
    {
        return [
            'data_interventie' => 'date',
            'data_urmatoare' => 'date',
            'pret' => 'decimal:2',
        ];
    }

    public function dozatorFiltre(): BelongsTo
    {
        return $this->belongsTo(DozatorFiltre::class, 'id_dozator_filtre');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'id_client');
    }

    public function masina(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'id_masina');
    }
}
