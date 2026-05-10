<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Faza 6.5 — Template email editabil din UI.
 *
 * Identificat prin `cheie` (slug stabil folosit in cod). Apelantii NU cunosc
 * id-urile DB — folosesc doar cheia. Asta permite migrarea/regenerarea
 * tabelului fara a sparge codul.
 */
class TemplateEmail extends Model
{
    protected $table = 'templateuri_email';

    protected $fillable = [
        'cheie',
        'denumire',
        'subiect',
        'continut_html',
        'descriere_placeholdere',
        'activ',
    ];

    protected function casts(): array
    {
        return [
            'activ' => 'boolean',
        ];
    }

    /**
     * Cauta un template dupa cheie. Returneaza null daca nu exista sau e
     * dezactivat — apelantul (MailService) trateaza ambele cazuri identic.
     */
    public static function gasestePeCheie(string $cheie): ?self
    {
        return static::where('cheie', $cheie)->where('activ', true)->first();
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('activ', true);
    }
}
