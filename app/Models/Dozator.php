<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dozator cu BIDOANE in custodie sau cumparat la un client.
 *
 * NU se confunda cu `dozatoare_filtre` (Faza 4.3) — entitate separata,
 * cu logica de mentenanta diferita (regula §8.5 din DOCUMENTATION.md).
 *
 * Mişcari de stoc generate prin `MiscariStocService::sincronizeazaCustodieDozator()`:
 *  - tranzactie='custodie' => Stoc CUSTODIE (bidoanele raman ale firmei dar la client)
 *  - tranzactie='cumparat' => Stoc OUT (vandut, iese complet din inventar)
 */
class Dozator extends Model
{
    protected $table = 'dozator';

    public const TRANZACTIE_CUSTODIE = 'custodie';
    public const TRANZACTIE_CUMPARAT = 'cumparat';

    protected $fillable = [
        'id_client',
        'id_adresa',
        'id_masina',
        'id_produs',
        'id_depozit',
        'serie',
        'tranzactie',
        'data_instalare',
        'comanda',
        'activ',
        'perioada_igenizare',
        'observatii',
    ];

    protected function casts(): array
    {
        return [
            'data_instalare' => 'date',
            'perioada_igenizare' => 'date',
            'comanda' => 'boolean',
            'activ' => 'boolean',
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

    public function produs(): BelongsTo
    {
        return $this->belongsTo(CostProduct::class, 'id_produs');
    }

    public function depozit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class, 'id_depozit');
    }

    public function vizite(): HasMany
    {
        return $this->hasMany(Vizita::class, 'id_dozator')->orderByDesc('data_vizita');
    }

    public function remindere(): HasMany
    {
        return $this->hasMany(DozatorReminder::class, 'id_dozator')->orderByDesc('trimis_la');
    }

    public function etichetaTranzactie(): string
    {
        return $this->tranzactie === self::TRANZACTIE_CUMPARAT ? 'Cumparat' : 'Custodie';
    }

    /**
     * Status igienizare bazat pe scadenta:
     *  - fara_data: perioada_igenizare nu e completata
     *  - la_zi: > 30 zile pana la scadenta
     *  - scadent_30: intre 16 si 30 zile (inclusiv)
     *  - scadent_15: intre 0 si 15 zile (inclusiv) — urgent
     *  - expirat: trecut de scadenta
     *
     * Conventia §272 din DOCUMENTATION.md (verde/galben/rosu/negru) e mapata
     * pe culoareStatus().
     */
    public function statusIgienizare(): string
    {
        if (! $this->perioada_igenizare) {
            return 'fara_data';
        }
        $diff = now()->startOfDay()->diffInDays($this->perioada_igenizare->startOfDay(), false);

        if ($diff < 0) {
            return 'expirat';
        }
        if ($diff <= 15) {
            return 'scadent_15';
        }
        if ($diff <= 30) {
            return 'scadent_30';
        }
        return 'la_zi';
    }

    public function etichetaStatusIgienizare(): string
    {
        return match ($this->statusIgienizare()) {
            'la_zi' => 'La zi',
            'scadent_30' => 'Scadent in 30 zile',
            'scadent_15' => 'Urgent (15 zile)',
            'expirat' => 'Expirat',
            default => 'Fara data',
        };
    }

    /**
     * Clase Tailwind pentru pill-ul de status (consistent cu pattern-ul din
     * Comenzi/Index si Aprobare).
     */
    public function culoareStatusIgienizare(): string
    {
        return match ($this->statusIgienizare()) {
            'la_zi' => 'bg-emerald-100 text-emerald-700',
            'scadent_30' => 'bg-amber-100 text-amber-700',
            'scadent_15' => 'bg-red-100 text-red-700',
            'expirat' => 'bg-gray-800 text-gray-100',
            default => 'bg-gray-100 text-gray-500',
        };
    }

    /**
     * True daca dozatorul are nevoie de reminder (status != la_zi si != fara_data).
     */
    public function necesitaReminder(): bool
    {
        return in_array($this->statusIgienizare(), ['scadent_30', 'scadent_15', 'expirat'], true);
    }

    public function ultimulReminder(): ?DozatorReminder
    {
        return $this->remindere()->first();
    }
}
