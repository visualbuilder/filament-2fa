<?php

namespace Optimacloud\Filament2fa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable as TwoFactor;

class RedirectIfTwoFactorNotConfirmed
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (!$user instanceof TwoFactor || !$user->hasTwoFactorEnabled() || config('two-factor.safe_devices.enabled', false) === false) {
            return $next($request);
        }

        if (
            $user instanceof TwoFactor
            && $user->hasTwoFactorEnabled()
            && config('two-factor.safe_devices.enabled', false)
            && $user->isSafeDevice($request)
            && !$request->routeIs(config('filament-2fa.excluded_routes', []))
        ) {
            return $next($request);
        }


        return $request->expectsJson()
            ? response()->json(['message' => trans('two-factor::messages.enable')], 403)
            : response()->redirectToRoute('2fa.validate');
    }
}