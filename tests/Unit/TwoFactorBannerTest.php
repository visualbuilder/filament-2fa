<?php

use Filament\Facades\Filament;
use Optimacloud\Filament2fa\Livewire\TwoFactorBanner as LivewireTwoFactorBanner;
use Optimacloud\Filament2fa\Models\TwoFactorBanner;

use function Pest\Livewire\livewire;

it('check banner page access', function () {
    $this
        ->get(url(config('filament-2fa.2fa_banner_url')))
        ->assertSuccessful();
});

it('can create or update banner', function () {
    
    $banner = TwoFactorBanner::factory()->make();
    
    $storedData = livewire(LivewireTwoFactorBanner::class)
        ->assertFormExists()
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
        ->call('submit')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(TwoFactorBanner::class, [
        'name' => $storedData->data['name'],
        'content' => $storedData->data['content'],
    ]);
});