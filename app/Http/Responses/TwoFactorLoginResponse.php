<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * Redirect dopo l'inserimento del codice 2FA. Senza questo binding, Fortify usa
 * config('fortify.home') = /dashboard, che manda il system_admin su un 403.
 */
class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    use ResolvesHomeRedirect;

    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        return $this->homeRedirect($request);
    }
}
