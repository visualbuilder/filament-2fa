<?php

use Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource;
use Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages\CreateTwoFactorBanner;
use Optimacloud\Filament2fa\Filament\Resources\TwoFactorBannerResource\Pages\EditTwoFactorBanner;
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
        ->assertCanSeeTableRecords($banners, inOrder: true);
});

it('can access create banner page', function () {
    $this->actingAs(
        $this->createUser()
    )->get(TwoFactorBannerResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create admin user', function () {
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
        'content' => $storedData->data['content'],
    ]);
});

it('can update banner', function () {
    $admin = TwoFactorBanner::factory()->withPrimaryContact([
        'salutation' => $this->faker->title,
        'first_name' => $this->faker->firstName,
        'last_name'  => $this->faker->lastName,
        'mobile'     => '',
        'phone'      => '',
        'pronouns'   => 'he/him',
    ])->create();

    $contact = TwoFactorBanner::factory()->make();

    $updatedData = livewire(EditTwoFactorBanner::class, [
        'record' => $admin->getRouteKey(),
    ])->fillForm([
        'primaryContact.first_name' => $contact->first_name,
        'primaryContact.last_name' => $contact->last_name,
        'primaryContact.avatar' => $file,
        'email' => $contact->email,
        'role' => $randomRole,
        'primaryLocation.address_1' => $location->address_1,
        'primaryLocation.address_2' => $location->address_2,
        'primaryLocation.address_3' => $location->address_3,
        'primaryLocation.city' => $location->city,
        'primaryLocation.county' => $location->county,
        'primaryLocation.postcode' => $location->postcode,
        'primaryLocation.country_id' => $location->country_id,
    ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(TwoFactorBanner::class, [
        'address_1' => $location->address_1,
        'address_2' => $location->address_2,
        'address_3' => $location->address_3,
        'city' => $location->city,
        'county' => $location->county,
        'postcode' => $location->postcode,
        'country_id' => $location->country_id,
    ]);
});