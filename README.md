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
