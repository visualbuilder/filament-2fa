<?php

namespace Optimacloud\Filament2fa\BannerManager;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Kenepa\Banner\BannerPlugin as BaseBannerPlugin;
use Kenepa\Banner\Http\Middleware\SetRenderLocation;
use Kenepa\Banner\Services\CacheStorageService;
use Kenepa\Banner\Services\DatabaseStorageService;
use Optimacloud\Filament2fa\Livewire\BannerManagerPage;
use Kenepa\Banner\Contracts\BannerStorage;

class BannerPlugin extends BaseBannerPlugin
{
    public function register(Panel $panel): void
    {
        $panel->pages([
            BannerManagerPage::class,
        ]);

        $panel->middleware([
            SetRenderLocation::class,
        ], true);

        app()->singleton(BannerStorage::class, function () {
            if ($this->persistBannersInDatabase) {
                return new DatabaseStorageService();
            }

            return new CacheStorageService();
        });
    }
}
