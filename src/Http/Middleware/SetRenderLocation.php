<?php

namespace Optimacloud\Filament2fa\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Arr;
use Optimacloud\Filament2fa\Models\TwoFactorBanner;

class SetRenderLocation
{
    public function handle($request, \Closure $next)
    {
        $banners = TwoFactorBanner::get();        
        if($this->checkRoutes($request) && $banners->count() > 0) {
            foreach ($banners as $banner) {                
                if ($banner->isVisible() && $this->checkAuthGuards($request) && $banner->checkGuard()) {
                    FilamentView::registerRenderHook(
                        $banner->render_location,
                        fn () => view('filament-2fa::components.banner-render', ['banner' => $banner]),
                        scopes: empty($banner->scope) ? null : $banner->scope
                    );
                }
            }
        }

        return $next($request);
    }

    private function checkAuthGuards($request)
    {
        $authGuards = config('filament-2fa.banner.auth_guards');
        return Filament::auth()->user() && 
            Arr::has($authGuards, Filament::getAuthGuard()) && 
            $authGuards[Filament::getAuthGuard()]['can_see_banner'];
    }

    private function checkRoutes($request)
    {
        $bannerManageUrl = config('filament-2fa.banner.navigation.url');
        return !$request->is($bannerManageUrl) && !$request->is("$bannerManageUrl/*") &&  
        !$request->is(...config('filament-2fa.banner.exclude_routes'));
    }
}
