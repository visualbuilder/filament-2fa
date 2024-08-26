<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Optimacloud\Filament2fa\Filament\Pages\Configure;
use Optimacloud\Filament2fa\Filament\Pages\Confirm2FA;

Route::middleware([
    EncryptCookies::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
    AuthenticateSession::class,
    'auth:' . config('filament-user-consent.auth-guards'),
])->group(function () {
    Route::name('2fa.')->group(function () {
        Route::get('two-factor-authentication', Configure::class)->name('register.setup');
    });
});
