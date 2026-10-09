<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consente l'accesso solo ai ruoli con competenza clinica. L'amministratore di
 * sistema (system_admin) è volutamente escluso dai dati clinici dei pazienti.
 */
class RequireClinical
{
    private const CLINICAL_ROLES = ['admin', 'psicologo', 'nutrizionista', 'osteopata', 'collaboratore'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->hasAnyRole(self::CLINICAL_ROLES), 403, 'Area riservata al personale clinico.');

        return $next($request);
    }
}
