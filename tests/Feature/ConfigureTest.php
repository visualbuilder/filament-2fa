<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Optimacloud\Filament2fa\Filament\Pages\Configure;
use Optimacloud\Filament2fa\Tests\Models\User;


it('can access configure page by user', function () {
    $this->actingAs(
        User::create(['email' => 'admin@domain.com', 'name' => 'Admin', 'password' => Hash::make('password') ])
    )->get(Configure::getUrl())->assertSuccessful();
});

it('can enable two factor authentication', function () {
    $this->actingAs(
        User::create(['email' => 'admin@domain.com', 'name' => 'Admin', 'password' => Hash::make('password') ])
    );
});