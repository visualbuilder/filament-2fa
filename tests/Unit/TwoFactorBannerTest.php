<?php

use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Tables\Actions\DeleteBulkAction;
use Visualbuilder\Filament2fa\Filament\Resources\BannerResource;
use Visualbuilder\Filament2fa\Filament\Resources\BannerResource\Pages\CreateBanner;
use Visualbuilder\Filament2fa\Filament\Resources\BannerResource\Pages\EditBanner;
use Visualbuilder\Filament2fa\Filament\Resources\BannerResource\Pages\ListBanners;
use Visualbuilder\Filament2fa\Models\Banner;

use function Pest\Livewire\livewire;

it('can access banner page', function () {
    $this->actingAs($this->createUser());
    $this->get(BannerResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list banners', function () {
    $this->actingAs($this->createUser());
    $banners = Banner::factory()->count(5)->create();
    livewire(ListBanners::class)
        ->assertCanSeeTableRecords($banners);
});

it('can access create banner page', function () {
    $this->actingAs($this->createUser());

    $this->get(BannerResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create banner', function () {

    $banner = Banner::factory()->make();

    $storedData = livewire(CreateBanner::class)
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

    $this->assertDatabaseHas(Banner::class, [
        'name' => $storedData->data['name'],
        'render_location' => $storedData->data['render_location'],
        'can_be_closed_by_user' => $storedData->data['can_be_closed_by_user'],
        'is_active' => $storedData->data['is_active']
    ]);
});

it('can validate create banner form', function () {
    livewire(CreateBanner::class)
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
    $banner = Banner::factory()->create();
    $this->get(BannerResource::getUrl('edit', [
        'record' => $banner,
    ]))->assertSuccessful();
});

it('can update banner', function () {
    $bannerNew = Banner::factory()->create();
    $banner = Banner::factory()->make();

    $updatedData = livewire(EditBanner::class, [
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

    $this->assertDatabaseHas(Banner::class, [
        'name' => $banner->name,
        'render_location' => $banner->render_location,
        'is_active' => $banner->is_active,
    ]);
});

it('can delete banner', function () {
    $banner = Banner::factory()->create();

    livewire(EditBanner::class, [
        'record' => $banner->getRouteKey(),
    ])->callAction(DeleteAction::class);

    $this->assertModelMissing($banner);
});

it('can delete multiple banners', function () {
    $banners = Banner::factory()->count(4)->create();

    livewire(ListBanners::class)
        ->callTableBulkAction(DeleteBulkAction::class, $banners->pluck('id')->toArray());

    foreach ($banners as $banner) {
        $this->assertModelMissing($banner);
    }
});

it('can disable multiple banners', function () {
    $banners = Banner::factory()->count(4)->create();

    livewire(ListBanners::class)
        ->callTableBulkAction('disableSelected', $banners->pluck('id')->toArray());

    foreach ($banners as $banner) {
        $this->assertDatabaseHas(Banner::class, [
            'id' => $banner->id,
            'is_active' => false,
        ]);
    }
});

it('can enable multiple banners', function () {
    $banners = Banner::factory()->count(4)->create();

    livewire(ListBanners::class)
        ->callTableBulkAction('enableSelected', $banners->pluck('id')->toArray());

    foreach ($banners as $banner) {
        $this->assertDatabaseHas(Banner::class, [
            'id' => $banner->id,
            'is_active' => true,
        ]);
    }
});
