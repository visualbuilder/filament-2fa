<?php
return [

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
        'enabled' => false,
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
];