<?php

namespace App\Services\Facturare;

use App\Models\Comanda;
use App\Models\FacturareSetari;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Faza 6.1 — Implementare facturare electronica via Oblio.eu.
 *
 * API reference: https://www.oblio.eu/api
 *   - Auth: POST https://www.oblio.eu/api/authorize/token (client_id, client_secret)
 *     → returneaza access_token (Bearer, TTL 3600s)
 *   - Emit factura: POST https://www.oblio.eu/api/docs/invoice
 *   - Rate limit: 30 req / 100s pe documente
 *
 * Setari asteptate (din FacturareSetari->setari, decriptate):
 *   - client_id (string): emailul contului Oblio (utilizator)
 *   - client_secret (string): API token din Settings > Account Data
 *   - cif (string): CIF emitent (ex: 'RO46043131')
 *   - seriesName (string): seria de facturi (ex: 'WF')
 *   - language (string, default 'RO')
 *   - currency (string, default 'RON')
 *   - dueDateOffsetDays (int, default 15): zile pana la termenul de plata
 *
 * Token cache: TTL efectiv 3000s (margine de 600s sub TTL-ul Oblio de 3600s).
 * Cheia de cache include hash-ul client_id pentru a permite reconfigurare.
 */
class OblioService implements FurnizorFacturareInterface
{
    private const URL_TOKEN = 'https://www.oblio.eu/api/authorize/token';
    private const URL_INVOICE = 'https://www.oblio.eu/api/docs/invoice';
    private const TOKEN_TTL_SECONDS = 3000; // 50 min, sub TTL-ul real (3600s)

    public function __construct(private readonly array $setari)
    {
    }

    public function cod(): string
    {
        return FacturareSetari::FURNIZOR_OBLIO;
    }

    public function eticheta(): string
    {
        return 'Oblio.eu';
    }

    public function testConexiune(): array
    {
        try {
            // Forteaza refetch token (sare peste cache) ca testul sa fie real.
            $this->stergeTokenCache();
            $token = $this->fetchToken();
            return ['ok' => true, 'mesaj' => 'Autentificare reusita. Token primit (TTL 1h).'];
        } catch (Throwable $e) {
            return ['ok' => false, 'mesaj' => $e->getMessage()];
        }
    }

    public function emiteFactura(Comanda $comanda): array
    {
        $comanda->loadMissing(['client', 'adresa', 'produse.produs.tva']);

        if (! $comanda->client) {
            throw new FacturareException('Comanda nu are un client asociat.');
        }
        if ($comanda->produse->isEmpty()) {
            throw new FacturareException('Comanda nu are linii de produs.');
        }

        $token = $this->fetchToken();
        $payload = $this->construiestePayload($comanda);

        try {
            $resp = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->post(self::URL_INVOICE, $payload);
        } catch (Throwable $e) {
            throw new FacturareException('Eroare retea la apelul Oblio: ' . $e->getMessage());
        }

        if (! $resp->successful()) {
            $msg = $resp->json('statusMessage') ?? $resp->body();
            throw new FacturareException('Oblio a refuzat factura (HTTP ' . $resp->status() . '): ' . $msg);
        }

        $data = $resp->json('data');
        if (! is_array($data) || empty($data['number'])) {
            throw new FacturareException('Raspuns invalid de la Oblio: lipseste data.number.');
        }

        return [
            'serie' => (string) ($data['seriesName'] ?? $this->setari['seriesName'] ?? ''),
            'numar' => (string) $data['number'],
            'link' => $data['link'] ?? null,
        ];
    }

    /**
     * Returneaza access token-ul din cache sau il fetch-uieste de la Oblio.
     */
    private function fetchToken(): string
    {
        $cacheKey = $this->cheieTokenCache();

        return Cache::remember($cacheKey, self::TOKEN_TTL_SECONDS, function () {
            $clientId = $this->setari['client_id'] ?? null;
            $clientSecret = $this->setari['client_secret'] ?? null;

            if (! $clientId || ! $clientSecret) {
                throw new FacturareException('Setarile Oblio sunt incomplete (lipseste client_id sau client_secret).');
            }

            try {
                $resp = Http::asJson()
                    ->acceptJson()
                    ->timeout(10)
                    ->post(self::URL_TOKEN, [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                    ]);
            } catch (Throwable $e) {
                throw new FacturareException('Eroare retea la autentificare Oblio: ' . $e->getMessage());
            }

            if (! $resp->successful()) {
                $msg = $resp->json('error_description') ?? $resp->json('error') ?? $resp->body();
                throw new FacturareException('Oblio a refuzat autentificarea (HTTP ' . $resp->status() . '): ' . $msg);
            }

            $token = $resp->json('access_token');
            if (! is_string($token) || $token === '') {
                throw new FacturareException('Raspuns invalid la autentificare Oblio (lipseste access_token).');
            }

            return $token;
        });
    }

    private function cheieTokenCache(): string
    {
        $clientId = $this->setari['client_id'] ?? '';
        return 'oblio_token_' . hash('sha256', $clientId);
    }

    public function stergeTokenCache(): void
    {
        Cache::forget($this->cheieTokenCache());
    }

    /**
     * Construieste payload-ul JSON conform docs Oblio
     * (https://www.oblio.eu/api).
     */
    private function construiestePayload(Comanda $comanda): array
    {
        $client = $comanda->client;
        $offsetZile = (int) ($this->setari['dueDateOffsetDays'] ?? 15);

        $issueDate = $comanda->data_livrare?->format('Y-m-d') ?? now()->format('Y-m-d');
        $dueDate = $comanda->data_livrare
            ? $comanda->data_livrare->copy()->addDays($offsetZile)->format('Y-m-d')
            : now()->addDays($offsetZile)->format('Y-m-d');

        $isPlatitorTva = $this->estePlatitorTva($client->cif ?? '');

        $payload = [
            'cif' => $this->setari['cif'],
            'client' => [
                'name' => $client->denumire ?? '',
                // Oblio accepta CIF sau CNP in acelasi camp `cif`
                'cif' => $client->cif ?? '',
                'address' => $client->adresaCompleta() ?: '',
                'city' => $client->oras ?: '',
                'state' => $client->sector ?: '',
                'country' => 'Romania',
                'email' => $client->email ?: '',
                'phone' => $client->telefon ?: '',
                'vatPayer' => $isPlatitorTva ? 1 : 0,
                'save' => 0, // Nu salva clientul in baza Oblio (gestionam noi)
            ],
            'issueDate' => $issueDate,
            'dueDate' => $dueDate,
            'seriesName' => $this->setari['seriesName'],
            'language' => $this->setari['language'] ?? 'RO',
            'currency' => $this->setari['currency'] ?? 'RON',
            'products' => $this->construiesteProduse($comanda),
        ];

        return $payload;
    }

    /**
     * Mapeaza liniile comandă la formatul Oblio.
     *
     * @return list<array<string, mixed>>
     */
    private function construiesteProduse(Comanda $comanda): array
    {
        $produse = [];
        foreach ($comanda->produse as $linie) {
            $tva = (float) ($linie->produs?->tva?->valoare ?? 0);
            $produse[] = [
                'name' => $linie->produs?->denumire ?? 'Produs',
                'measuringUnit' => 'buc',
                'currency' => $this->setari['currency'] ?? 'RON',
                'quantity' => (int) $linie->cantitate,
                'price' => round((float) $linie->pret, 2),
                'vatPercentage' => $tva,
                'vatName' => $tva > 0 ? 'Normala' : 'Scutit',
                'vatIncluded' => false,
            ];
        }
        return $produse;
    }

    /**
     * Detecteaza daca un CIF/CNP romanesc semnaleaza platitor de TVA.
     * Conventia RO: prefixul 'RO' inainte de numar = platitor TVA.
     */
    private function estePlatitorTva(string $cif): bool
    {
        return stripos(trim($cif), 'RO') === 0;
    }
}
