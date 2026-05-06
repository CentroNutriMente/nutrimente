<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireProfessional
{
    private const PROFESSIONAL_ROLES = ['admin', 'psicologo', 'nutrizionista', 'osteopata', 'collaboratore'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(self::PROFESSIONAL_ROLES)) {
            return redirect('/mia-area');
        }

        return $next($request);
    }
}
