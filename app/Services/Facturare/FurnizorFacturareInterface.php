<?php

namespace App\Services\Facturare;

use App\Models\Comanda;

/**
 * Faza 6.1 — Contract comun pentru furnizorii de facturare electronica
 * (Oblio.eu, SmartBill, etc.). Vezi DOCUMENTATION.md §7.4.
 *
 * Implementarile concrete (OblioService, SmartBillService) primesc setarile
 * deja decriptate din DB la constructor. Codul aplicatiei NU instantiaza
 * direct un furnizor — foloseste FacturareService::activ() care alege
 * automat furnizorul activ.
 */
interface FurnizorFacturareInterface
{
    /**
     * Codul intern al furnizorului (ex: 'oblio', 'smartbill').
     */
    public function cod(): string;

    /**
     * Eticheta umana pentru afisare in UI (ex: 'Oblio.eu', 'SmartBill').
     */
    public function eticheta(): string;

    /**
     * Verifica conexiunea cu API-ul furnizorului folosind cheile configurate.
     * Returneaza array cu cheile:
     *   - 'ok' (bool): true daca s-a autentificat cu succes
     *   - 'mesaj' (string): detaliu pentru afisare in UI (eroare sau success)
     *
     * @return array{ok: bool, mesaj: string}
     */
    public function testConexiune(): array;

    /**
     * Emite factura pentru o comanda. Comanda trebuie sa aiba:
     *   - client (PJ sau PF) cu denumire si CIF/CNP
     *   - linii produse (relatia `produse`)
     *   - data_livrare setata
     *
     * Returneaza array cu detaliile facturii emise:
     *   - 'serie' (string): seria documentului (ex: 'WF')
     *   - 'numar' (string): numarul (ex: '0053')
     *   - 'link' (string|null): URL spre PDF la furnizor
     *
     * Arunca FacturareException la orice esec (autentificare, validare,
     * comunicare cu API).
     *
     * @return array{serie: string, numar: string, link: ?string}
     * @throws FacturareException
     */
    public function emiteFactura(Comanda $comanda): array;
}
