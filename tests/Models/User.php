<?php

namespace Optimacloud\Filament2fa\Tests\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Optimacloud\Filament2fa\Contracts\TwoFactorAuthenticatable;
use Optimacloud\Filament2fa\Traits\TwoFactorAuthentication;

/**
 * @property string $email
 * @property string $name
 * @property string $password
 */
class User extends Authenticatable implements FilamentUser, TwoFactorAuthenticatable 
{
    use TwoFactorAuthentication;

    protected $guarded = [];

    protected $fillable = ['name', 'email', 'password'];

    public $timestamps = false;

    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
