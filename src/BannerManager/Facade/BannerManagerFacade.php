<?php

namespace Optimacloud\Filament2fa\BannerManager\Facade;

use Illuminate\Support\Facades\Facade;
use Optimacloud\Filament2fa\BannerManager\BannerManager;

/**
 * @see BannerManager
 */
class BannerManagerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BannerManager::class;
    }
}
