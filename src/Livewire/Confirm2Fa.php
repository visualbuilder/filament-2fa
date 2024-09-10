<?php

namespace Optimacloud\Filament2fa\Livewire;

use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Crypt;
use Optimacloud\Filament2fa\FilamentTwoFactor;

class Confirm2Fa extends SimplePage implements HasForms
{
    use InteractsWithForms, InteractsWithFormActions;

    public static ?string $title = 'Confirm your 2FA code';

    protected static string $view = 'filament-2fa::livewire.confirm2-fa';

    public bool $safe_device_enable;

    public string $totp_code;

    public function hasTopbar(): bool
    {
        return false;
    }

    public static function getSort(): int
    {
        return static::$sort ?? -1;
    }

    public function mount()
    {
        [$credentials, $remember] = $this->getFlashedData();
        if(!$credentials) {
            return redirect(Filament::getLoginUrl());
        }
    }

    public function authenticate(): null|bool|Model
    {
        [$credentials, $remember] = $this->getFlashedData();
    
        if (! Filament::auth()->attempt($credentials, $remember) ) {
            return false;
        }

        $user = Filament::auth()->user();

        if (! $user instanceof Model) {
            throw new Exception('The authenticated user object must be an Eloquent model to allow the profile page to update it.');
        }

        return $user;
    }

    public static function canView()
    {
        return false;
    }

    protected function getFormSchema(): array
    {
        return [
            $this->get2FaFormComponent(),
        ];
    }

    protected function get2FaFormComponent(): Component
    {
        return
            Group::make([
                Placeholder::make('Hint')
                    ->label('')
                    ->content(__('filament-2fa::two-factor.confirm_otp_hint', ['otpLength' => config('two-factor.totp.digits'), 'recoveryLength' => config('two-factor.recovery.length')])),
                TextInput::make('totp_code')
                    ->label(__('filament-2fa::two-factor.totp_or_recovery_code'))
                    ->required()
                    ->autocomplete(false),
                Toggle::make('safe_device_enable')
                    ->label(__('filament-2fa::two-factor.enable_safe_device'))
                    ->inline(false)
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-mark')
                    ->visible(config('two-factor.safe_devices.enabled'))
            ]);
    }

    public function submit(): void
    {
        $formData = $this->form->getState();

        $user = $this->authenticate();

        if(!$user) {
            $this->redirect(Filament::getUrl());
        } else {
            if ($user && app(FilamentTwoFactor::class, ['input' => 'totp_code', 'code' => $formData['totp_code'], 'safeDeviceInput' => isset($formData['safe_device_enable']) ? $formData['safe_device_enable'] : false])->validate2Fa($user)) {
                Notification::make()
                    ->title('Success')
                    ->body(__('filament-2fa::two-factor.success'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->send();
                $this->redirect(Filament::getUrl());
            } else {
                Filament::auth()->logout();
                session()->regenerate();
                $this->throwTotpcodeValidationException();
            }
        }
    }


    /**
     * Retrieve the flashed credentials in the session, and merges with the new on top.
     *
     * @param  array{credentials:array, remember:bool}  $credentials
     */
    protected function getFlashedData(): array
    {
        $sessionKey = config('filament2fa.login.credential_key');
        $credentials = session("$sessionKey.credentials", []);
        $remember = session("$sessionKey.remember", false);

        foreach ($credentials as $index => $value) {
            $credentials[$index] = Crypt::decryptString($value);
        }

        return [$credentials, $remember];
    }



    protected function throwTotpcodeValidationException(): never
    {
        throw ValidationException::withMessages([
            'totp_code' => __('filament-2fa::two-factor.fail_2fa'),
        ]);
    }
}
