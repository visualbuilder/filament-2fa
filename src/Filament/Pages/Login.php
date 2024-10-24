<?php

namespace Visualbuilder\Filament2fa\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Visualbuilder\Filament2fa\TwoFactorAuthResponse;
use Visualbuilder\Filament2fa\Contracts\TwoFactorAuthenticatable;
use Illuminate\Support\Facades\Crypt;

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
        if ($user instanceof TwoFactorAuthenticatable && $user->hasTwoFactorEnabled() && !$user->isSafeDevice(request())) {
            $responseClass = TwoFactorAuthResponse::class;
            $this->flashData($this->getCredentialsFromFormData($data), $data['remember'] ?? false);
            Filament::auth()->logout();
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

    /**
     * Flashes the credentials into the session, encrypted.
     */
    protected function flashData(array $credentials, bool $remember): void
    {
        foreach ($credentials as $key => $value) {
            $credentials[$key] = Crypt::encryptString($value);
        }

        if (config('filament-2fa.login.flashLoginCredentials')) {
            request()->session()->flash(config('filament-2fa.login.credential_key'), ['credentials' => $credentials, 'remember' => $remember]);
        } else {
            session([config('filament-2fa.login.credential_key') => ['credentials' => $credentials, 'remember' => $remember]]);
        }
    }
}
