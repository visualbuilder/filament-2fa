<?php

namespace Optimacloud\Filament2fa\Filament\Pages;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Arr;


class Profile extends EditProfile
{
    protected function afterSave(): void
    {
        if (isset($this->data['disable_two_factor_auth']) && $this->data['disable_two_factor_auth'] === true) {
            $this->getUser()->disableTwoFactorAuth();
        }
        if (isset($this->data['2fa_code']) && $this->data['2fa_code'] !== null) {
            $activated = $this->getUser()->confirmTwoFactorAuth($this->data['2fa_code']);
            if ($activated) {
                Notification::make()
                    ->title('Two factor authentication has been initiated.')
                    ->success()
                    ->send();
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
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getTwoFactorAuthFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(! static::isSimple()),
            ),
        ];
    }

    protected function getTwoFactorAuthFormComponent(): Component
    {
        return Section::make('Two Factor Authentication')
            ->description('Additional security for your account using two  factor authentication')
            ->relationship('twoFactorAuth')
            ->schema([
                $this->enable2FactorAuthGroupComponent(),
                $this->disable2FactorAuthGroupComponent()
            ]);
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

    protected function enable2FactorAuthGroupComponent(): Component
    {
        return Group::make()
            ->schema([
                Placeholder::make('2fa_info')
                    ->label('')
                    ->content(new HtmlString('<p class="text-justify">When two factor authentication is enabled, you will prompted for a secure, random token during authentication. You may retrieve this token from your phone\'s Google Authenticator application.</p><p class="text-justify">Two factor authentication is enabled now. Scan the following QR code using your phone\'s authenticator application and submit TOTP code to confirm two factor authentication. </p>')),
                ViewField::make('2fa_auth')
                    ->view('filament-2fa::forms.components.2fa-settings')
                    ->viewData($this->prepareTwoFactor()),
                TextInput::make('2fa_code')
                    ->label('Confirm 2FA Code')
                    ->numeric()
                    ->minLength(6)
                    ->autocomplete(false)
                    ->maxLength(6)
                    ->afterStateUpdated(fn($state) => $this->data['2fa_code'] = $state),
            ])->visible(!$this->getUser()->hasTwoFactorEnabled());
    }

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
        $recoveryCodes = "<p>Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.</p><ul>";
        foreach ($recoveryCodesArray as $code) {
            $recoveryCodes .= "<li>$code</li>";
        }
        $recoveryCodes .= '</ul>';
        return new HtmlString($recoveryCodes);
    }
}
