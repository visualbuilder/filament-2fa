<?php

namespace Visualbuilder\Filament2fa;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Session\Middleware\AuthenticateSession;
use Visualbuilder\Filament2fa\Filament\Pages\Configure;
use Visualbuilder\Filament2fa\Filament\Pages\Confirm2Fa;
use Visualbuilder\Filament2fa\Filament\Resources\BannerResource;
use Visualbuilder\Filament2fa\Http\Middleware\RedirectIfTwoFactorNotActivated;
use Visualbuilder\Filament2fa\Http\Middleware\SetRenderLocation;


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

        // Get panel guard mapping from config (Laravel automatically merges app config with package defaults)
        $panelId = $panel->getId();
        $panelGuardMap = config('filament-2fa.banner.panel_guard_map', []);
        $authGuard = $panelGuardMap[$panelId] ?? $panel->getAuthGuard();

        $bannerGuards = config('filament-2fa.banner.auth_guards', []);

        if (isset($bannerGuards[$authGuard]['can_manage']) && $bannerGuards[$authGuard]['can_manage'] === true) {
            $panel->resources([
                BannerResource::class
            ]);
        }

        $panel->middleware([
            AuthenticateSession::class,
            RedirectIfTwoFactorNotActivated::class,
            SetRenderLocation::class,
        ], true);

        $panel->pages([
            Configure::class,
            Confirm2Fa::class
        ]);

        $panel->widgets([

        ]);
    }

    public function boot(Panel $panel): void
    {
    }
}
