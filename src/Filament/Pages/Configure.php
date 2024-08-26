<?php

namespace Optimacloud\Filament2fa\Filament\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile;
use Filament\Support\Enums\Alignment;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use function Filament\Support\is_app_url;


class Configure extends EditProfile
{

    public static ?string $slug = 'two-factor-authentication';

    public ?string $maxWidth = '6xl';


    public static function getLabel(): string
    {
        return "Two Factor Authentication";
    }

    public static function getRelativeRouteName(): string
    {
        return self::$slug;
    }

    public static function getRouteName(?string $panel = null): string
    {
        $panel = $panel ? Filament::getPanel($panel) : Filament::getCurrentPanel();
        return $panel->generateRouteName(static::getRelativeRouteName());
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

    public function getFormActionsAlignment(): string | Alignment
    {
        return Alignment::End;
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
                ->label(__('Submit 2FA Pin and complete setup'))
                ->submit('save')
                ->keyBindings(['mod+s']);
    }
    protected function afterSave(): void
    {
        if (isset($this->data['disable_two_factor_auth']) && $this->data['disable_two_factor_auth'] === true) {
            $this->getUser()->disableTwoFactorAuth();
        }
        if (isset($this->data['2fa_code']) && $this->data['2fa_code'] !== null) {
            $activated = $this->getUser()->confirmTwoFactorAuth($this->data['2fa_code']);
            if ($activated) {
                Notification::make()
                        ->title('Two factor authentication has been enabled.')
                        ->success()
                        ->send();
                /**
                 * Todo Redirect back to this page or refresh?
                 */
                $redirectUrl = self::$slug;
                $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode() && is_app_url($redirectUrl));
            } else {
                Notification::make()
                        ->title('2FA confirmation code is incorrect or expired')
                        ->danger()
                        ->send();
            }
        }
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
        return Section::make('Two Factor Authentication')
                ->relationship('twoFactorAuth')
                ->schema([
                        $this->enable2FactorAuthGroupComponent(),
                        $this->disable2FactorAuthGroupComponent()
                ]);
    }

    protected function enable2FactorAuthGroupComponent(): Component
    {
        return Group::make()
                ->schema([
                        Placeholder::make('2fa_info')
                                ->label('Setup your device')
                                ->content(new HtmlString('<p class="text-justify">When two factor authentication is enabled, you will prompted for a secure pin during login. Your phone\'s authenticator application will provide a new pin every 60 seconds</p>
<p class="text-justify">To enable 2FA scan the following QR code using your phone\'s authenticator application and submit TOTP code to confirm setup. </p>')),

                        Group::make()
                                ->schema([
                                        Placeholder::make('step1')
                                                ->label('Step 1. Scan the QR Code'),
                                        Placeholder::make('step2')
                                                ->label('Step 2. Enter the pin provided by your app'),
                                        ViewField::make('2fa_auth')
                                                ->view('filament-2fa::forms.components.2fa-settings')
                                                ->viewData($this->prepareTwoFactor()),
                                        TextInput::make('2fa_code')
                                                ->label('Confirm 2FA Code')
                                                ->numeric()
                                                ->minLength(6)
                                                ->maxLength(6)
                                                ->autocomplete(false)
                                                ->afterStateUpdated(fn($state) => $this->data['2fa_code'] = $state),
                                ])->columns(2)
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


    /**
     * ToDo Show Recovery codes only once or for 5 mins??  If lirewire refreshes the view we will lose them
     *
     * ToDo Allow user to generate new recovery codes
     */
    protected function disable2FactorAuthGroupComponent(): Component
    {
        return Group::make()
                ->schema([
                        DateTimePicker::make('enabled_at')
                                ->format(config('filament-2fa.defaultDateTimeDisplayFormat'))
                                ->readOnly(),
                        Placeholder::make('recovery_code')
                                ->label('')
                                ->content($this->prepareRecoveryCodes()),
                        Toggle::make('disable_two_factor_auth')
                                ->inline(false)
                                ->onColor('danger')
                                ->offColor('success')
                                ->onIcon('heroicon-m-x-mark')
                                ->offIcon('heroicon-m-check-circle')
                                ->live()
                                ->afterStateUpdated(fn($state) => $this->data['disable_two_factor_auth'] = $state),
                ])->visible($this->getUser()->hasTwoFactorEnabled());
    }

    public function prepareRecoveryCodes(): HtmlString
    {
        $recoveryCodesArray = Arr::pluck($this->getUser()->getRecoveryCodes(), 'code');
        $recoveryCodes = "<p>Save these recovery codes in a safe place. Each code can be used once to recover account if your two factor authentication device is lost.</p><ul>";
        foreach ($recoveryCodesArray as $code) {
            $recoveryCodes .= "<li>$code</li>";
        }
        $recoveryCodes .= '</ul>';
        return new HtmlString($recoveryCodes);
    }
}
