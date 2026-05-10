<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Verifica daca utilizatorul autentificat are unul dintre rolurile permise.
     *
     * Utilizare in rute:
     *   ->middleware('rol:admin')
     *   ->middleware('rol:admin,gestiune')
     */
    public function handle(Request $request, Closure $next, string ...$roluri): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $tipuri = collect($roluri)->map(fn ($r) => $this->mapRolLaTip($r))->filter()->all();

        if (! in_array($user->tip, $tipuri, true)) {
            abort(403, 'Nu ai permisiunea sa accesezi aceasta pagina.');
        }

        return $next($request);
    }

    private function mapRolLaTip(string $rol): ?int
    {
        return match (strtolower($rol)) {
            'admin' => \App\Models\User::TIP_ADMIN,
            'client' => \App\Models\User::TIP_CLIENT,
            'sofer' => \App\Models\User::TIP_SOFER,
            'gestiune' => \App\Models\User::TIP_GESTIUNE,
            'superadmin' => \App\Models\User::TIP_SUPERADMIN,
            default => null,
        };
    }
}
