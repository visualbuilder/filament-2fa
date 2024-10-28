<?php


use Illuminate\Support\Facades\Route;
use Visualbuilder\Filament2fa\Livewire\Confirm2Fa;

Route::middleware(['web','2fa.is_login_session'])->group(function () {
    Route::get(config('filament-2fa.login.confirm_totp_page_url'), Confirm2Fa::class)->name('2fa.validate');
});
