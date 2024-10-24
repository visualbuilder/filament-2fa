<?php

namespace Visualbuilder\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages;

use Visualbuilder\Filament2fa\Filament\Resources\TwoFactorBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\MaxWidth;

class CreateTwoFactorBanner extends CreateRecord
{
    protected static string $resource = TwoFactorBannerResource::class;

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
