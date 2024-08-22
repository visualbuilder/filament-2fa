<?php

namespace Optimacloud\Filament2fa;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class Filament2faServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name("filament-2fa")
            ->hasMigrations(['create_two_factor_authentications_table'])
            ->hasConfigFile(['filament-2fa'])
            ->hasViews('filament-2fa')
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();
    }
}
