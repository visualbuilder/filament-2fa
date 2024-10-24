<?php

namespace Visualbuilder\Filament2fa\Helpers;

use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;

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
                    $this->notify('success', '2FA has been reset.');
                } else {
                    $this->notify('danger', 'This user does not support 2FA reset.');
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
