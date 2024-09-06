<?php

use Filament\Facades\Filament;
use Laragear\TwoFactor\Models\TwoFactorAuthentication;
use Optimacloud\Filament2fa\Filament\Pages\Login;
use Optimacloud\Filament2fa\Livewire\Confirm2Fa;

use function Pest\Livewire\livewire;

it('check encrypted credentiasl are stored on sessions', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();
    livewire(Login::class)
        ->assertFormExists()
        ->fillForm([
            'email' => 'admin@domain.com',
            'password' => 'password'
        ])
        ->call('authenticate')
        ->assertRedirect('/confirm-2fa');
    $sessionKey = config('filament2fa.login.credential_key');
    $credentials = request()->session()->pull("$sessionKey.credentials", []);
    expect($credentials['email'])->toBeTrue();
    livewire(Confirm2Fa::class)
            ->assertFormExists();
});