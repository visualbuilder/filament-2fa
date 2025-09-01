<?php


use Illuminate\Support\Facades\Route;


Route::middleware(['web','2fa.is_login_session'])->group(function () {
    Route::get(config('filament-2fa.login.confirm_totp_page_url'), \Visualbuilder\Filament2fa\Filament\Pages\Confirm2Fa::class)->name('2fa.validate');
});
