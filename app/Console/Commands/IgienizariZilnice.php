<?php

namespace App\Console\Commands;

use App\Models\Dozator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Faza 6.8 — Cron zilnic pentru igienizari dozatoare BIDOANE (DOCUMENTATION.md §3.13).
 *
 * Listeaza in log dozatoarele cu `perioada_igenizare` in fereastra azi+7 zile
 * (inclusiv expirate). Adminul trimite reminder-ele MANUAL din UI (`/dozatoare`)
 * — comanda asta doar listeaza candidatele (regula §8.6).
 *
 * Programare in cPanel: GET https://flotamuntenia.ro/cron/{token}/igienizari-zilnice
 * Schedule recomandat: zilnic la 07:00.
 *
 * Pentru rulare manuala (dev local):
 *   php artisan igienizari:zilnice
 */
class IgienizariZilnice extends Command
{
    protected $signature = 'igienizari:zilnice';

    protected $description = 'Listeaza in log dozatoarele BIDOANE cu igienizare scadenta in 7 zile (Faza 6.8).';

    public function handle(): int
    {
        $azi = now()->startOfDay();
        $aziStr = $azi->toDateString();
        $cap7Str = $azi->copy()->addDays(7)->toDateString();

        // Scadente in fereastra azi -> +7 zile (inclusiv azi)
        $scadente = Dozator::with(['client', 'adresa'])
            ->where('activ', true)
            ->whereNotNull('perioada_igenizare')
            ->whereBetween('perioada_igenizare', [$aziStr, $cap7Str])
            ->orderBy('perioada_igenizare')
            ->get();

        // Expirate (perioada_igenizare in trecut)
        $expirate = Dozator::with(['client', 'adresa'])
            ->where('activ', true)
            ->whereNotNull('perioada_igenizare')
            ->where('perioada_igenizare', '<', $aziStr)
            ->orderBy('perioada_igenizare')
            ->get();

        $this->info("Dozatoare bidoane igienizari:");
        $this->line("  - Scadente in 7 zile (azi inclusiv): {$scadente->count()}");
        $this->line("  - Expirate (depasite scadenta): {$expirate->count()}");

        foreach ($scadente as $d) {
            $zileRamase = (int) $azi->diffInDays($d->perioada_igenizare->startOfDay(), false);
            Log::channel('cron')->info('[igienizari:zilnice] scadent', [
                'id' => $d->id,
                'client' => $d->client?->denumire,
                'adresa' => $d->adresa?->denumire ?? $d->adresa?->adresaCompleta(),
                'serie' => $d->serie,
                'perioada_igenizare' => $d->perioada_igenizare->toDateString(),
                'zile_ramase' => $zileRamase,
            ]);
        }

        foreach ($expirate as $d) {
            $zileExpirat = (int) $d->perioada_igenizare->startOfDay()->diffInDays($azi, false);
            Log::channel('cron')->warning('[igienizari:zilnice] expirat', [
                'id' => $d->id,
                'client' => $d->client?->denumire,
                'adresa' => $d->adresa?->denumire ?? $d->adresa?->adresaCompleta(),
                'serie' => $d->serie,
                'perioada_igenizare' => $d->perioada_igenizare->toDateString(),
                'zile_expirat' => $zileExpirat,
            ]);
        }

        return self::SUCCESS;
    }
}
