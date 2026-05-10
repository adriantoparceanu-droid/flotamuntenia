<?php

namespace App\Services\Facturare;

use App\Models\Comanda;
use App\Models\FacturareSetari;

/**
 * Faza 6.1 — STUB pentru integrarea SmartBill (in dezvoltare).
 *
 * UI-ul din /setari/facturare expune campurile cerute de docs SmartBill
 * (username, token, companyVatCode, seriesName, currency, isDraft) ca admin
 * sa le poata salva si sa fie gata cand implementam emiteFactura().
 *
 * API reference (limitat — pe baza SDK third-party stevro/smart-bill-sdk):
 *   - Base URL: https://ws.smartbill.ro/SBORO/api
 *   - Auth: Basic Auth (username = email cont, password = API token)
 *   - POST /invoice cu payload: {companyVatCode, client, products, isDraft,
 *     seriesName, currency, issueDate, dueDate}
 *   - Response: {number, series, url}
 *
 * TODO emiteFactura(): implementare reala dupa obtinerea credentialelor de
 * test si verificarea cu docs oficiale SmartBill.
 *
 * Setari asteptate (din FacturareSetari->setari):
 *   - username (string): emailul contului SmartBill
 *   - token (string): API token din contul SmartBill
 *   - companyVatCode (string): CIF emitent (cu sau fara prefix RO)
 *   - seriesName (string): seria de facturi
 *   - currency (string, default 'RON')
 *   - dueDateOffsetDays (int, default 15)
 *   - isDraft (bool, default false): true = factura draft, false = fiscalizata
 */
class SmartBillService implements FurnizorFacturareInterface
{
    public function __construct(private readonly array $setari)
    {
    }

    public function cod(): string
    {
        return FacturareSetari::FURNIZOR_SMARTBILL;
    }

    public function eticheta(): string
    {
        return 'SmartBill';
    }

    public function testConexiune(): array
    {
        // STUB: doar valideaza ca setarile sunt completate; apelul real la API
        // nu e implementat inca.
        $username = $this->setari['username'] ?? null;
        $token = $this->setari['token'] ?? null;
        $vatCode = $this->setari['companyVatCode'] ?? null;

        if (! $username || ! $token || ! $vatCode) {
            return ['ok' => false, 'mesaj' => 'Setari SmartBill incomplete (username, token, companyVatCode obligatorii).'];
        }

        return [
            'ok' => false,
            'mesaj' => 'Integrare SmartBill in curs de dezvoltare. Setarile sunt salvate, dar emiterea reala a facturilor nu este inca implementata.',
        ];
    }

    public function emiteFactura(Comanda $comanda): array
    {
        throw new FacturareException(
            'Integrare SmartBill in curs de dezvoltare. Folositi temporar Oblio sau emiteti factura manual.'
        );
    }
}
