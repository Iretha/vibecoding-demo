<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Events\PasswordResetCompleted;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function reset(mixed $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();

        // Revoke all tokens for this user
        $user->tokens()->delete();

        event(new PasswordResetCompleted($user));
    }
}



