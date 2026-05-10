<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produs extends Model
{
    protected $table = 'produs';

    public const TIP_PER_BUCATA = 0;
    public const TIP_ABONAMENT = 1;
    public const TIP_FILTRE = 2;
    public const TIP_APARATE = 3;

    protected $fillable = [
        'id_adresa',
        'id_client',
        'abonament',
        'denumire_abonament',
        'nr_bidoane',
        'nr_bidoane_11l',
        'pret',
        'pret_11l',
        'pret_suplimentar_19l',
        'pret_suplimentar_11l',
        'frecventa',
        'zi_livrare',
        'id_masina',
        'id_depozit',
        'observatii',
    ];

    protected function casts(): array
    {
        return [
            'abonament' => 'integer',
            'nr_bidoane' => 'integer',
            'nr_bidoane_11l' => 'integer',
            'pret' => 'decimal:2',
            'pret_11l' => 'decimal:2',
            'pret_suplimentar_19l' => 'decimal:2',
            'pret_suplimentar_11l' => 'decimal:2',
            'frecventa' => 'integer',
            'zi_livrare' => 'date',
        ];
    }

    public function adresa(): BelongsTo
    {
        return $this->belongsTo(AdresaLivrare::class, 'id_adresa');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'id_client');
    }

    public function masina(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'id_masina');
    }

    public function depozit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class, 'id_depozit');
    }

    /**
     * Eticheta lizibila a tipului de configurare.
     */
    public function etichetaTip(): string
    {
        return match ($this->abonament) {
            self::TIP_ABONAMENT => 'Abonament',
            self::TIP_FILTRE => 'Filtre',
            self::TIP_APARATE => 'Aparate',
            default => 'Per bucata',
        };
    }

    public function isAbonament(): bool
    {
        return $this->abonament === self::TIP_ABONAMENT;
    }

    /**
     * Sumar uman al configurarii — folosit in card-ul de adresa.
     * Ex: "Pachet Standard · 5x 19L + 2x 11L incluse · prima livrare 15 Mai 2026"
     */
    public function sumar(): string
    {
        $parti = [];

        if ($this->isAbonament()) {
            if ($this->denumire_abonament) {
                $parti[] = $this->denumire_abonament;
            }

            $cantitati = [];
            if ($this->nr_bidoane > 0) {
                $cantitati[] = "{$this->nr_bidoane}x 19L";
            }
            if ($this->nr_bidoane_11l > 0) {
                $cantitati[] = "{$this->nr_bidoane_11l}x 11L";
            }
            if ($cantitati) {
                $parti[] = implode(' + ', $cantitati) . ' incluse';
            }

            if ($this->zi_livrare) {
                $parti[] = 'prima livrare ' . $this->zi_livrare->format('d M Y');
            }
        }

        return implode(' · ', $parti);
    }
}
