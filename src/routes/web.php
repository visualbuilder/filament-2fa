<?php
use Illuminate\Support\Facades\Route;
use Optimacloud\Filament2fa\Filament\Pages\Register;


Route::prefix('2fa')->middleware('auth:admin,customer,associate,client')->group(function () {
        Route::get('register-two-factor',[Register::class,'register'])->name('filament-2fa.register')->withoutMiddleware('2fa');

        /**
         * These routes can be replaced by actions
         */
//        Route::patch('regenerate',[Auth\TwoFAController::class,'regenerate'])->name('2fa.regenerate');
//        Route::get('delete-two-factor/{userType}/{authId}',[Auth\TwoFAController::class,'deleteTwoFactor'])->name('2fa.deleteTwoFactor');
    });


