<?php

namespace Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages;

use Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;

class EditTwoFactorBanner extends EditRecord
{
    protected static string $resource = TwoFactorBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::ScreenExtraLarge;
    }

    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => true,
            'maxWidth' => MaxWidth::SixExtraLarge,
        ];
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.simple';
    }
}
