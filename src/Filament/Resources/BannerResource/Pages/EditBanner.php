<?php

namespace Visualbuilder\Filament2fa\Filament\Resources\BannerResource\Pages;

use Visualbuilder\Filament2fa\Filament\Resources\BannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

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
