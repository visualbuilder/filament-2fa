<?php

use Filament\Pages\SubNavigationPosition;

return [
    'defaultDateTimeDisplayFormat'  => 'd M Y H:i',

    /**
     * When two factor is required exclude these routes from redirect
     */
    'exclude_routes' => [
        'two-factor-authentication',
        'confirm-2fa',
        'logout',
    ],

    'login' => [
        'credential_key' => '_2fa_login',
        'confirm_totp_page_url' => 'confirm-2fa'
    ],

    /**
     * 2FA link options
     */
    'navigation' => [
        'visible_on_navbar' => true,
        'icon' => 'heroicon-o-key',
        'group' => 'Auth Security',
        'label' => 'Two Factor Auth',
        'url' => 'two-factor-authentication',
        'cluster' => null,
        'sort' => 1,
        'subnav_position' => SubNavigationPosition::Top
    ],

    /**
     * Configure which auth guards 2fa should apply to
     */
    'auth_guards' => [
        'web' => [
            'enabled' => 'true',
            'mandatory' => false
        ]
    ],

    'banner' => [

        /**
         * Configure which auth guards banners should apply to
         * This will change the dropdown in the banner editor
         */
        'auth_guards' => [
            'web' => [
                'can_manage' => true,
                'can_see_banner' => true,
            ]
        ],

        /**
         * Navigation link options
         */
        'navigation' => [
            'icon' => 'heroicon-m-megaphone',
            'label' => 'Banners',
            'url' => 'banner-manager',
            'sort' => 50,
        ],
        /**
         * Do not show banners on these routes
         */
        'excluded_routes' => [
            'two-factor-authentication',
            'confirm-2fa',
        ]
    ]
];
