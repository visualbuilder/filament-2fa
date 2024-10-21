<?php

namespace Optimacloud\Filament2fa;

use Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Optimacloud\Filament2fa\Filament\Pages\Configure;
use Optimacloud\Filament2fa\Http\Middleware\RedirectIfTwoFactorNotActivated;
use Optimacloud\Filament2fa\Http\Middleware\SetRenderLocation;
use Optimacloud\Filament2fa\Livewire\Banner;
use Optimacloud\Filament2fa\Livewire\Confirm2Fa;
use Optimacloud\Filament2fa\Livewire\TwoFactorBanner;

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

        $panel->plugins([
            
        ]);

        $panel->resources([
            TwoFactorBannerResource::class 
        ]);

        $panel->middleware([
            AuthenticateSession::class,
            RedirectIfTwoFactorNotActivated::class,
            SetRenderLocation::class,
        ], true);

        $panel->pages([
            Configure::class
        ]);

        $panel->widgets([
            Confirm2Fa::class,
            Banner::class
        ]);
    }

    public function boot(Panel $panel): void
    {
        /**
         * This should be in middleware
         */
        //TwoFactorHelper::validateAuthModel();
    }
}
