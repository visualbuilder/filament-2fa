<?php

namespace Visualbuilder\Filament2fa\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable;

class RedirectIfTwoFactorNotActivated
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $authGuard = Filament::getAuthGuard();

        if ($this->requires2FARedirection($user, $authGuard, $request)) {
            return $this->handle2FARedirection($request);
        }

        return $next($request);
    }

    private function requires2FARedirection($user, $authGuard, $request): bool
    {
        $authGuards = config('filament-2fa.auth_guards');
        return $user instanceof TwoFactorAuthenticatable
            && Arr::has($authGuards, $authGuard)
            && $authGuards[$authGuard]['mandatory']
            && !$user->hasTwoFactorEnabled()
            && !$request->is(...config('filament-2fa.excluded_routes'));
    }

    private function handle2FARedirection(Request $request)
    {
        return $request->expectsJson()
            ? response()->json(['message' => trans('two-factor::messages.enable')], 403)
            : response()->redirectTo('two-factor-authentication');
    }

}
