<?php

return [
    'defaultDateTimeDisplayFormat'  => 'd M Y H:i',

    /**
     * When two factor is required exclude these routes from redirect
     */
    'exclude_routes' => [
        '2fa.register.setup',
        '2fa.register',
        'logout',
    ],

    'login' => [
        'flashLoginCredentials' => false,
        'credential_key' => '_2fa_login',
    ]
];
