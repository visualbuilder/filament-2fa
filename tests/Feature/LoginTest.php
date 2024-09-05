<?php

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Optimacloud\Filament2fa\Filament\Pages\Configure;
use Laragear\TwoFactor\Models\TwoFactorAuthentication;
use Optimacloud\Filament2fa\Filament\Pages\Login;
use Optimacloud\Filament2fa\Tests\Models\Admin;

use function Pest\Livewire\livewire;

it('can access login page', function () {
    livewire(Login::class)
        ->assertFormExists()
        ->assertFormFieldExists('email')
        ->assertFormFieldExists('password');
});

it('can login get validation error without 2factor', function () {
    $this->createUser();
    livewire(Login::class)
        ->assertFormExists()
        ->fillForm()
        ->call('authenticate')
        ->assertHasFormErrors([
            'email' => 'required',
            'password' => 'required'
        ]);
});

it('can login without 2factor', function () {
    $this->createUser();
    livewire(Login::class)
        ->assertFormExists()
        ->fillForm([
            'email' => 'admin@domain.com',
            'password' => 'password'
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();
    expect(auth()->user()->email)->toEqual('admin@domain.com');
});

it('can redirect to confirm page to verify totp code', function () {
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
});