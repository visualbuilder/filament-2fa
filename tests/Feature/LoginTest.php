<?php

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Optimacloud\Filament2fa\Filament\Pages\Configure;
use Laragear\TwoFactor\Models\TwoFactorAuthentication;
use Optimacloud\Filament2fa\Filament\Pages\Login;

use function Pest\Livewire\livewire;

it('can access login page', function () {
    livewire(Login::class)
        ->assertFormExists()
        ->assertFormFieldExists('email')
        ->assertFormFieldExists('password');
});

it('can login without 2factor', function () {
    $this->createUser();
    livewire(Login::class)
        ->assertFormExists()
        ->fillForm($this->credentials())
        ->call('authenticate');
        // ->toBe(LoginResponse::class);
});