<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdresaLivrare extends Model
{
    protected $table = 'adresa_livrare';

    protected $fillable = [
        'id_client',
        'denumire',
        'oras',
        'strada',
        'nr',
        'bloc',
        'scara',
        'etaj',
        'apartament',
        'sector',
        'interfon',
        'lat',
        'lng',
        'activ',
        'data_adaugare',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'activ' => 'boolean',
            'data_adaugare' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AdresaLivrare $adresa) {
            if (empty($adresa->data_adaugare)) {
                $adresa->data_adaugare = now()->toDateString();
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'id_client');
    }

    /**
     * Configurarea de livrare (1:1) — abonament/per bucata/filtre/aparate.
     * Vezi DOCUMENTATION.md §2 tabela 'produs'.
     */
    public function produs(): HasOne
    {
        return $this->hasOne(Produs::class, 'id_adresa');
    }

    public function adresaCompleta(): string
    {
        $parti = array_filter([
            $this->strada ? "Str. {$this->strada}" : null,
            $this->nr ? "nr. {$this->nr}" : null,
            $this->bloc ? "bl. {$this->bloc}" : null,
            $this->scara ? "sc. {$this->scara}" : null,
            $this->etaj ? "et. {$this->etaj}" : null,
            $this->apartament ? "ap. {$this->apartament}" : null,
            $this->sector ? "sect. {$this->sector}" : null,
            $this->oras,
        ]);

        return implode(', ', $parti);
    }

    public function areCoordonateGps(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    /**
     * Miscarile de recipienti la aceasta adresa (jurnalul complet).
     */
    public function recipienti(): HasMany
    {
        return $this->hasMany(Recipient::class, 'id_adresa');
    }

    /**
     * Soldul curent al recipientilor de recuperat — agregat din jurnal.
     * Returneaza ['19l' => int, '11l' => int]; valorile nu pot fi negative (§8.2).
     */
    public function soldRecipienti(): array
    {
        return Recipient::soldPerAdresa($this->id);
    }
}
