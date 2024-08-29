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
        return __('filament-2fa::two-factor.profile_label');
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
            ->label(__('filament-2fa::two-factor.action_label'))
            ->submit('save')
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
                /**
                 * Todo Redirect back to this page or refresh?
                 */
                $redirectUrl = self::$slug;
                $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode() && is_app_url($redirectUrl));
            } else {
                Notification::make()
                    ->title(__('filament-2fa::two-factor.fail_confirm'))
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
        return Section::make(__('filament-2fa::two-factor.profile_title'))
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
                    ->label(__('filament-2fa::two-factor.setup_title'))
                    ->content(new HtmlString('<p class="text-justify">' . __('filament-2fa::two-factor.setup_message_1') . '</p>
<p class="text-justify">' . __('filament-2fa::two-factor.setup_message_2') . '</p>')),

                Group::make()
                    ->schema([
                        Placeholder::make('step1')
                            ->label(__('filament-2fa::two-factor.setup_step_1')),
                        Placeholder::make('step2')
                            ->label(__('filament-2fa::two-factor.setup_step_2')),
                        ViewField::make('2fa_auth')
                            ->view('filament-2fa::forms.components.2fa-settings')
                            ->viewData($this->prepareTwoFactor()),
                        TextInput::make('2fa_code')
                            ->label(__('filament-2fa::two-factor.confirm'))
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
                    ->label(__('filament-2fa::two-factor.enabled_at'))
                    ->format(config('filament-2fa.defaultDateTimeDisplayFormat'))
                    ->readOnly(),
                Placeholder::make('recovery_code')
                    ->label('')
                    ->content($this->prepareRecoveryCodes()),
                Toggle::make('disable_two_factor_auth')
                    ->label(__('filament-2fa::two-factor.disable_2fa'))
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
        $recoveryCodes = "<p>" . __('filament-2fa::two-factor.recovery_instruction') . "</p><ul>";
        foreach ($recoveryCodesArray as $code) {
            $recoveryCodes .= "<li>$code</li>";
        }
        $recoveryCodes .= '</ul>';
        return new HtmlString($recoveryCodes);
    }
}
