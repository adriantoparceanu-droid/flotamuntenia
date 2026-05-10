<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dozator cu FILTRE in custodie sau cumparat la un client.
 *
 * Entitate complet separata fata de `Dozator` (Faza 4.1, bidoane). Logica de
 * mentenanta NU se bazeaza pe vizite de igienizare fizica — se bazeaza pe
 * intervale calendaristice (schimb filtre la 12 luni standard) si notificari
 * MANUALE 30/15 zile (regula §8.6 din DOCUMENTATION.md).
 *
 * Tabela proprie `dozatoare_filtre` + istoric proprii in `dozatoare_filtre_istoric`.
 * Notificarile in `notificari_mentenanta` (cu tip enum 30_zile/15_zile).
 *
 * Mişcari de stoc generate prin `MiscariStocService::sincronizeazaCustodieDozatorFiltre()`:
 *  - tranzactie='custodie' => Stoc CUSTODIE (filtrele raman ale firmei dar la client)
 *  - tranzactie='cumparat' => Stoc OUT (vandut, iese complet din inventar)
 */
class DozatorFiltre extends Model
{
    protected $table = 'dozatoare_filtre';

    public const TRANZACTIE_CUSTODIE = 'custodie';
    public const TRANZACTIE_CUMPARAT = 'cumparat';

    public const STATUS_ACTIV = 'activ';
    public const STATUS_RETRAS = 'retras';

    protected $fillable = [
        'id_client',
        'id_adresa',
        'id_masina',
        'id_produs',
        'id_depozit',
        'serie',
        'tranzactie',
        'data_instalare',
        'data_ultima_mentenanta',
        'data_urmatoare_mentenanta',
        'status',
        'suma_garantie',
        'observatii',
    ];

    protected function casts(): array
    {
        return [
            'data_instalare' => 'date',
            'data_ultima_mentenanta' => 'date',
            'data_urmatoare_mentenanta' => 'date',
            'suma_garantie' => 'decimal:2',
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

    public function istoric(): HasMany
    {
        return $this->hasMany(DozatorFiltreIstoric::class, 'id_dozator_filtre')
            ->orderByDesc('data_interventie');
    }

    public function notificari(): HasMany
    {
        return $this->hasMany(NotificareMentenanta::class, 'id_dozator_filtre')
            ->orderByDesc('data_trimitere');
    }

    public function etichetaTranzactie(): string
    {
        return $this->tranzactie === self::TRANZACTIE_CUMPARAT ? 'Cumparat' : 'Custodie';
    }

    public function esteActiv(): bool
    {
        return $this->status === self::STATUS_ACTIV;
    }

    /**
     * Status mentenanta bazat pe scadenta (acelasi pattern ca Dozator::statusIgienizare()):
     *  - fara_data: data_urmatoare_mentenanta nu e completata
     *  - la_zi: > 30 zile pana la scadenta
     *  - scadent_30: intre 16 si 30 zile (inclusiv)
     *  - scadent_15: intre 0 si 15 zile (inclusiv) — urgent
     *  - expirat: trecut de scadenta
     */
    public function statusMentenanta(): string
    {
        if (! $this->data_urmatoare_mentenanta) {
            return 'fara_data';
        }
        $diff = now()->startOfDay()->diffInDays($this->data_urmatoare_mentenanta->startOfDay(), false);

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

    public function etichetaStatusMentenanta(): string
    {
        return match ($this->statusMentenanta()) {
            'la_zi' => 'La zi',
            'scadent_30' => 'Scadent in 30 zile',
            'scadent_15' => 'Urgent (15 zile)',
            'expirat' => 'Expirat',
            default => 'Fara data',
        };
    }

    /**
     * Aceleasi clase Tailwind ca pe Dozator (consistenta vizuala intre tab-uri).
     */
    public function culoareStatusMentenanta(): string
    {
        return match ($this->statusMentenanta()) {
            'la_zi' => 'bg-emerald-100 text-emerald-700',
            'scadent_30' => 'bg-amber-100 text-amber-700',
            'scadent_15' => 'bg-red-100 text-red-700',
            'expirat' => 'bg-gray-800 text-gray-100',
            default => 'bg-gray-100 text-gray-500',
        };
    }

    public function necesitaReminder(): bool
    {
        return in_array($this->statusMentenanta(), ['scadent_30', 'scadent_15', 'expirat'], true);
    }

    /**
     * Auto-detecteaza tipul de notificare pe care admin-ul vrea sa-l trimita,
     * pe baza scadentei. `15_zile` daca scadenta e <= 15 zile sau expirat,
     * altfel `30_zile`. Folosit de UI cand admin apasa „Trimite reminder"
     * (un singur buton, tip auto-detectat — decizie validata cu user).
     */
    public function tipReminderAuto(): string
    {
        $status = $this->statusMentenanta();
        return in_array($status, ['scadent_15', 'expirat'], true) ? '15_zile' : '30_zile';
    }

    public function ultimaNotificare(): ?NotificareMentenanta
    {
        return $this->notificari()->first();
    }
}
