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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Optimacloud\Filament2fa\Filament2faServiceProvider;
use Optimacloud\Filament2fa\Tests\Models\User;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected User $user;

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
        $userMigration = include __DIR__.'/config.php';
        config()->set('database.default', 'testing');
        config()->set('two-factor', $userMigration['two-factor']);
        config()->set('auth', $userMigration['auth']);
    }

    public function createUser() 
    {
        return User::create($this->credentials());
    }

    public function credentials()
    {
        return ['email' => 'admin@domain.com', 'name' => 'Admin', 'password' => Hash::make('password') ];
    }
}
