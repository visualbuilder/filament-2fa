<?php

namespace Visualbuilder\Filament2fa\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable as BaseTwoFactorAuthenticatable;
use Laragear\TwoFactor\Contracts\TwoFactorTotp;

interface TwoFactorAuthenticatable extends BaseTwoFactorAuthenticatable
{

    /**
     * Fetch and returns a new Shared Secret.
     */
    public function hasTwoFactor(): MorphOne;

    /**
     * Fetch and returns a new Shared Secret.
     */
    public function getTwoFactorAuth(): TwoFactorTotp;

    /**
     * Remove safe devices
     */
    public function forgetSafeDevices(): bool;

}
