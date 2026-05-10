<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vizita de igienizare la un dozator cu bidoane.
 * Vezi DOCUMENTATION.md §2 tabel `vizite` + flux §4.2.
 *
 * Sursa de adevar pentru "cand s-a facut ultima igienizare" la un dozator.
 * Update-ul `perioada_igenizare` pe model Dozator se face la creare/marcare.
 */
class Vizita extends Model
{
    protected $table = 'vizite';

    protected $fillable = [
        'id_dozator',
        'id_client',
        'id_adresa',
        'id_masina',
        'data_vizita',
        'data_urmatoare',
        'livrat',
        'achitat',
        'pret',
        'observatii',
    ];

    protected function casts(): array
    {
        return [
            'data_vizita' => 'date',
            'data_urmatoare' => 'date',
            'livrat' => 'boolean',
            'achitat' => 'boolean',
        ];
    }

    public function dozator(): BelongsTo
    {
        return $this->belongsTo(Dozator::class, 'id_dozator');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'id_client');
    }

    public function adresa(): BelongsTo
    {
        return $this->belongsTo(AdresaLivrare::class, 'id_adresa');
    }

    public function masina(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'id_masina');
    }
}
