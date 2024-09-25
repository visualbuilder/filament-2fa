<?php

use Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource;
use Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages\ListTwoFactorBanners;
use Optimacloud\Filament2fa\Models\TwoFactorBanner;

use function Pest\Livewire\livewire;

it('can access banner page by user', function () {
    $this->actingAs(
        $this->createUser()
    )->get(TwoFactorBannerResource::getUrl())->assertSuccessful();
});

it('can list all banners', function () {
    $banners = TwoFactorBanner::factory()->count(5)->create();

    livewire(ListTwoFactorBanners::class)
        ->sortTable('id', 'desc')
        ->assertCanSeeTableRecords($banners->sortByDesc('id'), inOrder: true);
});