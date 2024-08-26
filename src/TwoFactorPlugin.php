<?php

namespace Optimacloud\Filament2fa;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Optimacloud\Filament2fa\Filament\Pages\Configure;


class TwoFactorPlugin implements Plugin
{
    public string $navigationGroup;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'filament-two-factor';
    }

    public function navigationGroup($navigationGroup): static
    {
        $this->navigationGroup = $navigationGroup;
        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup ?? config('filament-email-templates.navigation.templates.group');
    }

    public function register(Panel $panel): void
    {
        $panel->resources([

        ]);

        $panel->pages([
            Configure::class
        ]);

    }

    public function boot(Panel $panel): void
    {
        //
    }
}