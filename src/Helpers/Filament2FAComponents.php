<?php

namespace Visualbuilder\Filament2fa\Helpers;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Visualbuilder\Filament2fa\Contracts\TwoFactorAuthenticatable;

class Filament2FAComponents
{
    /**
     * Returns a common action to reset 2FA for a model.
     *
     * @return Action
     */
    public static function reset2FAAction():Action
    {
        return Action::make('reset2fa')
            ->label('Reset 2FA')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reset 2FA Confirmation')
            ->modalDescription('Are you sure you want to reset the 2FA? The user will need to reconfigure their authenticator app.')
            ->modalSubmitActionLabel('Delete 2FA Details')
            ->visible(fn ($record)=> $record->hasTwoFactor)
            ->action(function ($record) {
                if ($record instanceof TwoFactorAuthenticatable) {
                    $record->hasTwoFactor()->delete();
                    Notification::make('2fa_deleted')
                        ->title('2FA has been deleted')
                        ->icon('fas-lock')
                        ->iconColor('danger')
                        ->send();
                } else {
                    Notification::make('2fa_deleted')
                        ->title('The model '.basename($record). ' is not enabled for 2FA')
                        ->icon('fas-lock')
                        ->iconColor('danger')
                        ->send();
                }
            });
    }

    public static function twoFAStatusColumn(): IconColumn
    {
        return IconColumn::make('hasTwoFactor.updated_at')
            ->label('2FA Status')
            ->alignCenter()
            ->boolean()
            ->trueIcon('heroicon-o-check-circle' )
            ->falseIcon( 'heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('warning');
    }
}
