<?php

namespace App\Http\Controllers;

use App\Models\DocumentClient;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Faza 6.7 — Download document atasat la un client.
 *
 * Acces protejat de middleware admin/superadmin (din grupul de rute).
 * Defense in depth: verificam ca fisierul fizic exista pe disk-ul `local`
 * inainte de a returna response. Daca lipseste (orphan record sau backup
 * incomplet), aruncam 404 in loc sa propagam exceptia framework-ului.
 *
 * Disposition: `attachment` cu numele original (nume_fisier, NU nume_stocat
 * care e UUID intern).
 */
class DocumentDownloadController extends Controller
{
    public function __invoke(DocumentClient $document): BinaryFileResponse|Response
    {
        $cale = $document->caleStocare();

        if (! Storage::disk('local')->exists($cale)) {
            throw new NotFoundHttpException('Fisier inexistent pe disk.');
        }

        $caleAbsoluta = Storage::disk('local')->path($cale);

        return response()->download($caleAbsoluta, $document->nume_fisier, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'no-store',
        ]);
    }
}
