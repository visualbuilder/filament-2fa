<?php

namespace Optimacloud\Filament2fa;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;


class Filament2faServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name("filament-2fa")
            ->hasConfigFile(['filament-2fa'])
            ->hasViews('filament-2fa')
            ->runsMigrations()
            ->hasCommands([
//                InstallCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        if ($this->app->runningInConsole()) {
            $this->publishMigrations();
            $this->publishConfigs();
        }
    }

    protected function publishMigrations()
    {
        $now = now();
        $files = Collection::make(File::files(base_path('vendor/laragear/two-factor/database/migrations')))
            ->mapWithKeys(fn(\SplFileInfo $file): array => [
                $file->getRealPath() => Str::of($file->getFileName())
                    ->after('0000_00_00_000000')
                    ->prepend($now->addSecond()->format('Y_m_d_His'))
                    ->prepend('/')
                    ->prepend($this->app->databasePath('migrations'))
                    ->toString(),
            ]);

        $this->publishes($files->toArray(), 'filament-2fa-migrations');
    }

    protected function publishConfigs()
    {
        $this->publishes([
            base_path('vendor/laragear/two-factor/config/two-factor.php') => base_path('config/two-factor.php')
        ]);
    }
}
