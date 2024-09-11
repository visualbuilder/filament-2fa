<?php

namespace Optimacloud\Filament2fa;

use Exception;
use Filament\Facades\Filament;
use Optimacloud\Filament2fa\Contracts\TwoFactorAuthenticatable;

class TwoFactorHelper
{

    public static function validateAuthModel()
    {
        $availableGuards = config('filament-2fa.auth_guards');
        $guardOptions = $availableGuards[Filament::getAuthGuard()];
        $modelClass = Filament::auth()->getProvider()->getModel();
        if($guardOptions['enabled'] && $guardOptions['mandatory'] && !new $modelClass instanceof TwoFactorAuthenticatable) {
            throw new Exception(TwoFactorAuthenticatable::class ." Not Implemented on auth model");
        }        
    }
}