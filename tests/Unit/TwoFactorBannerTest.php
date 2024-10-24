<?php

use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Tables\Actions\DeleteBulkAction;
use Visualbuilder\Filament2fa\Filament\Resources\TwoFactorBannerResource;
use Visualbuilder\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages\CreateTwoFactorBanner;
use Visualbuilder\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages\EditTwoFactorBanner;
use Visualbuilder\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages\ListTwoFactorBanners;
use Visualbuilder\Filament2fa\Models\TwoFactorBanner;

use function Pest\Livewire\livewire;

it('can access banner page', function () {
    $this->actingAs($this->createUser());
    $this->get(TwoFactorBannerResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list banners', function () {
    $this->actingAs($this->createUser());
    $banners = TwoFactorBanner::factory()->count(5)->create();
    livewire(ListTwoFactorBanners::class)
        ->assertCanSeeTableRecords($banners);
});

it('can access create banner page', function () {
    $this->actingAs($this->createUser());

    $this->get(TwoFactorBannerResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create banner', function () {

    $banner = TwoFactorBanner::factory()->make();

    $storedData = livewire(CreateTwoFactorBanner::class)
        ->fillForm([
            'name' => $banner->name,
            'content' => $banner->content,
            'render_location' => $banner->render_location,
            'auth_guards' => $banner->auth_guards,
            'scope' => $banner->scope,
            'can_be_closed_by_user' => $banner->can_be_closed_by_user,
            'can_truncate_message' => $banner->can_truncate_message,
            'is_active' => $banner->is_active,
            'active_since' => $banner->active_since,
            'text_color' => $banner->text_color,
            'icon' => $banner->icon,
            'icon_color' => $banner->icon_color,
            'background_type' => $banner->background_type,
            'start_color' => $banner->start_color,
            'end_color' => $banner->end_color,
            'start_time' => $banner->start_time,
            'end_time' => $banner->end_time
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(TwoFactorBanner::class, [
        'name' => $storedData->data['name'],
        'render_location' => $storedData->data['render_location'],
        'can_be_closed_by_user' => $storedData->data['can_be_closed_by_user'],
        'is_active' => $storedData->data['is_active']
    ]);
});

it('can validate create banner form', function () {
    livewire(CreateTwoFactorBanner::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'render_location' => 'required',
            'content' => 'required',
            'auth_guards' => 'required',
        ]);
});

it('can access edit banner page', function () {
    $this->actingAs($this->createUser());
    $banner = TwoFactorBanner::factory()->create();
    $this->get(TwoFactorBannerResource::getUrl('edit', [
        'record' => $banner,
    ]))->assertSuccessful();
});

it('can update banner', function () {
    $bannerNew = TwoFactorBanner::factory()->create();
    $banner = TwoFactorBanner::factory()->make();

    $updatedData = livewire(EditTwoFactorBanner::class, [
        'record' => $bannerNew->getRouteKey(),
    ])->fillForm([
        'name' => $banner->name,
        'content' => $banner->content,
        'render_location' => $banner->render_location,
        'auth_guards' => $banner->auth_guards,
        'scope' => $banner->scope,
        'can_be_closed_by_user' => $banner->can_be_closed_by_user,
        'can_truncate_message' => $banner->can_truncate_message,
        'is_active' => $banner->is_active,
        'active_since' => $banner->active_since,
        'text_color' => $banner->text_color,
        'icon' => $banner->icon,
        'icon_color' => $banner->icon_color,
        'background_type' => $banner->background_type,
        'start_color' => $banner->start_color,
        'end_color' => $banner->end_color,
        'start_time' => $banner->start_time,
        'end_time' => $banner->end_time
    ])
    ->call('save')
    ->assertHasNoFormErrors();

    $this->assertDatabaseHas(TwoFactorBanner::class, [
        'name' => $banner->name,
        'render_location' => $banner->render_location,
        'is_active' => $banner->is_active,
    ]);
});

it('can delete banner', function () {
    $banner = TwoFactorBanner::factory()->create();

    livewire(EditTwoFactorBanner::class, [
        'record' => $banner->getRouteKey(),
    ])->callAction(DeleteAction::class);

    $this->assertModelMissing($banner);
});

it('can delete multiple banners', function () {
    $banners = TwoFactorBanner::factory()->count(4)->create();

    livewire(ListTwoFactorBanners::class)
        ->callTableBulkAction(DeleteBulkAction::class, $banners->pluck('id')->toArray());

    foreach ($banners as $banner) {
        $this->assertModelMissing($banner);
    }
});

it('can disable multiple banners', function () {
    $banners = TwoFactorBanner::factory()->count(4)->create();

    livewire(ListTwoFactorBanners::class)
        ->callTableBulkAction('disableSelected', $banners->pluck('id')->toArray());

    foreach ($banners as $banner) {
        $this->assertDatabaseHas(TwoFactorBanner::class, [
            'id' => $banner->id,
            'is_active' => false,
        ]);
    }
});

it('can enable multiple banners', function () {
    $banners = TwoFactorBanner::factory()->count(4)->create();

    livewire(ListTwoFactorBanners::class)
        ->callTableBulkAction('enableSelected', $banners->pluck('id')->toArray());

    foreach ($banners as $banner) {
        $this->assertDatabaseHas(TwoFactorBanner::class, [
            'id' => $banner->id,
            'is_active' => true,
        ]);
    }
});
