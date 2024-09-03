<?php

namespace Optimacloud\Filament2fa\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Optimacloud\Filament2fa\Filament2faServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            UserPanelProvider::class,
            Filament2faServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $this->setupConfig();
        
        $userMigration = include __DIR__.'/database/migrations/create_users_table.php';
        $userMigration->up();

        $twoFactorMigration = include __DIR__.'/../vendor/laragear/two-factor/database/migrations/0000_00_00_000000_create_two_factor_authentications_table.php';
        $twoFactorMigration->up();
        
    }

    protected function setupConfig()
    {
        
       $configs = [
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

        config()->set('database.default', 'testing');

        config()->set('two-factor', $configs);
    }
}
