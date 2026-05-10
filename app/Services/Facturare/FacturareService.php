<?php

namespace App\Services\Facturare;

use App\Models\Comanda;
use App\Models\FacturareSetari;

/**
 * Faza 6.1 — Orchestrare facturare electronica (factory).
 *
 * Codul aplicatiei foloseste:
 *   - FacturareService::activ() pentru a obtine furnizorul activ
 *   - FacturareService::pentruFurnizor('oblio') cand are nevoie de o instanta
 *     specifica (ex: testarea conexiunii in UI inainte de activare)
 *   - FacturareService::furnizoriDisponibili() pentru lista in UI
 *
 * Persistarea facturii pe comanda (factura_serie/factura_numar/factura_link/
 * factura_furnizor + invoice_generated=true) o face componenta Livewire dupa
 * apelul reusit la emiteFactura(); serviciul ramane pur API-call, fara
 * efecte colaterale pe DB.
 */
class FacturareService
{
    /**
     * Lista cu codurile furnizorilor disponibili in aplicatie + clasele lor.
     *
     * @return array<string, class-string<FurnizorFacturareInterface>>
     */
    public static function furnizoriDisponibili(): array
    {
        return [
            FacturareSetari::FURNIZOR_OBLIO => OblioService::class,
            FacturareSetari::FURNIZOR_SMARTBILL => SmartBillService::class,
        ];
    }

    /**
     * Returneaza instanta furnizorului activ.
     *
     * @throws FacturareException daca niciun furnizor nu e activ sau setarile sunt invalide
     */
    public static function activ(): FurnizorFacturareInterface
    {
        $setari = FacturareSetari::activ();
        if (! $setari) {
            throw new FacturareException(
                'Niciun furnizor de facturare nu este activ. Configureaza unul din /setari/facturare.'
            );
        }
        if (! $setari->esteConfigurat()) {
            throw new FacturareException(
                "Furnizorul activ ({$setari->eticheta()}) nu este complet configurat. Verifica setarile."
            );
        }

        return self::instanta($setari->furnizor, $setari->setari ?? []);
    }

    /**
     * Returneaza o instanta a unui furnizor specific (folosita pentru testarea
     * conexiunii inainte de activare).
     *
     * @param  array<string, mixed>  $setari  setari decriptate
     */
    public static function pentruFurnizor(string $cod, array $setari): FurnizorFacturareInterface
    {
        return self::instanta($cod, $setari);
    }

    /**
     * Wrapper care emite factura prin furnizorul activ si persisteaza
     * rezultatul pe comanda (helper folosit din componente).
     *
     * @return array{serie: string, numar: string, link: ?string}
     * @throws FacturareException
     */
    public static function emiteSiPersisteaza(Comanda $comanda): array
    {
        $furnizor = self::activ();
        $rezultat = $furnizor->emiteFactura($comanda);

        $comanda->update([
            'invoice_generated' => true,
            'factura_serie' => $rezultat['serie'],
            'factura_numar' => $rezultat['numar'],
            'factura_link' => $rezultat['link'],
            'factura_furnizor' => $furnizor->cod(),
        ]);

        return $rezultat;
    }

    /**
     * @param  array<string, mixed>  $setari
     */
    private static function instanta(string $cod, array $setari): FurnizorFacturareInterface
    {
        $clase = self::furnizoriDisponibili();
        if (! isset($clase[$cod])) {
            throw new FacturareException("Furnizor necunoscut: {$cod}.");
        }
        return new $clase[$cod]($setari);
    }
}
