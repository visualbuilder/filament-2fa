<?php

namespace Visualbuilder\Filament2fa\Filament\Pages;

use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Concerns\HasRoutes;
use Filament\Pages\SimplePage;
use Filament\Panel\Concerns\HasNavigation;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Actions as ActionsBar;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form as SchemaForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\UnorderedList;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Locked;
use Visualbuilder\Filament2fa\Contracts\TwoFactorAuthenticatable;

class Configure extends SimplePage
{
    use HasRoutes;
    use HasNavigation;

    protected Width | string | null $maxContentWidth = Width::FourExtraLarge;

    public static function getSlug(): string
    {
        return 'two-factor-authentication';
    }

    public static function registerNavigationItems(): void
    {
        if (! static::shouldRegisterNavigation()) {
            return;
        }

        Filament::getCurrentPanel()
            ->navigationItems(static::getNavigationItems());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('filament-2fa.navigation.visible_on_navbar', false);
    }
    
    public static function getNavigationLabel(): string
    {
        return (string) (config('filament-2fa.navigation.label') ?? 'Two-Factor Authentication');
    }

    public static function getNavigationIcon(): ?string
    {
        return (string) (config('filament-2fa.navigation.icon') ?? 'heroicon-o-shield-check');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-2fa.navigation.group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-2fa.navigation.sort_no');
    }

    public static function getCluster(): ?string
    {
        return config('filament-2fa.navigation.cluster');
    }

    public ?array $data = [];            // form state lives here (e.g. data.two_factor_code)
    public bool $showRecoveryCodes = false;

    #[Locked]
    public array $recoveryCodes = [];

    #[Locked]            // prevents client-side mutation and survives re-renders
    public ?array $provisioning = null;

    public function mount(): void
    {
        $user = $this->getUser();

        // If there is ALREADY a pending two-factor record, reuse it.
        // Only create if none exists yet. Do NOT gate this by "enabled".
        $record = $user->twoFactorAuth ?? $user->getTwoFactorAuth();

        if (! $record) {
            $record = $user->createTwoFactorAuth(); // create once
        }

        // Some versions of the underlying library expect a "label" attribute
        // on the TwoFactorAuth model to derive the issuer. When the label is
        // missing calling `toQr()` or `toUri()` would throw an exception.
        // Ensure the label is always present by defaulting to the application
        // name and the user's email (or their identifier if email is missing).
        $email = $user->email ?? $user->getAuthIdentifier();
        if (! $record->label) {
            $record->label = config('app.name') . ':' . $email;
        }

        // Ensure a secret exists before attempting to serialize it.
        // Some installations don't generate the shared secret until it is
        // explicitly requested, which would make `toString()` return null.
        if (! $record->shared_secret) {
            $record->shared_secret = static::generateBase32Secret();
        }

        $secret = $record->toString();

        if (! $record->exists || $record->isDirty('label') || $record->isDirty('shared_secret')) {
            $record->save();
        }

        $this->provisioning = [
            'qr'     => $record->toQr(),
            'uri'    => $record->toUri(),
            'secret' => $secret,
        ];

        // If you still want form state:
        $this->form->fill();
    }

    // If your view calls this, keep it idempotent:
    public function prepareTwoFactor(): array
    {
        return $this->provisioning ?? [];
    }

    protected static function generateBase32Secret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

    public function getTitle(): string|Htmlable
    {
        return __('Two-Factor Authentication');
    }

    /** Create the "$form" container & bind it to data.* */
    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    /** Define the inner form fields (the OTP input in this case). */
    public function form(Schema $schema): Schema
    {
        $digits = (int) config('two-factor.totp.digits', 6);

        return $schema->components([
            TextInput::make('two_factor_code')
                ->label(__('Enter the :n-digit code', ['n' => $digits]))
                ->required()
                ->minLength($digits)
                ->maxLength($digits)
                ->autocomplete(false)
                ->live()
                ->extraInputAttributes([
                    'inputmode'   => 'numeric',
                    'autocomplete'=> 'one-time-code',
                    'class'       => 'text-center text-3xl tracking-[0.6rem]',
                ])
                ->afterStateUpdated(function (?string $state) use ($digits) {
                    if (strlen((string) $state) === $digits) {
                        $this->confirmTwoFactor((string) $state);
                    }
                }),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            // SETUP (when not enabled)
            Section::make(__('Enable two-factor authentication'))
                ->columnSpanFull()
                ->schema([
                    Grid::make()->schema([
                        // Left: QR + instructions
                        Group::make()->schema([
                            Text::make(__('Step 1. Scan this QR code with your authenticator app.')),
                            // Your Blade pulls data via $this->prepareTwoFactor()
                            View::make('filament-2fa::forms.components.2fa-qrcode'),
                        ])->columnSpan(['md' => 1]),

                        Group::make()
                            ->extraAttributes([
                                // make the grid item fill the row height, then center its children
                                'class' => 'md:self-stretch md:h-full md:flex md:items-center'
                            ])
                            ->schema([
                                SchemaForm::make([EmbeddedSchema::make('form')])
                                    ->id('form')
                                    ->livewireSubmitHandler('confirmTwoFactorFromForm')
                                    ->footer([
                                        ActionsBar::make($this->getFormActions())
                                            ->alignment(Alignment::End)
                                            ->fullWidth(true),
                                    ]),
                            ])
                            ->columnSpan(['md' => 1]),
                    ])->columns(['md' => 2]),
                ])
                ->visible(! $this->getUser()->hasTwoFactorEnabled()),

            // MANAGE (when enabled)
            Section::make(__('Two-factor is enabled'))
                ->schema([
                    Text::make(fn() => __('Enabled on :date', [
                        'date' => optional($this->getUser()->twoFactorAuth?->enabled_at)
                            ?->format(config('filament-2fa.defaultDateTimeDisplayFormat')),
                    ]))->color('success'),

                    Text::make(__('Trusted devices'))->weight(\Filament\Support\Enums\FontWeight::Bold)
                        ->visible((bool) ($this->getUser()->twoFactorAuth?->safe_devices)),

                    UnorderedList::make(function (): array {
                        $devices = collect($this->getUser()->twoFactorAuth?->safe_devices ?? []);
                        return $devices->map(fn(array $d) => Text::make(
                            $d['ip'].' — '.Carbon::parse($d['added_at'])
                                ->format(config('filament-2fa.defaultDateTimeDisplayFormat'))
                        ))->all();
                    })->visible((bool) ($this->getUser()->twoFactorAuth?->safe_devices)),

                    ActionsBar::make([
                        Action::make('toggleRecovery')
                            ->color('success')
                            ->icon($this->showRecoveryCodes ? 'heroicon-m-eye-slash' : 'heroicon-m-eye')
                            ->label($this->showRecoveryCodes ? __('Hide recovery codes') : __('Show recovery codes'))
                            ->action(function () {
                                $this->showRecoveryCodes = ! $this->showRecoveryCodes;
                                if ($this->showRecoveryCodes && empty($this->recoveryCodes)) {
                                    $this->recoveryCodes = $this->getUser()->getRecoveryCodes()->values()->all();
                                }
                            }),

                        Action::make('generateRecovery')
                            ->visible($this->showRecoveryCodes)
                            ->icon('heroicon-m-key')
                            ->label(__('Generate new recovery codes'))
                            ->requiresConfirmation()
                            ->action(function () {
                                $this->recoveryCodes = $this->getUser()->generateRecoveryCodes()->values()->all();
                            }),

                        Action::make('forgetDevices')
                            ->label(__('Forget safe devices'))
                            ->color('warning')
                            ->requiresConfirmation()
                            ->icon('heroicon-m-shield-exclamation')
                            ->action(function () {
                                $this->getUser()->forgetSafeDevices();
                                $this->dispatch('refresh');
                            })->visible((bool) ($this->getUser()->twoFactorAuth?->safe_devices)),

                        Action::make('disable2fa')
                            ->label(__('Disable 2FA'))
                            ->color('danger')
                            ->requiresConfirmation()
                            ->icon('heroicon-m-shield-exclamation')
                            ->modalDescription(__('You will need to remove this account from your authenticator app.'))
                            ->action(function () {
                                $this->getUser()->disableTwoFactorAuth();
                                $this->showRecoveryCodes = false;
                                $this->recoveryCodes = [];
                                $this->dispatch('refresh');
                            }),
                    ])->alignment(Alignment::End),

                    Section::make()
                        ->schema([
                            Text::make(new HtmlString('<p>'.__('Store these codes somewhere safe, you can login once with each one if you don\'t have access to your phone').'</p>')),
                            UnorderedList::make(fn() => collect($this->recoveryCodes)->pluck('code')
                                ->map(fn($c) => Text::make($c)->fontFamily(\Filament\Support\Enums\FontFamily::Mono))
                                ->all()),
                        ])
                        ->compact()
                        ->secondary()
                        ->visible($this->showRecoveryCodes),
                ])
                ->visible($this->getUser()->hasTwoFactorEnabled()),
        ]);
    }

    protected function getFormActions(): array
    {
        return [$this->getConfirmFormAction()];
    }

    protected function getConfirmFormAction(): Action
    {
        return Action::make('confirm')->label(__('Confirm'))->submit('confirmTwoFactorFromForm');
    }

    public function confirmTwoFactorFromForm(): void
    {
        $code = (string) data_get($this->data, 'two_factor_code', '');
        $this->confirmTwoFactor($code);
    }

    /** Triggered by the auto-submit-on-length and by the button above. */
    public function confirmTwoFactor(string $code): void
    {
        $ok = $this->getUser()->confirmTwoFactorAuth($code);

        \Filament\Notifications\Notification::make()
            ->title($ok ? __('Two-factor enabled') : __('Invalid code'))
            ->{$ok ? 'success' : 'danger'}()
            ->send();

        $this->dispatch('refresh');
    }

    protected function getUser(): Authenticatable & Model
    {
        $user = Filament::auth()->user();
        if (! $user instanceof Model) {
            throw new Exception('Authenticated user must be an Eloquent model.');
        }
        return $user;
    }

}
