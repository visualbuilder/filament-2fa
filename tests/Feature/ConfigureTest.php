<?php

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Optimacloud\Filament2fa\Filament\Pages\Configure;
use Laragear\TwoFactor\Models\TwoFactorAuthentication;
use function Pest\Livewire\livewire;

it('can access configure page by user', function () {
    $this->actingAs(
        $this->createUser()
    )->get(Configure::getUrl())->assertSuccessful();
});

it('can see 2fa confirm code', function () {
    $user = $this->createUser();
    $this->actingAs($user);
    livewire(Configure::class)
        ->refresh()
        ->assertFormExists()        
        ->assertFormFieldExists('2fa_code');
});

it('can enable two factor authentication', function () {
    $user = $this->createUser();
    $this->actingAs($user);        
    $user->twoFactorAuth()->save(
        TwoFactorAuthentication::factory()->make()
    );
    expect($user->hasTwoFactorEnabled())->toBeTrue();
});