<?php

use Optimacloud\Filament2fa\Livewire\Confirm2Fa;
use Illuminate\Support\Facades\Route;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Optimacloud\Filament2fa\Livewire\TwoFactorBanner;

Route::middleware([
    EncryptCookies::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
    AuthenticateSession::class,
])->group(function () {
    Route::name('2fa.')->group(function () {
        Route::get( config('filament-2fa.login.confirm_totp_page_url') , Confirm2Fa::class)->name('validate');
        Route::get( 'two-factor-banner' , TwoFactorBanner::class)->name('banner');
    });
});
