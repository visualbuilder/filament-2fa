<?php

namespace Visualbuilder\Filament2fa\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as ContractsLoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;
use Visualbuilder\Filament2fa\Contracts\TwoFactorAuthenticatable;
use Visualbuilder\Filament2fa\TwoFactorAuthResponse;

class Login extends BaseLogin
{
    public function authenticate(): ?ContractsLoginResponse
    {
        // --- Rate limiting (same semantics as core) ---
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            // match V4’s validation path
            $this->addError('data.email', __('auth.throttle', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]));
            return null;
        }

        $data = $this->form->getState();

        /** @var SessionGuard $auth */
        $auth = Filament::auth();
        $credentials = $this->getCredentialsFromFormData($data);

        // Manual “pre-lookup” like core does, so we can raise Failed event consistently
        $provider = $auth->getProvider(); /** @phpstan-ignore-line */
        $candidate = $provider->retrieveByCredentials($credentials);

        if ((! $candidate) || (! $provider->validateCredentials($candidate, $credentials))) {
            $this->fireFailedEvent($auth, $candidate, $credentials);
            $this->throwFailureValidationException();
        }

        // Authenticate (we’ll still do post-checks and potentially log out)
        if (! $auth->attempt($credentials, $data['remember'] ?? false)) {
            $this->fireFailedEvent($auth, $candidate, $credentials);
            $this->throwFailureValidationException();
        }

        /** @var Authenticatable $user */
        $user = $auth->user();

        // Panel access parity with core
        if ($user instanceof FilamentUser) {
            if (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
                $auth->logout();
                $this->throwFailureValidationException();
            }
        }

        // ---- Your package’s 2FA decision point ----
        if ($this->needsTwoFactorAuthentication($user)) {
            $this->storeCredentials($credentials, (bool) ($data['remember'] ?? false));
            $auth->logout();
            session()->regenerate(); // prevent fixation
            return app(TwoFactorAuthResponse::class);
        }

        session()->regenerate();
        return app(ContractsLoginResponse::class);
    }

    /**
     * Decide whether to require your package’s 2FA.
     */
    protected function needsTwoFactorAuthentication(Authenticatable $user): bool
    {
        $guard = Filament::getAuthGuard();

        return $user instanceof TwoFactorAuthenticatable
            && array_key_exists($guard, config('filament-2fa.auth_guards', []))
            && (bool) config("filament-2fa.auth_guards.$guard.enabled")
            && $user->hasTwoFactorEnabled()
            && ! $user->isSafeDevice(request());
    }

    /**
     * Save credentials (encrypted) for the follow-up 2FA challenge.
     * We also remember the panel id so the response can route correctly.
     */
    protected function storeCredentials(array $credentials, bool $remember): void
    {
        $encrypted = array_map(
            static fn ($v) => Crypt::encryptString((string) $v),
            $credentials
        );

        $payload = [
            'credentials' => $encrypted,
            'remember'    => $remember,
            'panel_id'    => Filament::getCurrentOrDefaultPanel()->getId(),
        ];

        $key = config('filament-2fa.login.credential_key', 'vb_2fa_login');
        session([$key => $payload]);
    }

    /**
     * Emit the same Failed event shape core uses.
     * @param array<string,mixed> $credentials
     */
    protected function fireFailedEvent(Guard $guard, ?Authenticatable $user, #[SensitiveParameter] array $credentials): void
    {
        event(app(\Illuminate\Auth\Events\Failed::class, [
            'guard' => property_exists($guard, 'name') ? $guard->name : '',
            'user' => $user,
            'credentials' => $credentials,
        ]));
    }

    /**
     * Match core’s validation errors path for consistency.
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
