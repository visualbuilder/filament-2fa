<?php

namespace Visualbuilder\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages;

use Visualbuilder\Filament2fa\Filament\Resources\TwoFactorBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListTwoFactorBanners extends ListRecords
{
    protected static string $resource = TwoFactorBannerResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Create new'),
        ];
    }

    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => true,
            'maxWidth' => MaxWidth::SevenExtraLarge,
        ];
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.simple';
    }

}
