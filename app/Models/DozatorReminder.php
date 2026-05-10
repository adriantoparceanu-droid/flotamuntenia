<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail pentru reminder-ele de igienizare trimise dozatoarelor cu bidoane.
 *
 * NU foloseste timestamps Eloquent (`created_at`/`updated_at`) — am o singura
 * coloana `trimis_la` cu valoarea autoCURRENT din DB la insert. Nu se editeaza
 * niciodata o intrare existenta.
 */
class DozatorReminder extends Model
{
    protected $table = 'dozator_remindere';

    public $timestamps = false;

    protected $fillable = [
        'id_dozator',
        'trimis_de',
        'trimis_la',
    ];

    protected function casts(): array
    {
        return [
            'trimis_la' => 'datetime',
        ];
    }

    public function dozator(): BelongsTo
    {
        return $this->belongsTo(Dozator::class, 'id_dozator');
    }

    public function trimisDe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trimis_de');
    }
}
