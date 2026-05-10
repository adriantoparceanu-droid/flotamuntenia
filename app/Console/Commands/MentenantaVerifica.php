<?php

namespace App\Console\Commands;

use App\Models\DozatorFiltre;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Faza 4.3 — Stub pentru cron-ul `mentenanta:verifica` (vezi DOCUMENTATION.md §3.13).
 *
 * Logheaza in Laravel Log dozatoarele cu filtre care au scadenta in 30 si 15 zile.
 * Admin trimite reminder-ul MANUAL din UI (`/dozatoare?tip=filtre`) — comanda asta
 * doar listeaza candidatele (regula §8.6: notificarile NU se trimit automat).
 *
 * NU se inregistreaza inca in `routes/console.php` schedule — infrastructura cron
 * (token UUID, ruta /cron/*) se livreaza in Faza 6.8. Pana atunci, se ruleaza manual:
 *   php artisan mentenanta:verifica
 */
class MentenantaVerifica extends Command
{
    protected $signature = 'mentenanta:verifica';

    protected $description = 'Listeaza in log dozatoarele cu filtre cu scadenta de mentenanta in 30 sau 15 zile (Faza 4.3).';

    public function handle(): int
    {
        $azi = now()->startOfDay();
        $cap30 = $azi->copy()->addDays(30)->toDateString();
        $cap15 = $azi->copy()->addDays(15)->toDateString();
        $aziStr = $azi->toDateString();

        // Cele cu scadenta intre 16 si 30 zile (exclusiv 0-15 — alea sunt urgente).
        $scadente30 = DozatorFiltre::with(['client', 'adresa'])
            ->where('status', DozatorFiltre::STATUS_ACTIV)
            ->whereNotNull('data_urmatoare_mentenanta')
            ->whereBetween('data_urmatoare_mentenanta', [
                $azi->copy()->addDays(16)->toDateString(),
                $cap30,
            ])
            ->get();

        // Urgente: 0-15 zile sau expirate.
        $scadente15 = DozatorFiltre::with(['client', 'adresa'])
            ->where('status', DozatorFiltre::STATUS_ACTIV)
            ->whereNotNull('data_urmatoare_mentenanta')
            ->where('data_urmatoare_mentenanta', '<=', $cap15)
            ->where('data_urmatoare_mentenanta', '>=', $aziStr)
            ->get();

        $expirate = DozatorFiltre::with(['client', 'adresa'])
            ->where('status', DozatorFiltre::STATUS_ACTIV)
            ->whereNotNull('data_urmatoare_mentenanta')
            ->where('data_urmatoare_mentenanta', '<', $aziStr)
            ->get();

        $this->info('Dozatoare filtre cu scadenta mentenanta:');
        $this->line("  - Scadente 30 zile (16-30): {$scadente30->count()}");
        $this->line("  - Scadente 15 zile (urgente, 0-15): {$scadente15->count()}");
        $this->line("  - Expirate (depasite): {$expirate->count()}");

        foreach ($scadente30 as $d) {
            Log::info('[mentenanta:verifica] 30_zile', [
                'id' => $d->id,
                'client' => $d->client?->denumire,
                'adresa' => $d->adresa?->denumire,
                'serie' => $d->serie,
                'data_scadenta' => $d->data_urmatoare_mentenanta?->toDateString(),
            ]);
        }

        foreach ($scadente15 as $d) {
            Log::warning('[mentenanta:verifica] 15_zile', [
                'id' => $d->id,
                'client' => $d->client?->denumire,
                'adresa' => $d->adresa?->denumire,
                'serie' => $d->serie,
                'data_scadenta' => $d->data_urmatoare_mentenanta?->toDateString(),
            ]);
        }

        foreach ($expirate as $d) {
            Log::error('[mentenanta:verifica] expirat', [
                'id' => $d->id,
                'client' => $d->client?->denumire,
                'adresa' => $d->adresa?->denumire,
                'serie' => $d->serie,
                'data_scadenta' => $d->data_urmatoare_mentenanta?->toDateString(),
            ]);
        }

        return self::SUCCESS;
    }
}
