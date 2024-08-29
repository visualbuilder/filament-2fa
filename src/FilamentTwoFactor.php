<?php

namespace Optimacloud\Filament2fa;

use Closure;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Laragear\TwoFactor\Exceptions\InvalidCodeException;
use Laragear\TwoFactor\TwoFactor;

use function validator;

class FilamentTwoFactor extends TwoFactor
{
    /**
     * Creates a new Laraguard instance.
     */
    public function __construct(
        protected Repository $config,
        protected Request $request,
        protected string $input,
        protected string $code,
        protected string $safeDeviceInput,
    ) {
        //
    }

    /**
     * Check if the user uses TOTP and has a valid code.
     *
     * If the user does not use TOTP, no checks will be done.
     */
    public function validate(Authenticatable $user): bool
    {
        // If the user does not use 2FA or is not enabled, don't check.
        if (! $user instanceof TwoFactorAuthenticatable || ! $user->hasTwoFactorEnabled()) {
            return true;
        }

        // If safe devices are enabled, and this is a safe device, bypass.
        if ($this->isSafeDevicesEnabled() && $user->isSafeDevice($this->request)) {
            $user->setTwoFactorBypassedBySafeDevice(true);

            return true;
        }

        // If the code is valid, return true only after we try to save the safe device.
        if ($this->requestHasCode() && $user->validateTwoFactorCode($this->getCode())) {
            if ($this->isSafeDevicesEnabled() && $this->wantsToAddDevice()) {
                $user->addSafeDevice($this->request);
            }

            return true;
        }

        return false;
    }

    
    public function checkSafeDeviceLogin(Authenticatable $user)
    {
        // If safe devices are enabled, and this is a safe device, bypass.
        if ($this->isSafeDevicesEnabled() && $user->isSafeDevice($this->request)) {
            $user->setTwoFactorBypassedBySafeDevice(true);
            return true;
        } else {
            return false;
        }
    }

    public function validate2Fa(Authenticatable $user)
    {
        // If the code is valid, return true only after we try to save the safe device.
        if ($this->requestHasCode() && $user->validateTwoFactorCode($this->getCode())) {
            if ($this->isSafeDevicesEnabled() && $this->wantsToAddDevice()) {
                $user->addSafeDevice($this->request);
            }

            return true;
        }
    }


    /**
     * Checks if the Request has a Two-Factor Code and is valid.
     */
    protected function requestHasCode(): bool
    {
        return ! validator([$this->input => $this->code], [
            $this->input => 'required|alpha_num',
        ])->fails();
    }

    /**
     * Returns the code from the request input.
     */
    protected function getCode(): string
    {
        return $this->code;
    }

    /**
     * Checks if the user wants to add this device as "safe".
     */
    protected function wantsToAddDevice(): bool
    {
        return (bool)$this->safeDeviceInput;
    }
}
