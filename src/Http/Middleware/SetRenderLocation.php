<?php

namespace Optimacloud\Filament2fa\Http\Middleware;

use Filament\Support\Facades\FilamentView;
use Optimacloud\Filament2fa\Models\TwoFactorBanner;

class SetRenderLocation
{
    public function handle($request, \Closure $next)
    {
        $banners = TwoFactorBanner::get();
        if($banners->count() > 0) {
            foreach ($banners as $banner) {
                if ($banner->isVisible() && $banner->checkGuard()) {
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
}
