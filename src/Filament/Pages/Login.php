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
use Illuminate\Contracts\Auth\Authenticatable;

class Login extends BaseLogin
{
    public function authenticate(): null|TwoFactorAuthResponse|LoginResponse
    {
        if ($response = $this->handleRateLimiting()) {
            return $response;
        }

        $data = $this->form->getState();
        $credentials = $this->getCredentialsFromFormData($data);
        $remember = $data['remember'] ?? false;

        if (! $this->attemptLogin($credentials, $remember)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if ($response = $this->handleTwoFactorAuthentication($user, $credentials, $remember)) {
            return $response;
        }

        if (! $this->userCanAccessPanel($user)) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    /**
     * Handle rate limiting for login attempts.
     */
    protected function handleRateLimiting(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        return null;
    }

    /**
     * Attempt to authenticate the user.
     */
    protected function attemptLogin(array $credentials, bool $remember): bool
    {
        return Filament::auth()->attempt($credentials, $remember);
    }

    /**
     * Handle two-factor authentication if required.
     */
    protected function handleTwoFactorAuthentication(Authenticatable $user, array $credentials, bool $remember): ?TwoFactorAuthResponse
    {
        if ($this->needsTwoFactorAuthentication($user)) {
            $this->flashCredentials($credentials, $remember);
            Filament::auth()->logout();

            return app(TwoFactorAuthResponse::class);
        }

        return null;
    }

    /**
     * Determine if two-factor authentication is required.
     */
    protected function needsTwoFactorAuthentication(Authenticatable $user): bool
    {
        return $user instanceof TwoFactorAuthenticatable
            && $user->hasTwoFactorEnabled()
            && ! $user->isSafeDevice(request());
    }

    /**
     * Check if the authenticated user can access the current panel.
     */
    protected function userCanAccessPanel(Authenticatable $user): bool
    {
        return ! ($user instanceof FilamentUser && ! $user->canAccessPanel(Filament::getCurrentPanel()));
    }

    /**
     * Flash the credentials into the session, encrypted.
     */
    protected function flashCredentials(array $credentials, bool $remember): void
    {
        $encryptedCredentials = array_map(
            fn($value) => Crypt::encryptString($value),
            $credentials
        );

        $sessionData = [
            'credentials' => $encryptedCredentials,
            'remember' => $remember,
        ];

        $credentialKey = config('filament-2fa.login.credential_key');

        if (config('filament-2fa.login.flashLoginCredentials')) {
            request()->session()->flash($credentialKey, $sessionData);
        } else {
            session([$credentialKey => $sessionData]);
        }
    }
}
