<?php

namespace Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages;

use Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTwoFactorBanners extends ListRecords
{
    protected static string $resource = TwoFactorBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}