<?php

namespace Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages;

use Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTwoFactorBanner extends CreateRecord
{
    protected static string $resource = TwoFactorBannerResource::class;
}
