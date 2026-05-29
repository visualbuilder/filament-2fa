<?php

namespace Visualbuilder\Filament2fa\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Visualbuilder\Filament2fa\Filament2faServiceProvider;
use Visualbuilder\Filament2fa\Tests\Models\User;
use Filament\Facades\Filament;
use Livewire\Mechanisms\DataStore;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Facades\View;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
        $dataStore = app(DataStore::class);
        app()->instance(DataStore::class, $dataStore);

        // Boot the Filament panel for direct Livewire component testing
        Filament::setCurrentPanel(Filament::getPanel('user'));
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
            SchemasServiceProvider::class,
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
    }

    protected function defineDatabaseMigrations(): void
    {
        // Run migrations here (not in getEnvironmentSetUp) so the Eloquent
        // connection resolver is bound. laragear/two-factor v3 resolves the
        // schema builder through the model's connection, which is unavailable
        // during environment setup.
        $userMigration = include __DIR__.'/database/migrations/create_users_table.php';
        $userMigration->up();

        $bannerMigration = include __DIR__.'/database/migrations/create_two_factor_banners_table.php';
        $bannerMigration->up();

        $twoFactorMigration = include __DIR__.'/database/migrations/create_two_factor_authentications_table.php';
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
