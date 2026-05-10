<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ContracteService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Faza 6.2 — Descarca contractul unui client ca PDF.
 *
 * Genereaza PDF-ul pe loc din `contracte_clienti.continut_html` (DomPDF cu
 * font DejaVu Sans pentru diacritice romanesti). Daca clientul nu are inca
 * contract, e creat ad-hoc din template-ul global.
 *
 * Disposition: `inline` — browserul afiseaza PDF-ul in tab; userul poate
 * descarca cu butonul de save al previzualizatorului.
 */
class ContractPdfController extends Controller
{
    public function __invoke(Client $client): Response
    {
        $contract = ContracteService::obtineContract($client);

        $pdf = ContracteService::genereazaPdf(
            $contract->continut_html ?? '',
            'Contract ' . $client->denumire,
        );

        $nume = 'contract-' . Str::slug($client->denumire ?? 'client') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nume . '"',
            // Evitam cache-ul ca sa reflectam imediat editarile
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
