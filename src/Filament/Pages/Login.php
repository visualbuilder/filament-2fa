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
use Optimacloud\Filament2fa\FilamentTwoFactor;
use Illuminate\Validation\ValidationException;
use Optimacloud\Filament2fa\Contracts\TwoFactorAuthenticatable;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        
        if (isset($data['login_with_recovery_code']) && $data['login_with_recovery_code']) {
            /** Login with 2FA Recovery code */
            $user = $this->getAuthModel()::whereEmail($data['email'])->first();            
            if (!app(FilamentTwoFactor::class, ['input' => 'code', 'code' => $data['code'], 'safeDeviceInput' => true])->loginWithRecoveryCode($user)) {                
                $this->throwCodeValidationException('code');
            }
            Filament::auth()->login($user, $data['remember']);

        } else {
            if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
                $this->throwFailureValidationException();
            }
        }

        $user = Filament::auth()->user();

        /** Verify 2FA code on login page */
        if (isset($data['2fa_code']) && $data['2fa_code']) {
            if (!app(FilamentTwoFactor::class, ['input' => '2fa_code', 'code' => $data['2fa_code'], 'safeDeviceInput' => $data['safe_device_enable']])->validate($user)) {
                Filament::auth()->logout();
                $this->throwCodeValidationException('2fa_code');
            }
        }

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function throwCodeValidationException($fieldName): never
    {
        throw ValidationException::withMessages([
            "data.$fieldName" => 'TOTP code expired or invalid',
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
                        // $this->get2FaFormComponent(),
                        $this->getRecoveryCodeFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function get2FaFormComponent(): Component
    {
        return
            Group::make([
                TextInput::make('2fa_code')
                    ->label('One Time Pin')
                    ->required()
                    ->numeric()
                    ->minLength(6)
                    ->maxLength(6)
                    ->autocomplete(false),
                Checkbox::make('safe_device_enable')
            ]);
    }

    protected function getRecoveryCodeFormComponent(): Component
    {
        return
            Group::make([
                Toggle::make('login_with_recovery_code')
                    ->live(),
                TextInput::make('code')
                    ->label('Recovery Code')
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
