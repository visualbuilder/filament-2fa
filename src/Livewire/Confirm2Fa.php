<?php

namespace Optimacloud\Filament2fa\Livewire;

use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Group;
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

    public function getUser(): Authenticatable & Model
    {
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
                TextInput::make('totp_code')
                    ->label('One Time Pin')
                    ->required()
                    ->numeric()
                    ->minLength(6)
                    ->maxLength(6)
                    ->autocomplete(false),
                Toggle::make('safe_device_enable')
                    ->label('Enable safe device')
                    ->inline(false)
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-mark'),
            ]);
    }

    public function submit(): void
    {
        $formData = $this->form->getState();

        if (app(FilamentTwoFactor::class, ['input' => 'totp_code', 'code' => $formData['totp_code'], 'safeDeviceInput' => $formData['safe_device_enable'] ?: false])->validate2Fa($this->getUser())) {
            Notification::make()
                ->title('Success')
                ->body('Your 2FA has been verified.')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->send();
            request()->session()->put('2fa_verified', time());
            $this->redirect(Filament::getUrl());
        } else {
            $this->throwTotpcodeValidationException();
        }
    }

    protected function throwTotpcodeValidationException(): never
    {
        throw ValidationException::withMessages([
            'totp_code' => 'The TOTP code has expired or is invalid.',
        ]);
    }
}
