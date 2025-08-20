<?php

namespace Visualbuilder\Filament2fa\Filament\Resources\BannerResource\Pages;

use Filament\Support\Enums\Width;
use Visualbuilder\Filament2fa\Filament\Resources\BannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::ScreenExtraLarge;
    }

    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => true,
            'maxWidth' => Width::SixExtraLarge,
        ];
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.simple';
    }
}
