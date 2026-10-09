<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        // Policy robusta per dati sanitari: min 12 caratteri, maiuscole+minuscole,
        // numeri e simboli. (uncompromised() omesso: richiederebbe una chiamata
        // esterna a HaveIBeenPwned, non garantita sull'hosting.)
        return [
            'required', 'string', 'confirmed',
            Password::min(12)->mixedCase()->numbers()->symbols(),
        ];
    }
}
