<?php

use Visualbuilder\Filament2fa\Filament\Pages\Configure;
use Laragear\TwoFactor\Models\TwoFactorAuthentication;
use function Pest\Livewire\livewire;

it('can access configure page by user', function () {
    $this->actingAs(
        $this->createUser()
    )->get('/two-factor-authentication')->assertSuccessful();
});

it('can see 2fa confirm code & validation', function () {
    $user = $this->createUser();
    $this->actingAs($user);
    livewire(Configure::class)
        ->assertFormExists()
        ->assertFormFieldExists('two_factor_code')
        ->fillForm(['two_factor_code' => '000000'])
        ->call('confirmTwoFactorFromForm')
        ->assertNotified('Invalid code');
});

it('can enable two factor authentication', function () {
    $user = $this->createUser();
    $this->actingAs($user);
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();
});

it('can disable two factor authentication', function () {
    $user = $this->createUser();
    $this->actingAs($user);
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();

    // Disable by calling the method directly (action is inside schema content)
    livewire(Configure::class)
        ->call('disable2fa');

    $user->refresh();
    $user->load('twoFactorAuth');
    expect($user->hasTwoFactorEnabled())->toBeFalse();
});
