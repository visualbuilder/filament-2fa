<?php

namespace Optimacloud\Filament2fa\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Optimacloud\Filament2fa\TwoFactorAuthResponse;
use Optimacloud\Filament2fa\Contracts\TwoFactorAuthenticatable;

class Login extends BaseLogin
{
    public function authenticate(): null|TwoFactorAuthResponse|LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $responseClass = LoginResponse::class;

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        /** Check If user loggedin with unsafe device redirecting to 2fa verification page */
        if ($user instanceof TwoFactorAuthenticatable && $user->hasTwoFactorEnabled() && config('two-factor.safe_devices.enabled', false) && !$user->isSafeDevice(request())) {
            $responseClass = TwoFactorAuthResponse::class;
            goto response;
        }

        response:
        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app($responseClass);
    }
}
