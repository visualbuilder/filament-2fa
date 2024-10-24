# Two Factor Auth for filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/visualbuilder/filament-2fa.svg?style=flat-square)](https://packagist.org/packages/visualbuilder/filament-2fa)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/visualbuilder/filament-2fa/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/visualbuilder/filament-2fa/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/visualbuilder/filament-2fa/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/visualbuilder/filament-2fa/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/visualbuilder/filament-2fa.svg?style=flat-square)](https://packagist.org/packages/visualbuilder/filament-2fa)


Adds Two Factor authentication to Filament Panels. 
Requires an app like Authy or Google Authenticator to generate One Time Pins every 60 seconds.



## Installation

You can install the package via composer:

```bash
composer require visualbuilder/filament-2fa
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-2fa-migrations"
php artisan migrate
```

A Banner Seeder adds a configurable Setup 2FA banner shown to users who are not setup yet
```bash
php artisan vendor:publish --tag="filament-2fa-seeders"
php artisan db:seed --class=TwoFactorBannerSeeder
```

Publish the config files
```bash
php artisan vendor:publish --tag="filament-2fa-config"
```
This package extends the https://github.com/Laragear/TwoFactor
so you will get two config files:-
```bash
config/two-factor.php
config/filament-2fa.php
```

##Review the config files

```php
    /*
    |--------------------------------------------------------------------------
    | Safe Devices
    |--------------------------------------------------------------------------
    |
    | Authenticating with Two-Factor Codes can become very obnoxious when the
    | user does it every time. "Safe devices" allows to remember the device
    | for a period of time which 2FA Codes won't be asked when login in.
    |
    */

    'safe_devices' => [
        'enabled' => true,
        'cookie' => '_2fa_remember',
        'max_devices' => 3,
        'expiration_days' => 14,
    ],
```

Optionally, you can publish the views using
```bash
php artisan vendor:publish --tag="filament-2fa-views"
```


## Usage
Minimal configuration required to enable 2FA on a panel.

### Step 1:

Implement TwoFactorAuthenticatables on the authenticatable model

```php
use Visualbuilder\Filament2fa\Contracts\TwoFactorAuthenticatable;
use Visualbuilder\Filament2fa\Traits\TwoFactorAuthentication;

class Admin extends Authenticatable implements FilamentUser, TwoFactorAuthenticatable
{
    use HasFactory, TwoFactorAuthentication;
}
```

### Step 2:

Add TwoFactor Plugin on PanelServiceProvider

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->plugins([
            TwoFactorPlugin::make()
        ])
}
```

### Step 3:

Add menu items where required.
For all users  *Setup Two Factor Authentication* link 
For Admins only *Banner Manager pages*


```php
use Visualbuilder\Filament2fa\Filament\Pages\Login;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->plugins([
            TwoFactorPlugin::make()
        ])
        ->login(Login::class)
        ->userMenuItems([
        /**
        * All users page to configure their 2fa
         */
            MenuItem::make('two-factor')
                ->url('/two-factor-authentication')
                ->label('Two Factor Auth')
                ->icon('heroicon-o-key')
                ->sort(1),
                
               /**
                * This allows editing system wide banners - should only be available to admins 
                 */
            MenuItem::make('two-factor-banner')
                ->url(config('filament-2fa.banner.navigation.url'))
                ->label(config('filament-2fa.banner.navigation.label'))
                ->icon(config('filament-2fa.banner.navigation.icon'))
                ->sort(2)
                ->visible(config('filament-2fa.banner.auth_guards.admin.can_manage')),
        ])
}
```

### Step 4:

Can enable or disable TwoFactor in filament-2fa.php config file

```php
use Filament\Pages\SubNavigationPosition;
return [
    'defaultDateTimeDisplayFormat'  => 'd M Y H:i',

    'exclude_routes' => [
        'two-factor-authentication',
        'confirm-2fa',
        'logout',
    ],

    'login' => [
        'flashLoginCredentials' => false,
        'credential_key' => '_2fa_login',
        'confirm_totp_page_url' => 'confirm-2fa'
    ],

    'navigation' => [
        'visible_on_navbar' => true,
        'icon' => 'heroicon-o-key',
        'group' => 'Auth Security',
        'label' => 'Two Factor Auth',
        'cluster' => null,
        'sort_no' => 10,
        'subnav_position' => SubNavigationPosition::Top
    ],

    'auth_guards' => [
        'web' => [
            'enabled' => 'true', 
            'mandatory' => false
        ]
    ],

    'banner' => [        
        'auth_guards' => [
            'web' => [
                'can_manage' => true,
                'can_see_banner' => true,
            ]
        ],
        'navigation' => [
            'icon' => 'heroicon-m-megaphone',
            'label' => '2FA Banners',
            'url' => 'two-factor-banner'
        ],
        'excluded_routes' => [
            'two-factor-authentication',
            'confirm-2fa',
        ]
    ]
];
```

### Middleware
```
1. RedirectIfTwoFactorNotActivated.php
2. SetRenderLocation.php
```
If the mandatory authentication guard user has not set up 2FA, they will be redirected to the two-factor authentication setup page by the **RedirectIfTwoFactorNotActivated** middleware.

The **SetRenderLocation** middleware will display a notification banner on a page to remind to enable 2FAThe SetRenderLocationmiddleware will display a notification banner on a page to remind users to enable 2FA.

### 2FA Notification Banner
In the configuration, if the auth guard is enabled to manage the banner, the user can create, edit, delete, and enable/disable the banner. 

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Lee Evans](https://github.com/lee)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
