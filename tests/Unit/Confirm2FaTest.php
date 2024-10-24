<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Crypt;
use Laragear\TwoFactor\Models\TwoFactorAuthentication;
use Visualbuilder\Filament2fa\Filament\Pages\Login;
use Visualbuilder\Filament2fa\Livewire\Confirm2Fa;


use function Pest\Livewire\livewire;

it('check confirm page without login credentials', function () {
    $this->get(url(config('filament-2fa.login.confirm_totp_page_url')))
        ->assertRedirect(Filament::getLoginUrl());
});

it('check encrypted credentiasl are stored on sessions', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();

    livewire(Login::class)
        ->assertFormExists()
        ->fillForm([
            'email' => $loginEmail = 'admin@domain.com',
            'password' => 'password'
        ])
        ->call('authenticate')
        ->assertRedirect('/confirm-2fa');

    $sessionKey = config('filament-2fa.login.credential_key');
    $credentials = session("$sessionKey.credentials", []);
    expect(Crypt::decryptString($credentials['email']) )->toEqual($loginEmail);
    livewire(Confirm2Fa::class)
        ->assertFormFieldExists('totp_code')
        ->assertFormExists();
});

it('Confirm 2FA TOTP code check validation errors', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();

    livewire(Login::class)
        ->assertFormExists()
        ->fillForm([
            'email' => $loginEmail = 'admin@domain.com',
            'password' => 'password'
        ])
        ->call('authenticate')
        ->assertRedirect('/confirm-2fa');

    $sessionKey = config('filament-2fa.login.credential_key');
    $credentials = session("$sessionKey.credentials", []);
    expect(Crypt::decryptString($credentials['email']) )->toEqual($loginEmail);

    livewire(Confirm2Fa::class)
        ->assertFormExists()
        ->fillForm()
        ->call('submit')
        ->assertHasErrors(['totp_code']);

    livewire(Confirm2Fa::class)
        ->assertFormExists()
        ->fillForm([
            'totp_code' => '12345126'
        ])
        ->call('submit')
        ->assertHasErrors(['totp_code']);
    expect(!auth()->check())->toBeTrue();
});

it('Confirm 2FA TOTP code', function () {
    $user = $this->createUser();
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();

    livewire(Login::class)
        ->assertFormExists()
        ->fillForm([
            'email' => $loginEmail = 'admin@domain.com',
            'password' => 'password'
        ])
        ->call('authenticate')
        ->assertRedirect('/confirm-2fa');

    $sessionKey = config('filament-2fa.login.credential_key');
    $credentials = session("$sessionKey.credentials", []);
    expect(Crypt::decryptString($credentials['email']) )->toEqual($loginEmail);
    livewire(Confirm2Fa::class)
        ->fillForm([
            'totp_code' => $user->makeTwoFactorCode()
        ])
        ->assertFormExists()
        ->assertHasNoFormErrors()
        ->call('submit');
    expect(auth()->user()->email)->toEqual('admin@domain.com');
});
