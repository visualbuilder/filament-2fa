<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Crypt;
use Laragear\TwoFactor\Models\TwoFactorAuthentication;
use Visualbuilder\Filament2fa\Filament\Pages\Login;
use Visualbuilder\Filament2fa\Filament\Pages\Confirm2Fa;

use function Pest\Livewire\livewire;

it('check confirm page without login credentials', function () {
    $this->get(url(config('filament-2fa.login.confirm_totp_page_url')))
        ->assertRedirect(Filament::getLoginUrl());
});

it('check encrypted credentials are stored on sessions', function () {
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
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    $sessionKey = config('filament-2fa.login.credential_key');
    $credentials = session("$sessionKey.credentials", []);
    expect(Crypt::decryptString($credentials['email']))->toEqual($loginEmail);
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
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    $sessionKey = config('filament-2fa.login.credential_key');
    $credentials = session("$sessionKey.credentials", []);
    expect(Crypt::decryptString($credentials['email']))->toEqual($loginEmail);

    // Empty code should fail validation
    livewire(Confirm2Fa::class)
        ->fillForm(['totp_code' => ''])
        ->call('submit')
        ->assertHasFormErrors(['totp_code' => 'required']);

    // Invalid code should not authenticate the user
    livewire(Confirm2Fa::class)
        ->fillForm(['totp_code' => '123456'])
        ->call('submit')
        ->assertNotified('Invalid Code');
    expect(auth()->check())->toBeFalse();
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
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    $sessionKey = config('filament-2fa.login.credential_key');
    $credentials = session("$sessionKey.credentials", []);
    expect(Crypt::decryptString($credentials['email']))->toEqual($loginEmail);
    livewire(Confirm2Fa::class)
        ->fillForm([
            'totp_code' => $user->makeTwoFactorCode()
        ])
        ->assertFormExists()
        ->assertHasNoFormErrors()
        ->call('submit');
    expect(auth()->user()->email)->toEqual('admin@domain.com');
});

it('does not remember the device when toggle is off', function () {
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
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    livewire(Confirm2Fa::class)
        ->fillForm([
            'totp_code' => $user->makeTwoFactorCode(),
        ])
        ->call('submit');

    $user->refresh();
    expect($user->twoFactorAuth->safe_devices)->toBeEmpty();
});

it('remembers the device when toggle is on', function () {
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
        ->assertRedirect(config('filament-2fa.login.confirm_totp_page_url'));

    livewire(Confirm2Fa::class)
        ->fillForm([
            'totp_code' => $user->makeTwoFactorCode(),
            'safe_device_enable' => true,
        ])
        ->call('submit');

    $user->refresh();
    expect($user->twoFactorAuth->safe_devices)->not()->toBeEmpty();
});
