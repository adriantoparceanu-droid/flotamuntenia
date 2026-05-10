<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    protected $table = 'clienti';

    public const TIP_PJ = 1; // Persoana juridica (firma)
    public const TIP_PF = 2; // Persoana fizica

    protected $fillable = [
        'cod_client',
        'client',
        'denumire',
        'cif',
        'reg_com',
        'oras',
        'strada',
        'nr',
        'bloc',
        'scara',
        'etaj',
        'apartament',
        'sector',
        'interfon',
        'email',
        'telefon',
        'observatii',
        'reziliat',
        'observatii_reziliere',
        'data_adaugare',
    ];

    protected function casts(): array
    {
        return [
            'client' => 'integer',
            'reziliat' => 'boolean',
            'data_adaugare' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Setam data_adaugare automat la creare daca nu e furnizata.
        static::creating(function (Client $client) {
            if (empty($client->data_adaugare)) {
                $client->data_adaugare = now()->toDateString();
            }
        });
    }

    public function adrese(): HasMany
    {
        return $this->hasMany(AdresaLivrare::class, 'id_client');
    }

    public function utilizatori(): HasMany
    {
        return $this->hasMany(User::class, 'id_client');
    }

    /**
     * Comenzile clientului. Folosit pentru top clienti pe dashboard,
     * istoric portal, rapoarte.
     */
    public function comenzi(): HasMany
    {
        return $this->hasMany(Comanda::class, 'id_client');
    }

    /**
     * Faza 6.2 — Snapshot HTML al contractului per client (1:1).
     */
    public function contract(): HasOne
    {
        return $this->hasOne(ContractClient::class, 'id_client');
    }

    public function isPJ(): bool
    {
        return $this->client === self::TIP_PJ;
    }

    public function isPF(): bool
    {
        return $this->client === self::TIP_PF;
    }

    /**
     * Returneaza adresa sediului ca un singur string lizibil.
     */
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
}
