<?php

namespace Optimacloud\Filament2fa\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Optimacloud\Filament2fa\TwoFactorAuthResponse;
use Optimacloud\Filament2fa\FilamentTwoFactor;
use Optimacloud\Filament2fa\Contracts\TwoFactorAuthenticatable;
use Illuminate\Validation\ValidationException;

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

        /** Login with Email & 2FA Recovery code */
        if (isset($data['login_with_recovery_code']) && $data['login_with_recovery_code']) {            
            $user = $this->getAuthModel()::whereEmail($data['email'])->first();       
            if (!app(FilamentTwoFactor::class, ['input' => 'code', 'code' => $data['code'], 'safeDeviceInput' => false])->validate2Fa($user)) {
                $this->throwRecoveryCodeValidationException('code');
            }
            Filament::auth()->login($user, $data['remember']);
            goto response;
        }

        /** Login with Email & Password */
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

    protected function throwRecoveryCodeValidationException($fieldName): never
    {
        throw ValidationException::withMessages([
            "data.$fieldName" => __('filament-2fa::two-factor.recovery_code.error_message')
        ]);
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent()
                            ->visible(fn(Get $get) => !$get('login_with_recovery_code')),
                        $this->getRecoveryCodeFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getRecoveryCodeFormComponent(): Component
    {
        return
            Group::make([
                Toggle::make('login_with_recovery_code')
                    ->label(__('filament-2fa::two-factor.recovery_code.toggle_recovery_login'))
                    ->live(),
                TextInput::make('code')
                    ->label(__('filament-2fa::two-factor.recovery_code.recovery_code'))
                    ->required()
                    ->autocomplete(false)
                    ->visible(fn(Get $get) => $get('login_with_recovery_code'))
            ]);
    }

    protected function getAuthModel()
    {
        $provider = config('auth.guards.'.Filament::getAuthGuard())['provider'];
        $modelName = config("auth.providers.$provider.model");
        return $modelName;
    }
}
