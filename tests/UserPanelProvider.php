<?php

namespace Visualbuilder\Filament2fa\Tests;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Visualbuilder\Filament2fa\Filament\Pages\Configure;
use Visualbuilder\Filament2fa\Filament\Resources\BannerResource;

class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('user')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                BannerResource::class
            ])
            ->pages([
                Configure::class
            ])
            ->userMenuItems([
                MenuItem::make('two-factor')
                    ->url(config('filament-2fa.navigation.url'))
                    ->label(config('filament-2fa.navigation.label'))
                    ->icon(config('filament-2fa.navigation.icon'))
                    ->sort(config('filament-2fa.navigation.sort')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
