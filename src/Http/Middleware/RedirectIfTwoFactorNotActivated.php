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
        $authGuards = config('filament-2fa.auth_guards');
        if($user instanceof TwoFactorAuthenticatable
            && Arr::has($authGuards, Filament::getAuthGuard())
            && $authGuards[Filament::getAuthGuard()]['mandatory']
            && !$user->hasTwoFactorEnabled()
            && !$request->is(...config('filament-2fa.exclude_routes'))) {

            return $request->expectsJson()
            ? response()->json(['message' => trans('two-factor::messages.enable')], 403)
            : response()->redirectTo('two-factor-authentication');
        }

        return $next($request);
    }
}
