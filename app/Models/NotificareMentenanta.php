<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail pentru notificarile manuale 30/15 zile trimise pentru
 * dozatoare cu filtre (regula §8.6 din DOCUMENTATION.md — notificarile NU
 * se trimit automat, admin apasa „Trimite reminder" si pastram log-ul aici).
 *
 * Fara timestamps Eloquent — `data_trimitere` e singurul camp temporal,
 * cu DEFAULT CURRENT_TIMESTAMP la insert; nu se editeaza dupa.
 */
class NotificareMentenanta extends Model
{
    protected $table = 'notificari_mentenanta';

    public $timestamps = false;

    public const TIP_30_ZILE = '30_zile';
    public const TIP_15_ZILE = '15_zile';

    protected $fillable = [
        'id_dozator_filtre',
        'id_client',
        'tip_notificare',
        'data_trimitere',
        'trimis_de',
    ];

    protected function casts(): array
    {
        return [
            'data_trimitere' => 'datetime',
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

    public function trimisDe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trimis_de');
    }

    public function etichetaTip(): string
    {
        return $this->tip_notificare === self::TIP_15_ZILE ? '15 zile' : '30 zile';
    }
}
