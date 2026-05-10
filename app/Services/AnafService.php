<?php

namespace App\Services;

use App\Support\CifValidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Faza 6.6 — Integrare ANAF pentru completare automata date PJ din CIF.
 *
 * Endpoint: POST https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva
 * Body: [{"cui": int, "data": "YYYY-MM-DD"}]
 *
 * Cache 24h via Cache::remember pentru a evita request-uri redundante
 * (datele firmei nu se schimba des — denumire, adresa, etc.). Cheia cache:
 * `anaf:{cif_normalizat}`.
 *
 * Validare CIF prin CifValidator inainte de apel — daca invalid, returnam null
 * fara request HTTP (evita rate-limit ANAF + raspuns instant pentru typo-uri).
 *
 * Returneaza array structurat sau null la esec.
 */
class AnafService
{
    private const URL = 'https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva';
    private const CACHE_TTL_SECONDE = 86400; // 24 ore
    private const TIMEOUT_SECONDE = 10;

    /**
     * Cauta date firma dupa CIF. Returneaza array structurat:
     * - denumire, cif, reg_com, oras, strada, nr, sector, judet, tva_la_incasare
     * Sau null daca: CIF invalid, firma negasita, ANAF down.
     */
    public function cautaCif(string $cif): ?array
    {
        if (! CifValidator::esteValid($cif)) {
            return null;
        }

        $cifNormalizat = CifValidator::normalizeaza($cif);
        $cheieCache = "anaf:{$cifNormalizat}";

        return Cache::remember($cheieCache, self::CACHE_TTL_SECONDE, function () use ($cifNormalizat) {
            return $this->fetchDinAnaf($cifNormalizat);
        });
    }

    /**
     * Apel HTTP efectiv. Separat de cautaCif() pentru a permite cache wrapping.
     */
    private function fetchDinAnaf(string $cifNormalizat): ?array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDE)
                ->acceptJson()
                ->asJson()
                ->post(self::URL, [[
                    'cui' => (int) $cifNormalizat,
                    'data' => now()->toDateString(),
                ]]);

            if (! $response->successful()) {
                Log::warning('[ANAF] HTTP non-200', [
                    'cif' => $cifNormalizat,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);
                return null;
            }

            $json = $response->json();

            // Format ANAF v9: { "found": [...], "notFound": [...] }
            // Daca CIF-ul nu exista, found[] e gol si notFound contine input-ul.
            $found = $json['found'] ?? [];
            if (empty($found)) {
                return null;
            }

            return $this->parseazaRaspuns($found[0]);
        } catch (Throwable $e) {
            Log::error('[ANAF] exception', [
                'cif' => $cifNormalizat,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Mapeaza response-ul ANAF la structura noastra. Format ANAF API v9:
     *   - date_generale.{denumire, cui, nrRegCom, adresa}
     *   - inregistrare_scop_Tva.scpTVA (boolean — platitor TVA?)
     *   - inregistrare_RTVAI.statusTvaIncasare
     *   - adresa_sediu_social.{sdenumire_Strada, snumar_Strada, scod_Localitate (1-6=sector Bucuresti),
     *                          sdenumire_Localitate, sdenumire_Judet, sdetalii_Adresa, scod_Postal}
     *
     * Pentru Bucuresti, judetul e „MUNICIPIUL BUCUREŞTI" si sectorul vine din
     * scod_Localitate (cifra 1-6) — il extragem ca string.
     */
    private function parseazaRaspuns(array $r): array
    {
        $dateGen = $r['date_generale'] ?? [];
        $sediu = $r['adresa_sediu_social'] ?? [];

        $judet = trim((string) ($sediu['sdenumire_Judet'] ?? ''));
        $localitate = trim((string) ($sediu['sdenumire_Localitate'] ?? ''));

        // Pentru Bucuresti, scod_Localitate e cifra sectorului (1-6)
        $sector = '';
        if (stripos($judet, 'BUCURE') !== false) {
            $codLoc = (string) ($sediu['scod_Localitate'] ?? '');
            if (preg_match('/^[1-6]$/', $codLoc)) {
                $sector = $codLoc;
            }
        }

        return [
            'denumire' => trim((string) ($dateGen['denumire'] ?? '')),
            'cif' => (string) ($dateGen['cui'] ?? ''),
            'reg_com' => trim((string) ($dateGen['nrRegCom'] ?? '')),
            'oras' => $localitate,
            'strada' => $this->extrageStrada($sediu['sdenumire_Strada'] ?? ''),
            'nr' => trim((string) ($sediu['snumar_Strada'] ?? '')),
            'sector' => $sector,
            'judet' => $judet,
            'tva' => (bool) ($r['inregistrare_scop_Tva']['scpTVA'] ?? false),
            'tva_la_incasare' => (bool) ($r['inregistrare_RTVAI']['statusTvaIncasare'] ?? false),
        ];
    }

    /**
     * ANAF returneaza adesea strada cu prefix "Str." sau "STR." — il scoatem
     * pentru consistent (UI-ul nostru asteapta nume curat).
     */
    private function extrageStrada(string $strada): string
    {
        $strada = trim($strada);
        // Sterge prefix variants: "Str. ", "STR. ", "Strada ", etc.
        return preg_replace('/^(str\.|strada|str)\s+/i', '', $strada) ?? $strada;
    }
}
