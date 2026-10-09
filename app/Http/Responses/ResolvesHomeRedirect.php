<?php

namespace App\Http\Responses;

/**
 * Redirect post-login coerente col ruolo. Condiviso tra il login normale
 * (LoginResponse) e quello con 2FA (TwoFactorLoginResponse), così i due
 * percorsi non divergono mai.
 */
trait ResolvesHomeRedirect
{
    private const PROFESSIONAL_ROLES = ['admin', 'psicologo', 'nutrizionista', 'osteopata', 'collaboratore'];

    private function homeRedirect($request)
    {
        $user = $request->user();

        // Amministratore di sistema: area gestione utenti (nessun accesso clinico).
        // Ignoriamo un eventuale URL "intended" (es. /dashboard, protetto da
        // require.clinical): seguirlo dopo il login lo manderebbe su un 403.
        if ($user->hasRole('system_admin')) {
            $request->session()->forget('url.intended');

            return redirect('/utenti');
        }

        if ($user->hasAnyRole(self::PROFESSIONAL_ROLES)) {
            return redirect()->intended('/dashboard');
        }

        // Ruolo paziente o nessun ruolo → area personale del paziente.
        return redirect()->intended('/mia-area');
    }
}
