<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipient extends Model
{
    protected $table = 'recipienti';

    protected $fillable = [
        'id_client',
        'id_adresa',
        'lasati',
        'recuperati',
        'lasati_11l',
        'recuperati_11l',
        'data',
        'id_comanda',
        'id_utilizator',
        'observatii',
    ];

    protected function casts(): array
    {
        return [
            'lasati' => 'integer',
            'recuperati' => 'integer',
            'lasati_11l' => 'integer',
            'recuperati_11l' => 'integer',
            'data' => 'date',
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

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class, 'id_comanda');
    }

    public function utilizator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_utilizator');
    }

    /**
     * Soldul curent al recipientilor per adresa.
     * Returneaza un array cu cele doua capacitati: ['19l' => int, '11l' => int].
     *
     * Soldul POATE fi negativ — semnaleaza ca clientul a returnat mai multe
     * bidoane goale decat a primit pline (de regula recuperari de bidoane
     * cu reformat / surplus de la firma anterioara). Soldul pozitiv inseamna
     * "de recuperat la client", soldul negativ inseamna "datorie firma".
     */
    public static function soldPerAdresa(int $idAdresa): array
    {
        $row = self::query()
            ->where('id_adresa', $idAdresa)
            ->selectRaw('
                COALESCE(SUM(lasati), 0) - COALESCE(SUM(recuperati), 0) as sold19l,
                COALESCE(SUM(lasati_11l), 0) - COALESCE(SUM(recuperati_11l), 0) as sold11l
            ')
            ->first();

        return [
            '19l' => (int) ($row->sold19l ?? 0),
            '11l' => (int) ($row->sold11l ?? 0),
        ];
    }
}
