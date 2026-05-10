<?php

namespace App\Http\Controllers;

use App\Models\SetariPlatforma;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Faza 6.8 — Endpoint-uri publice pentru cron jobs din cPanel.
 *
 * URL pattern: GET /cron/{token}/{job}
 *   - {token} = UUID din setari_platforma.cron_token (regenerabil din UI)
 *   - {job}   = 'igienizari-zilnice' | 'mentenanta-verifica'
 *
 * Securitate:
 *   - Validare token cu hash_equals() (constant-time, evita timing attacks)
 *   - 404 pe token invalid (anti-enumerare — atacatorul nu stie daca ruta exista)
 *   - Throttle 60/min per IP (in routes/web.php) — anti spam/DoS
 *   - Audit log in channel 'cron' (IP + timestamp + status)
 *
 * Return text/plain — cPanel cron foloseste doar status code (200 = OK,
 * 4xx/5xx = log eroare). Body util doar pentru debug manual.
 */
class CronController extends Controller
{
    /**
     * Cron zilnic igienizari dozatoare BIDOANE.
     */
    public function igienizariZilnice(Request $request, string $token): Response
    {
        $this->validateazaToken($token, 'igienizari-zilnice', $request);

        $exitCode = Artisan::call('igienizari:zilnice');
        $output = Artisan::output();

        Log::channel('cron')->info('[cron] igienizari-zilnice OK', [
            'ip' => $request->ip(),
            'exit_code' => $exitCode,
        ]);

        return response("OK\n\n" . $output, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * Cron zilnic mentenanta filtre dozatoare.
     */
    public function mentenantaVerifica(Request $request, string $token): Response
    {
        $this->validateazaToken($token, 'mentenanta-verifica', $request);

        $exitCode = Artisan::call('mentenanta:verifica');
        $output = Artisan::output();

        Log::channel('cron')->info('[cron] mentenanta-verifica OK', [
            'ip' => $request->ip(),
            'exit_code' => $exitCode,
        ]);

        return response("OK\n\n" . $output, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * Validare token cu hash_equals (constant-time). Pe mismatch, log warning
     * + arunca 404 (anti-enumerare; atacatorul nu vede diferenta intre „ruta
     * inexistenta" si „token gresit").
     */
    private function validateazaToken(string $tokenPrimit, string $job, Request $request): void
    {
        $tokenAsteptat = SetariPlatforma::obtineCronToken();

        if (! hash_equals($tokenAsteptat, $tokenPrimit)) {
            Log::channel('cron')->warning('[cron] token invalid', [
                'ip' => $request->ip(),
                'job' => $job,
                'user_agent' => $request->userAgent(),
            ]);
            throw new NotFoundHttpException();
        }
    }
}
