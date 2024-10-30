<?php

namespace Visualbuilder\Filament2fa\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Visualbuilder\Filament2fa\Models\Banner;

class SetRenderLocation
{
    public function handle($request, \Closure $next)
    {
        // Attempt to retrieve banners from cache
        $banners = Cache::remember('all_banners', 60, function () {
            return Banner::where('is_active', true)->get();
        });

        $user = $request->user();
        if (!$this->checkRoutes($request)) {
            return $next($request);
        }

        $filteredBanners = $this->filterBannersForUser($banners, $user);

        foreach ($filteredBanners as $banner) {
            FilamentView::registerRenderHook(
                $banner->render_location,
                fn() => view('filament-2fa::components.banner-render', ['banner' => $banner]),
                scopes: empty($banner->scope) ? null : $banner->scope
            );
        }

        return $next($request);
    }


    private function filterBannersForUser($banners, $user)
    {
        return $banners->filter(function (Banner $banner) use ($user) {
            $is2FAEnabledForGuard = $this->isTwoFaEnabledForCurrentGuard();
            return $banner->isVisible()
                && $banner->isApplicableForUser($user)
                && $banner->checkGuard()
                && $this->checkCurrentAuth()
                && (!$banner->is_2fa_setup || $is2FAEnabledForGuard);
        });
    }

    private function isTwoFaEnabledForCurrentGuard()
    {
        $authGuards = config('filament-2fa.auth_guards');
        $currentGuard = Filament::getAuthGuard();
        return Arr::has($authGuards, $currentGuard) && $authGuards[$currentGuard]['enabled'];
    }

    private function checkCurrentAuth()
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
        !$request->is(...config('filament-2fa.banner.excluded_routes'));
    }
}
