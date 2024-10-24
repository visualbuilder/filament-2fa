<?php

use Visualbuilder\Filament2fa\Tests\Models\User;

return [
    'two-factor' => [
        'cache' => [
            'store' => null,
            'prefix' => '2fa.code',
        ],
        'recovery' => [
            'enabled' => true,
            'codes' => 10,
            'length' => 8,
        ],
        'safe_devices' => [
            'enabled' => true,
            'cookie' => '_2fa_remember',
            'max_devices' => 3,
            'expiration_days' => 14,
        ],
        'confirm' => [
            'key' => '_2fa',
            'time' => 60 * 3, // 3 hours
        ],
        'login' => [
            'view' => 'two-factor::login',
            'key' => '_2fa_login',
            'flash' => true,
        ],
        'secret_length' => 20,
        'issuer' => env('OTP_TOTP_ISSUER'),

        'totp' => [
            'digits' => 6,
            'seconds' => 30,
            'window' => 1,
            'algorithm' => 'sha1',
        ],
        'qr_code' => [
            'size' => 400,
            'margin' => 4,
        ]
    ],
    'auth' => [
        'defaults' => [
            'guard' => 'web',
            'passwords' => 'users',
        ],

        'guards' => [

            'web' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
        ],

        'providers' => [
            'users' => [
                'driver' => 'eloquent',
                'model' => User::class,
            ]
        ],

        'passwords' => [
            'users' => [
                'provider' => 'users',
                'table' => 'password_reset_tokens',
                'expire' => 60,
                'throttle' => 60,
            ],
        ],
        'password_timeout' => 10800,
    ]
];
