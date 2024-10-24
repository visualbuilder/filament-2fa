<?php

namespace Visualbuilder\Filament2fa\Filament\Resources\BannerResource\Pages;

use Visualbuilder\Filament2fa\Filament\Resources\BannerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\MaxWidth;

class CreateBanner extends CreateRecord
{
    protected static string $resource = BannerResource::class;

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
