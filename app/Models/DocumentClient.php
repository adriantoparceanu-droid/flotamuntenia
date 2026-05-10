<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Faza 6.7 — Document atasat la un client.
 *
 * Fisierul fizic e in `storage/app/private/documente-clienti/{id_client}/{nume_stocat}`
 * (disk `local`, accesibil doar prin controller cu auth — DocumentDownloadController).
 */
class DocumentClient extends Model
{
    protected $table = 'documente_clienti';

    protected $fillable = [
        'id_client',
        'nume_fisier',
        'nume_stocat',
        'mime_type',
        'marime_bytes',
        'descriere',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'marime_bytes' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'id_client');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Calea relativa pe disk-ul `local` pentru fisierul fizic.
     * Folosita de DocumentDownloadController + Clienti\Documente la upload/delete.
     */
    public function caleStocare(): string
    {
        return "documente-clienti/{$this->id_client}/{$this->nume_stocat}";
    }

    /**
     * Returneaza dimensiunea formatata uman (KB sau MB).
     */
    public function marimeUmana(): string
    {
        $b = $this->marime_bytes;
        if ($b < 1024) {
            return $b . ' B';
        }
        if ($b < 1024 * 1024) {
            return number_format($b / 1024, 1, ',', '.') . ' KB';
        }
        return number_format($b / 1024 / 1024, 1, ',', '.') . ' MB';
    }

    /**
     * Returneaza numele iconitei Heroicons pentru tipul de fisier.
     * Folosit in UI lista documente — vizual rapid.
     */
    public function iconHeroicon(): string
    {
        $mime = (string) $this->mime_type;

        if (str_starts_with($mime, 'image/')) {
            return 'photo';
        }
        if ($mime === 'application/pdf') {
            return 'document-text';
        }
        if (str_contains($mime, 'word') || str_contains($mime, 'officedocument.wordprocessingml')) {
            return 'document';
        }
        if (str_contains($mime, 'sheet') || str_contains($mime, 'excel')) {
            return 'table-cells';
        }
        if (str_contains($mime, 'zip') || str_contains($mime, 'compressed')) {
            return 'archive-box';
        }

        return 'document';
    }

    /**
     * Returneaza extensia originala (din nume_fisier) — fallback la nume_stocat.
     */
    public function extensie(): string
    {
        return strtolower(pathinfo($this->nume_fisier, PATHINFO_EXTENSION) ?: pathinfo($this->nume_stocat, PATHINFO_EXTENSION));
    }
}
