<?php

namespace Visualbuilder\Filament2fa\Filament\Pages;

use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile;
use Filament\Pages\SubNavigationPosition;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Laragear\TwoFactor\Models\TwoFactorAuthentication;
use Visualbuilder\Filament2fa\Contracts\TwoFactorAuthenticatable;


class Configure extends EditProfile
{

    public static ?string $slug = 'two-factor-authentication';

    public ?string $maxWidth = '6xl';

    public Collection|array $recoveryCodes;

    public bool $showRecoveryCodes = false;

    public function __construct()
    {
        $this->recoveryCodes = $this->getUser()->hasTwoFactorEnabled() ? $this->getUser()->getRecoveryCodes() : [];
    }

    public function getUser(): Authenticatable & Model
    {
        $user = Filament::auth()->user();

        if (!$user instanceof Model || !$user instanceof TwoFactorAuthenticatable) {
            throw new Exception('The authenticated user must be an Eloquent model implementing TwoFactorAuthenticatable class.');
        }

        return $user;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-2fa.navigation.visible_on_navbar');
    }

    public static function getLabel(): string
    {
        return __('filament-2fa::two-factor.profile_label');
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-2fa.navigation.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-2fa.navigation.group');
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return config('filament-2fa.navigation.icon');
    }

    public static function getCluster(): ?string
    {
        return config('filament-2fa.navigation.cluster');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-2fa.navigation.sort_no');
    }

    public static function getRouteName(?string $panel = null): string
    {
        $panel = $panel ? Filament::getPanel($panel) : Filament::getCurrentPanel();
        return $panel->generateRouteName(static::getRelativeRouteName());
    }

    public static function getRelativeRouteName(): string
    {
        return self::$slug;
    }

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return config('filament-2fa.navigation.subnav_position');
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.simple';
    }

    public function getView(): string
    {
        return static::$view ?? 'filament-panels::pages.auth.edit-profile';
    }

    public function hasLogo(): bool
    {
        return true;
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }

    public function getCancelFormAction(): Action
    {
        return Action::make('back')
            ->label(__('filament-2fa::two-factor.back_to_dashboard'))
            ->url(Filament::getUrl())
            ->color('gray');
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label($this->getUser()->hasTwoFactorEnabled() ? __('filament-2fa::two-factor.save_changes') : __('filament-2fa::two-factor.action_label'))
            ->submit('save')
            ->visible(fn() => !$this->getUser()->hasTwoFactorEnabled())
            ->keyBindings(['mod+s']);
    }

    protected function afterSave(): void
    {
        if (isset($this->data['disable_two_factor_auth']) && $this->data['disable_two_factor_auth'] === true) {
            $this->getUser()->disableTwoFactorAuth();
            Notification::make()
                ->title(__('filament-2fa::two-factor.disabled'))
                ->success()
                ->send();
        }
        if (isset($this->data['2fa_code']) && $this->data['2fa_code'] !== null) {
            $activated = $this->getUser()->confirmTwoFactorAuth($this->data['2fa_code']);
            if ($activated) {
                Notification::make()
                    ->title(__('filament-2fa::two-factor.enabled'))
                    ->success()
                    ->send();
//                /**
//                 * Todo Redirect back to this page or refresh?
//                 */
//                $redirectUrl = self::$slug;
//                $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode() && is_app_url($redirectUrl));
            } else {
                Notification::make()
                    ->title(__('filament-2fa::two-factor.fail_confirm'))
                    ->danger()
                    ->send();
            }
        }
        $this->js('$wire.$refresh()');
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getTwoFactorAuthFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(!static::isSimple()),
            ),
        ];
    }

    protected function getTwoFactorAuthFormComponent(): Component
    {
        return Section::make(__('filament-2fa::two-factor.profile_title'))
            ->icon('heroicon-o-shield-check')
            ->iconColor('success')
            ->relationship('twoFactorAuth')
            ->schema([
                $this->enable2FactorAuthGroupComponent(),
                $this->manage2FactorAuthGroupComponent()
            ]);
    }

    protected function enable2FactorAuthGroupComponent(): Component
    {
        return Group::make()
            ->schema([
                Placeholder::make('2fa_info')
                    ->label(__('filament-2fa::two-factor.setup_title'))
                    ->content(new HtmlString('<p class="text-justify">'.__('filament-2fa::two-factor.setup_message_1', ['interval' => config('two-factor.totp.seconds')]).'</p>
                <p class="text-justify">'.__('filament-2fa::two-factor.setup_message_2').'</p>')),

                Group::make()
                    ->schema([
                        // Step 1 - Left Column
                        Group::make()
                            ->schema([
                                Placeholder::make('step1')
                                    ->label(false)
                                    ->content(fn() => new HtmlString('<h3 class="text-lg font-bold text-primary">'.__('filament-2fa::two-factor.setup_step_1').'</h3>')),
                                ViewField::make('2fa_auth')
                                    ->view('filament-2fa::forms.components.2fa-settings')
                                    ->viewData($this->prepareTwoFactor()),
                            ])
                            ->columnSpan([
                                'sm' => 2, // Full-width on small screens
                                'md' => 1, // Half-width on medium and larger screens
                            ]),

                        // Step 2 and Confirm - Right Column
                        Group::make()
                            ->schema([
                                Placeholder::make('step2')
                                    ->label(false)
                                    ->content(fn() => new HtmlString('<h3 class="text-lg font-bold text-primary">'.__('filament-2fa::two-factor.setup_step_2').'</h3>')),
                                TextInput::make('2fa_code')
                                    ->label(__('filament-2fa::two-factor.confirm'))
                                    ->autofocus()
                                    ->required(!$this->getUser()->hasTwoFactorEnabled())
                                    ->length(config('two-factor.totp.digits'))
                                    ->autocomplete(false)
                                    ->live()
                                    ->extraInputAttributes(['class' => 'text-center', 'style' => 'font-size:2.6em; letter-spacing:1rem'])
                                    ->afterStateUpdated(function ($state) {
                                        $requiredLength = config('two-factor.totp.digits');
                                        if (strlen($state) == $requiredLength) {
                                            $this->data['2fa_code'] = $state;
                                            $this->save();
                                        }
                                    }),
                            ])
                            ->columnSpan([
                                'sm' => 2, // Full-width on small screens
                                'md' => 1, // Half-width on medium and larger screens
                            ]),
                    ])
                    ->columns([
                        'sm' => 1, // Single column on small screens
                        'md' => 2, // Two columns on medium and larger screens
                    ])
            ])->visible(!$this->getUser()->hasTwoFactorEnabled());

    }

    protected function prepareTwoFactor(): array
    {
        $secret = $this->getUser()->hasTwoFactor ? $this->getUser()->getTwoFactorAuth() : $this->getUser()->createTwoFactorAuth();
        return [
            'qr_code' => $secret->toQr(),     // As QR Code
            'uri'     => $secret->toUri(),    // As "otpauth://" URI.
            'string'  => $secret->toString(), // As a string
        ];
    }

    protected function manage2FactorAuthGroupComponent(): Component
    {
        return Grid::make()
            ->schema([
                Placeholder::make('2fa_info')
                    ->inlineLabel(false)
                    ->label(fn(TwoFactorAuthentication $record) => __('filament-2fa::two-factor.enabled_message',
                        ['date' => $record->enabled_at?->format(config('filament-2fa.defaultDateTimeDisplayFormat'))])),

                Placeholder::make('trusted_devices')
                    ->inlineLabel(false)
                    ->label('Trusted devices')
                    ->content(function (TwoFactorAuthentication $record) {
                        $devices = $record->safe_devices;
                        $items = '';

                        // Iterate over each device and create an <li> element
                        foreach ($devices as $device) {
                            $formattedDate = Carbon::parse($device['added_at'])->format(config('filament-2fa.defaultDateTimeDisplayFormat'));
                            $items .= "<li>{$device['ip']} added on {$formattedDate}</li>";
                        }
                        return new HtmlString("<ul>{$items}</ul>");
                    })->visible(fn($record) =>
                        $record->safe_devices
                        && $record->safe_devices instanceof Collection
                        && $record->safe_devices->isNotEmpty()
                    ),

                Actions::make([
                    FormAction::make('ShowRecoveryCode')
                        ->color('success')
                        ->icon($this->showRecoveryCodes ? 'heroicon-m-eye-slash' : 'heroicon-m-eye')
                        ->label($this->showRecoveryCodes ? __('filament-2fa::two-factor.hide_recovery_code') : __('filament-2fa::two-factor.show_recovery_code'))
                        ->action(function () {
                            $this->showRecoveryCodes = !$this->showRecoveryCodes;
                            $this->js('$wire.$refresh()');
                        }),
                    FormAction::make('GenerateRecoveryCode')
                        ->icon('heroicon-m-key')
                        ->label(__('filament-2fa::two-factor.generate_recovery_code'))
                        ->action(function () {
                            $this->recoveryCodes = $this->getUser()->generateRecoveryCodes();
                        })
                        ->visible($this->showRecoveryCodes)
                        ->requiresConfirmation(),
                    FormAction::make('clearSafeDevices')
                        ->label('Forget safe devices')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->icon('heroicon-m-shield-exclamation')
                        ->modalDescription('These devices will require 2FA at next login')
                        ->visible(fn($record) => $record->safe_devices
                            && $record->safe_devices instanceof Collection
                            && $record->safe_devices->isNotEmpty())
                        ->action(function () {
                            $this->getUser()->forgetSafeDevices();
                            $this->js('$wire.$refresh()');
                        }),
                    FormAction::make('disableTwoFactorAuth')
                        ->label(__('filament-2fa::two-factor.disable_2fa'))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->icon('heroicon-m-shield-exclamation')
                        ->modalDescription('You will need to remove the account from your device.  That account will not work again.')
                        ->action(function () {
                            $this->getUser()->disableTwoFactorAuth();
                            $this->data['disable_two_factor_auth'] = true;
                            $this->save();
                        }),
                ]),
                Placeholder::make('recovery_code')
                    ->label('')
                    ->content($this->prepareRecoveryCodes())
                    ->visible($this->showRecoveryCodes),

            ])
            ->columns(1)
            ->visible($this->getUser()->hasTwoFactorEnabled());
    }

    public function prepareRecoveryCodes(): HtmlString
    {
        $recoveryCodesArray = Arr::pluck($this->recoveryCodes, 'code');
        $recoveryCodes = "<p>".__('filament-2fa::two-factor.recovery_instruction')."</p><ul>";
        foreach ($recoveryCodesArray as $code) {
            $recoveryCodes .= "<li>$code</li>";
        }
        $recoveryCodes .= '</ul>';
        return new HtmlString($recoveryCodes);
    }

}
