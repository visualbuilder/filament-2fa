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
        $this->handleRateLimiting();

        // Stop execution if there are validation errors
        if ($this->getErrorBag()->isNotEmpty()) {
            return null;
        }

        $data = $this->form->getState();
        $credentials = $this->getCredentialsFromFormData($data);
        $remember = $data['remember'] ?? false;

        if (! $this->attemptLogin($credentials, $remember)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if ($this->needsTwoFactorAuthentication($user)) {
            $this->storeCredentials($credentials, $remember);
            Filament::auth()->logout();
            // Regenerate session to prevent fixation
            session()->regenerate();
            return app(TwoFactorAuthResponse::class);
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
    protected function handleRateLimiting(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            // Use the available property to get the number of seconds
            $this->addError('email', __('auth.throttle', ['seconds' => $exception->secondsUntilAvailable]));
            // Stop further execution by returning early
            return;
        }
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
            $this->storeCredentials($credentials, $remember);
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
        $authGuard = Filament::getAuthGuard();
        return $user instanceof TwoFactorAuthenticatable
            && array_key_exists($authGuard, config('filament-2fa.auth_guards'))
            && config("filament-2fa.auth_guards.$authGuard.enabled")
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
     * Save credentials to session encrypted.
     * Flash not possible with Filament as Livewire call would clear them before they can be used
     */
    protected function storeCredentials(array $credentials, bool $remember): void
    {
        $encryptedCredentials = array_map(
            fn($value) => Crypt::encryptString($value),
            $credentials
        );

        $sessionData = [
            'credentials' => $encryptedCredentials,
            'remember' => $remember,
            'panel_id' => Filament::getCurrentPanel()->getId(),
        ];

        $credentialKey = config('filament-2fa.login.credential_key');

        session([$credentialKey => $sessionData]);
    }
}
