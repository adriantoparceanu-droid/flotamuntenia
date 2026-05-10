<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Jurnalul de miscari de stoc. Sursa unica de adevar — stocul se calculeaza
// prin agregare (regula §8.1), nu se stocheaza separat.
class Stoc extends Model
{
    protected $table = 'stoc';

    public const TIP_IN = 'IN';
    public const TIP_OUT = 'OUT';
    public const TIP_CUSTODIE = 'CUSTODIE';

    public const REF_COMANDA = 'comanda';
    public const REF_COMANDA_RAPIDA = 'comanda_rapida';
    public const REF_CHELTUIALA = 'cheltuiala';
    public const REF_DOZATOR = 'dozator';
    public const REF_DOZATOR_FILTRU = 'dozator_filtru';
    public const REF_MANUAL = 'manual';

    protected $fillable = [
        'id_produs',
        'id_depozit',
        'cantitate',
        'tip',
        'id_referinta',
        'tip_referinta',
        'data',
        'observatii',
    ];

    protected function casts(): array
    {
        return [
            'cantitate' => 'integer',
            'data' => 'date',
        ];
    }

    public function produs(): BelongsTo
    {
        return $this->belongsTo(CostProduct::class, 'id_produs');
    }

    public function depozit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class, 'id_depozit');
    }

    /**
     * Filtreaza miscarile generate de o entitate (ex: o comanda specifica).
     */
    public function scopePentruReferinta(Builder $q, string $tip, int $id): Builder
    {
        return $q->where('tip_referinta', $tip)->where('id_referinta', $id);
    }

    /**
     * Soldul curent pentru o pereche (produs, depozit), agregat din jurnal.
     * IN si CUSTODIE conteaza diferit fata de OUT — CUSTODIE iese fizic dar
     * ramane "urmaribil" la client (nu intra inapoi pe stoc).
     *
     * Returneaza intregul cu semn:
     *   sold = SUM(cantitate WHERE tip='IN') - SUM(cantitate WHERE tip IN ('OUT','CUSTODIE'))
     */
    public static function soldPerProdusDepozit(int $idProdus, int $idDepozit): int
    {
        $intrari = self::query()
            ->where('id_produs', $idProdus)
            ->where('id_depozit', $idDepozit)
            ->where('tip', self::TIP_IN)
            ->sum('cantitate');

        $iesiri = self::query()
            ->where('id_produs', $idProdus)
            ->where('id_depozit', $idDepozit)
            ->whereIn('tip', [self::TIP_OUT, self::TIP_CUSTODIE])
            ->sum('cantitate');

        return (int) $intrari - (int) $iesiri;
    }

    /**
     * Faza 5.2 — Agregare matriceala (produs × depozit × tip) pentru raport stoc.
     *
     * Foloseste un singur query GROUP BY pentru a evita N×M selecturi separate.
     * Returneaza un nested array indexat dupa [id_produs][id_depozit] cu toate
     * cele 3 totaluri (IN/OUT/CUSTODIE). Apelantul calculeaza apoi:
     *   sold      = IN - OUT - CUSTODIE
     *   custodie  = CUSTODIE
     *   totalFirma = sold + custodie = IN - OUT
     *
     * Filtre optionale aplicate la nivel de WHERE pe stoc, NU pe agregare —
     * astfel produsele/depozitele care nu au mişcari raman excluse natural
     * (ce vrem la „doar produse cu mişcari").
     */
    public static function agregatPerDepozit(?array $idDepozite = null): array
    {
        // null = toate depozitele; [] = niciunul (rezultat gol).
        // Distingem explicit ca utilizatorul sa poata deselecta toate
        // checkboxurile si sa vada matrice goala (nu toata istoria).
        if ($idDepozite !== null && empty($idDepozite)) {
            return [];
        }

        $q = self::query()
            ->select('id_produs', 'id_depozit', 'tip')
            ->selectRaw('SUM(cantitate) as total')
            ->groupBy('id_produs', 'id_depozit', 'tip');

        if (! empty($idDepozite)) {
            $q->whereIn('id_depozit', $idDepozite);
        }

        $matrice = [];
        foreach ($q->get() as $rand) {
            $idProdus = (int) $rand->id_produs;
            $idDepozit = (int) $rand->id_depozit;
            $matrice[$idProdus] ??= [];
            $matrice[$idProdus][$idDepozit] ??= [
                self::TIP_IN => 0,
                self::TIP_OUT => 0,
                self::TIP_CUSTODIE => 0,
            ];
            $matrice[$idProdus][$idDepozit][$rand->tip] = (int) $rand->total;
        }

        return $matrice;
    }
}
