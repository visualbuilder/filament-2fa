<?php

namespace Optimacloud\Filament2fa\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable;

class RedirectIfTwoFactorNotActivated
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $authGuards = config('filament-2fa.auth_guards');
        if($user instanceof TwoFactorAuthenticatable && (isset($authGuards[Filament::getAuthGuard()]) && $authGuards[Filament::getAuthGuard()]['mandatory']) && !$user->hasTwoFactorEnabled()) {
            return $request->expectsJson()
            ? response()->json(['message' => trans('two-factor::messages.enable')], 403)
            : response()->redirectTo('two-factor-authentication');
        }

        return $next($request);        
    }
}
