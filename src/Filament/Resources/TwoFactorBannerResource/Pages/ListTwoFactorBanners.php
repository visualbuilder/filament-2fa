<?php

namespace Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages;

use Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListTwoFactorBanners extends ListRecords
{
    protected static string $resource = TwoFactorBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
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
