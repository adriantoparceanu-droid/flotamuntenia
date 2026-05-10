<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Problema extends Model
{
    protected $table = 'probleme';

    public const MODPLATA_CASH = 1;
    public const MODPLATA_OP = 2;
    public const MODPLATA_CARD = 3;
    public const MODPLATA_ALTA = 4;

    protected $fillable = [
        'id_client',
        'id_adresa',
        'id_masina',
        'id_depozit',
        'descriere',
        'suma',
        'id_modalitate_plata',
        'data_livrare',
        'interval_livrare',
        'nume',
        'telefon',
        'livrat',
        'achitat',
        'ordine_traseu',
    ];

    protected function casts(): array
    {
        return [
            'suma' => 'decimal:2',
            'id_modalitate_plata' => 'integer',
            'data_livrare' => 'date',
            'livrat' => 'boolean',
            'achitat' => 'boolean',
            'ordine_traseu' => 'integer',
        ];
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

    public function depozit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class, 'id_depozit');
    }

    public function etichetaModPlata(): string
    {
        return match ((int) $this->id_modalitate_plata) {
            self::MODPLATA_OP => 'Ordin de plata',
            self::MODPLATA_CARD => 'Card',
            self::MODPLATA_ALTA => 'Alta',
            default => 'Cash',
        };
    }

    /**
     * Total intervenție = suma. Aliniere cu pattern-ul Comanda::total() pentru
     * a putea trata problemele și comenzile uniform în Lista zilnică.
     */
    public function total(): float
    {
        return (float) $this->suma;
    }
}
