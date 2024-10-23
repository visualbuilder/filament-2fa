# Two Factor Auth for filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/optimacloud/filament-2fa.svg?style=flat-square)](https://packagist.org/packages/optimacloud/filament-2fa)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/optimacloud/filament-2fa/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/optimacloud/filament-2fa/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/optimacloud/filament-2fa/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/optimacloud/filament-2fa/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/optimacloud/filament-2fa.svg?style=flat-square)](https://packagist.org/packages/optimacloud/filament-2fa)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require optimacloud/filament-2fa
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

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-2fa-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-2fa-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filament2fa = new Optimacloud\Filament2fa();
echo $filament2fa->echoPhrase('Hello, Optimacloud!');
```

### Step 1:

Implement TwoFactorAuthenticatables on auth model

```php
use Optimacloud\Filament2fa\Contracts\TwoFactorAuthenticatable;
use Optimacloud\Filament2fa\Traits\TwoFactorAuthentication;

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

Remember to add the *Setup Two Factor Authentication* and *Banner Notification" pages*, as well as the *TwoFactor Login* class on the PanelServiceProvider.

```php
use Optimacloud\Filament2fa\Filament\Pages\Login;

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
            MenuItem::make('two-factor')
                ->url('/two-factor-authentication')
                ->label('Two Factor Auth')
                ->icon('heroicon-o-key')
                ->sort(1),
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
