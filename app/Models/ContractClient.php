<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Faza 6.2 — Snapshot HTML al contractului unui client.
 *
 * 1:1 cu `Client` (UNIQUE pe id_client). Continutul HTML poate fi:
 *   - generat din template-ul global la prima accesare (cu placeholdere
 *     substituite din datele clientului — vezi `ContracteService`);
 *   - editat ad-hoc de admin din UI (TinyMCE in tab Contract pe Detalii);
 *   - regenerat oricand din template (suprascrie ce a fost editat manual).
 *
 * PDF-ul se genereaza pe loc din `continut_html` (DomPDF) — nu salvat ca fisier.
 */
class ContractClient extends Model
{
    protected $table = 'contracte_clienti';

    protected $fillable = [
        'id_client',
        'continut_html',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'id_client');
    }
}
