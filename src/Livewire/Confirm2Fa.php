<?php

namespace Visualbuilder\Filament2fa\Livewire;

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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Visualbuilder\Filament2fa\FilamentTwoFactor;

class Confirm2Fa extends SimplePage implements HasForms
{
    use InteractsWithForms, InteractsWithFormActions;

    public static ?string $title = 'Confirm your 2FA code';

    protected static string $view = 'filament-2fa::livewire.confirm2-fa';

    public bool $safe_device_enable = true;

    public string $totp_code;

    public static function getSort(): int
    {
        return static::$sort ?? -1;
    }

    public static function canView()
    {
        return false;
    }

    public function mount()
    {
        [$credentials,$panelId, $remember] = $this->getFlashedData();
        if (!$credentials || !$panelId) {
            return redirect(Filament::getLoginUrl());
        }
        // Initialize the form with default values
        $this->form->fill([
            'totp_code' => '',
            'safe_device_enable' => false,
        ]);
    }

    /**
     * Retrieve the flashed credentials in the session, and merges with the new on top.
     *
     * @param  array{credentials:array, remember:bool}  $credentials
     */
    protected function getFlashedData(): array
    {
        $sessionKey = config('filament-2fa.login.credential_key');
        $credentials = session("$sessionKey.credentials", []);
        $remember = session("$sessionKey.remember", false);
        $panelId = session("$sessionKey.panel_id");

        foreach ($credentials as $index => $value) {
            $credentials[$index] = Crypt::decryptString($value);
        }

        return [$credentials, $panelId,$remember ];
    }

    public function submit(): void
    {
        $formData = $this->form->getState();

        $user = $this->authenticate();

        if (!$user) {
            $this->redirect(Filament::getUrl());
        } else {
            if (app(FilamentTwoFactor::class,
                [
                    'code'            => $formData['totp_code'],
                    'safeDeviceInput' => isset($formData['safe_device_enable']) ? $formData['safe_device_enable'] : false
                ])->validate2Fa($user)) {
                $sessionKey = config('filament-2fa.login.credential_key', '_2fa_login');

                Notification::make()
                    ->title('Success')
                    ->body(__('filament-2fa::two-factor.success'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->send();

                session()->forget("{$sessionKey}.credentials");
                session()->forget("{$sessionKey}.remember");
                session()->forget("{$sessionKey}.panel_id");

                $this->redirectIntended(Filament::getUrl());
            } else {
                Filament::auth()->logout();
                session()->regenerate();
                $this->throwTotpcodeValidationException();
            }
        }
    }

    public function authenticate(): null|bool|Model
    {
        [$credentials, $panelId, $remember] = $this->getFlashedData();

        $panel = Filament::getPanel($panelId);
        Filament::setCurrentPanel($panel);

        if (!Filament::auth()->attempt($credentials, $remember)) {
            return false;
        }

        $user = Filament::auth()->user();

        if (!$user instanceof Model) {
            throw new Exception('The authenticated user object must be an Eloquent model to login.');
        }

        return $user;
    }

    protected function throwTotpcodeValidationException(): never
    {
        throw ValidationException::withMessages([
            'totp_code' => __('filament-2fa::two-factor.fail_2fa'),
        ]);
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
                    ->autofocus()
                    ->minLength(config('two-factor.totp.digits'))
                    ->maxLength(8)
                    ->required()
                    ->autocomplete(false)
                    ->extraInputAttributes(['class'=>'text-center','style'=>'font-size:2.6em; letter-spacing:1rem'])
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        if (strlen($state) === config('two-factor.totp.digits')) {
                            $this->submit();
                        }
                    }),
                Toggle::make('safe_device_enable')
                    ->label(__('filament-2fa::two-factor.enable_safe_device',['days' => config('two-factor.safe_devices.expiration_days')]))
                    ->hintIcon('heroicon-o-information-circle',__('filament-2fa::two-factor.safe_device_hint'))
                    ->hintColor('info')
                    ->inline()
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-mark')
                    ->default(true)
                    ->visible(config('two-factor.safe_devices.enabled'))
            ]);
    }
}
