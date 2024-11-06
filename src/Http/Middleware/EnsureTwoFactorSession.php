<?php

namespace Visualbuilder\Filament2fa\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class EnsureTwoFactorSession
{
    /**
     * Handle an incoming request.
     *
     * Allow access only if 2FA session data exists.
     */
    public function handle(Request $request, Closure $next)
    {
        $sessionKey = Config::get('filament-2fa.login.credential_key', '_2fa_login');
        $hasCredentials = $request->session()->has("{$sessionKey}.credentials");
        $hasPanelId = $request->session()->has("{$sessionKey}.panel_id");

        if (!$hasCredentials || !$hasPanelId) {
            return redirect()->to(Filament::getLoginUrl());
        }

        return $next($request);
    }
}
