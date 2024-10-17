<?php

namespace Optimacloud\Filament2fa\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Optimacloud\Filament2fa\Models\TwoFactorBanner;

class SetRenderLocation
{
    public function handle($request, \Closure $next)
    {
        $banners = TwoFactorBanner::get();
        
        if($banners->count() > 0) {
            foreach ($banners as $banner) {                
                if ($banner->isVisible() && $this->checkAuthGuardsAndRoutes() && $banner->checkGuard()) {
                    FilamentView::registerRenderHook(
                        $banner->render_location,
                        fn () => view('filament-2fa::components.banner', ['banner' => $banner]),
                        scopes: empty($banner->scope) ? null : $banner->scope
                    );
                }
            }
        }

        return $next($request);
    }

    private function checkAuthGuardsAndRoutes()
    {
        $authGuards = config('filament-2fa.banner.auth_guards');
        return Filament::auth()->user() && 
            isset($authGuards[Filament::getAuthGuard()]) && 
            $authGuards[Filament::getAuthGuard()]['can_see_banner'] && 
            (!in_array(Route::current()->uri, config('filament-2fa.banner.excluded_routes')));
    }
}