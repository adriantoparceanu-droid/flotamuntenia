<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Factura de achizitii/cheltuieli (Faza 5.1).
 * Vezi DOCUMENTATION.md §425 (schema) + §3.7 (flux).
 *
 * Genereaza mişcari de stoc IN per linie de produs (regula §8.1 — soldul se
 * agrega din jurnal). Pattern revert+recreate la fiecare salvare via
 * `MiscariStocService::sincronizeazaIntrariCheltuiala`.
 */
class Cheltuiala extends Model
{
    protected $table = 'cheltuieli';

    protected $fillable = [
        'nr_factura',
        'furnizor',
        'id_depozit',
        'data',
        'total',
        'achitat',
        'observatii',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'total' => 'decimal:2',
            'achitat' => 'boolean',
        ];
    }

    public function depozit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class, 'id_depozit');
    }

    public function produse(): HasMany
    {
        return $this->hasMany(CheltuialaProdus::class, 'id_cheltuiala');
    }

    /**
     * Recalculeaza totalul din liniile incarcate (sum cantitate × pret).
     * Folosit la salvare pentru a persista o valoare consistenta cu liniile.
     */
    public function recalculeazaTotal(): float
    {
        $this->loadMissing('produse');
        return (float) $this->produse->sum(fn ($l) => (float) $l->cantitate * (float) $l->pret);
    }
}
