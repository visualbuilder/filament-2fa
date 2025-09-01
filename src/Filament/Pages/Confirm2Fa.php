<?php
namespace Visualbuilder\Filament2fa\Filament\Pages;

use Exception;
use Filament\Facades\Filament;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasRoutes;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Panel\Concerns\HasNavigation;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Visualbuilder\Filament2fa\FilamentTwoFactor;

class Confirm2Fa extends SimplePage
{
    use InteractsWithForms, InteractsWithFormActions;
    use HasRoutes;

    public static ?string $title = 'Confirm your 2FA code';

    protected string $view = 'filament-2fa::livewire.confirm2-fa';

    public ?array $data = [];

    public bool $safe_device_enable = false;

    public string $totp_code;

    public bool $isSubmitting = false;

    /** Re-run verify if user edited the code mid-flight */
    public bool $shouldResubmit = false;

    /** Snapshot of the code we’re currently verifying */
    public ?string $processingCode = null;

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
        [$credentials, $panelId, $remember] = $this->getFlashedData();
        if (! $credentials || ! $panelId) {
            return redirect(Filament::getLoginUrl());
        }
        // Initialize the form with default values
        $this->form->fill([
            'totp_code' => '',
            'safe_device_enable' => false,
        ]);
    }

    public static function registerNavigationItems(): void
    {
        return;
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

        return [$credentials, $panelId, $remember];
    }

    public function submit(): void
    {
        if ($this->isSubmitting) {
            $this->shouldResubmit = true;
            return;
        }

        $this->isSubmitting = true;

        try {
            $this->form->validate();

            $user = $this->authenticate();

            if (! $user) {
                $this->redirect(Filament::getUrl());
                return;
            }

            $state = $this->form->getState();
            $code = (string) ($state['totp_code'] ?? '');
            $this->processingCode = $code;

            request()->merge([
                'totp_code' => $code,
                'safe_device_enable' => (bool) ($state['safe_device_enable'] ?? false),
            ]);

            $twoFactorValid = app(\Visualbuilder\Filament2fa\FilamentTwoFactor::class, [
                'input' => 'totp_code',
                'safeDeviceInput' => 'safe_device_enable',
            ])->validate($user);

            if ($twoFactorValid) {
                $sessionKey = config('filament-2fa.login.credential_key', '_2fa_login');

                \Filament\Notifications\Notification::make()
                    ->title('Success')
                    ->body(__('filament-2fa::two-factor.success'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->send();

                session()->forget("{$sessionKey}.credentials");
                session()->forget("{$sessionKey}.remember");
                session()->forget("{$sessionKey}.panel_id");

                $this->redirectIntended(\Filament\Facades\Filament::getUrl());
                return;
            }

            \Filament\Notifications\Notification::make()
                ->title('Invalid Code')
                ->body(__('filament-2fa::two-factor.fail_2fa'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->send();

        } finally {
            $this->isSubmitting = false;

            // If user changed the code during verify, re-run; otherwise emit "finished"
            if ($this->shouldResubmit) {
                $this->shouldResubmit = false;

                $latest = (string) ($this->form->getState()['totp_code'] ?? '');
                if ($latest !== ($this->processingCode ?? '')) {
                    $totpDigits  = (int) config('two-factor.totp.digits', 6);
                    $recoveryLen = (int) config('two-factor.recovery.length', 8);
                    $len = strlen($latest);
                    $isTotp = $len === $totpDigits && ctype_digit($latest);
                    $isRecovery = $len === $recoveryLen && preg_match('/[a-zA-Z]/', $latest);

                    if ($isTotp || $isRecovery) {
                        $this->submit();
                        return; // keep spinner on; next cycle will emit its own finished
                    }
                }
            }

            // Tell the front-end to hide the spinner
            $this->dispatch('twofa-finished');
        }
    }


    public function authenticate(): null|bool|Model
    {
        [$credentials, $panelId, $remember] = $this->getFlashedData();

        $panel = Filament::getPanel($panelId);
        Filament::setCurrentPanel($panel);

        if (! Filament::auth()->attempt($credentials, $remember)) {
            return false;
        }

        $user = Filament::auth()->user();

        if (! $user instanceof Model) {
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

    protected function get2FaFormComponent(): Group
    {
        return Group::make([
            ViewField::make('hint')
                ->label('')
                ->view('filament-2fa::forms.components.hint'),

            TextInput::make('totp_code')
                ->label(__('filament-2fa::two-factor.totp_or_recovery_code'))
                ->autofocus()
                ->minLength((int) config('two-factor.totp.digits', 6))
                ->maxLength((int) config('two-factor.recovery.length', 8))
                ->required()
                ->autocomplete(false)
                ->extraInputAttributes([
                    'class' => 'text-center',
                    'style' => 'font-size:2.6em; letter-spacing:1rem',
                    'x-on:input.debounce.150ms' => 'handleOtpInput($event.target.value)',
                ])
                ->live(),

//            TextInput::make('totp_code')
//                ->label(__('filament-2fa::two-factor.totp_or_recovery_code'))
//                ->autofocus()
//                ->minLength((int) config('two-factor.totp.digits', 6))
//                ->maxLength((int) config('two-factor.recovery.length', 8))
//                ->required()
//                ->autocomplete(false)
//                ->extraInputAttributes([
//                    'class' => 'text-center',
//                    'style' => 'font-size:2.6em; letter-spacing:1rem',
//                ])
//                ->live()
//                ->afterStateUpdated(function (string $state) {
//                    $totpDigits  = (int) config('two-factor.totp.digits', 6);
//                    $recoveryLen = (int) config('two-factor.recovery.length', 8);
//
//                    $len        = strlen($state);
//                    $isTotp     = $len === $totpDigits && ctype_digit($state);
//                    $isRecovery = $len === $recoveryLen && preg_match('/[a-zA-Z]/', $state);
//
//                    if (! ($isTotp || $isRecovery)) {
//                        return; // not “complete” yet
//                    }
//
//                    if ($this->isSubmitting) {
//                        // A verify is already running; schedule a re-run after it finishes.
//                        $this->shouldResubmit = true;
//                        return;
//                    }
//
//                    $this->submit();
//                }),

            Toggle::make('safe_device_enable')
                ->label(__('filament-2fa::two-factor.enable_safe_device', [
                    'days' => config('two-factor.safe_devices.expiration_days'),
                ]))
                ->hintIcon('heroicon-o-information-circle', __('filament-2fa::two-factor.safe_device_hint'))
                ->hintColor('info')
                ->inline()
                ->onColor('success')
                ->offColor('danger')
                ->onIcon('heroicon-m-check-circle')
                ->offIcon('heroicon-m-x-mark')
                ->default(false)
                ->visible((bool) config('two-factor.safe_devices.enabled')),
        ]);
    }
}
