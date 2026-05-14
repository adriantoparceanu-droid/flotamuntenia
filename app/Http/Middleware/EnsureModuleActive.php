<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware care verifică dacă un modul opțional este activ.
 *
 * Utilizare în rute:
 *   ->middleware('modul:portal_client')
 *   ->middleware('modul:comenzi_rapide')
 *
 * Argumentul primit este sufixul cheii (fără prefixul 'modul_').
 * Dacă modulul e inactiv, returnează view-ul 'modul-indisponibil' cu HTTP 403.
 * Datele din baza de date rămân intacte — dezactivarea e complet reversibilă.
 */
class EnsureModuleActive
{
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $cheie = 'modul_' . $slug;

        if (! ModuleService::isActive($cheie)) {
            return response()->view('modul-indisponibil', [
                'slug' => $slug,
                'definitii' => \App\Services\ModuleService::definitiiModule(),
                'cheie' => $cheie,
            ], 403);
        }

        return $next($request);
    }
}
