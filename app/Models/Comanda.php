<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comanda extends Model
{
    protected $table = 'comenzi';

    public const TIP_ABONAMENT = 'abonament';
    public const TIP_CONSUM_SUPLIMENTAR = 'consum suplimentar';
    public const TIP_FARA_ABONAMENT = 'fara abonament';

    public const MODPLATA_CASH = 1;
    public const MODPLATA_OP = 2;
    public const MODPLATA_CARD = 3;
    public const MODPLATA_ALTA = 4;

    public const STATUS_IN_ASTEPTARE = 'In asteptare';
    public const STATUS_RESPINS = 'Respins';

    protected $fillable = [
        'id_client',
        'id_adresa',
        'id_masina',
        'id_depozit',
        'tip_comanda',
        'nr_recipienti',
        'nr_pahare',
        'id_modalitate_plata',
        'data_livrare',
        'interval_livrare',
        'livrat',
        'achitat',
        'invoice_generated',
        'factura_serie',
        'factura_numar',
        'factura_link',
        'factura_furnizor',
        'luna_livrata',
        'status',
        'motiv_respingere',
        'data_respingere',
        'aprobat_de',
        'id_utilizator',
        'nume',
        'telefon',
        'observatii',
        'ordine_traseu',
    ];

    protected function casts(): array
    {
        return [
            'nr_recipienti' => 'integer',
            'nr_pahare' => 'integer',
            'id_modalitate_plata' => 'integer',
            'data_livrare' => 'date',
            'data_respingere' => 'datetime',
            'livrat' => 'boolean',
            'achitat' => 'boolean',
            'invoice_generated' => 'boolean',
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

    public function aprobatDe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobat_de');
    }

    public function utilizator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_utilizator');
    }

    public function produse(): HasMany
    {
        return $this->hasMany(ComandaProdus::class, 'id_comanda');
    }

    public function etichetaTip(): string
    {
        return match ($this->tip_comanda) {
            self::TIP_ABONAMENT => 'Abonament',
            self::TIP_CONSUM_SUPLIMENTAR => 'Consum suplimentar',
            default => 'Fara abonament',
        };
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

    public function isAbonament(): bool
    {
        return $this->tip_comanda === self::TIP_ABONAMENT;
    }

    public function isInAsteptare(): bool
    {
        return $this->status === self::STATUS_IN_ASTEPTARE;
    }

    public function isRespinsa(): bool
    {
        return $this->status === self::STATUS_RESPINS;
    }

    /**
     * Total comanda din suma liniilor (sursa de adevar pentru pret).
     */
    public function total(): float
    {
        return (float) $this->produse->sum(fn ($l) => (float) $l->cantitate * (float) $l->pret);
    }

    /**
     * Format YYYY/MM derivat din data_livrare — folosit pentru auto-completarea
     * campului `luna_livrata` la save daca admin nu il introduce explicit.
     */
    public function lunaLivrataAuto(): ?string
    {
        return $this->data_livrare?->format('Y/m');
    }
}
