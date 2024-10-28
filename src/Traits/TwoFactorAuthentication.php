<?php

namespace Visualbuilder\Filament2fa\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Laragear\TwoFactor\Contracts\TwoFactorTotp;
use Laragear\TwoFactor\TwoFactorAuthentication as BaseTwoFactorAuthentication;
use Laragear\TwoFactor\Models\TwoFactorAuthentication as BaseModelTwoFactorAuthentication;


trait TwoFactorAuthentication
{
    use BaseTwoFactorAuthentication;

    public static function isTwoFactorMandatory(): bool
    {
        return true;
    }

    public function hasTwoFactor(): MorphOne
    {
        return $this->morphOne(BaseModelTwoFactorAuthentication::class, 'authenticatable');
    }

    /**
     * Fetch and returns a new Shared Secret.
     */
    public function getTwoFactorAuth(): TwoFactorTotp
    {
        return $this->twoFactorAuth;
    }

    public function forgetSafeDevices(): bool
    {
        return $this->twoFactorAuth->forceFill(['safe_devices' => []])->save();
    }
}
