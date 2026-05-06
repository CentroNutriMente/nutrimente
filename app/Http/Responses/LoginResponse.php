<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    private const PROFESSIONAL_ROLES = ['admin', 'psicologo', 'nutrizionista', 'osteopata', 'collaboratore'];

    public function toResponse($request)
    {
        $user = $request->user();

        if ($user->hasAnyRole(self::PROFESSIONAL_ROLES)) {
            return redirect()->intended('/dashboard');
        }

        // patient role or no role → patient portal
        return redirect()->intended('/mia-area');
    }
}
