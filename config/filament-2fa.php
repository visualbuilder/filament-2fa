<?php

use Filament\Pages\SubNavigationPosition;

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

    '2fa_banner_url' => 'two-factor-banner'
];
