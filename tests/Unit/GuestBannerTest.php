<?php

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Http\Request;
use Visualbuilder\Filament2fa\Http\Middleware\SetRenderLocation;
use Visualbuilder\Filament2fa\Models\Banner;

it('shows a guest banner on the login page to signed-out visitors', function () {
    Banner::factory()->create([
        'content' => 'Guest maintenance notice',
        'render_location' => PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
        'is_2fa_setup' => false,
        'show_to_guests' => true,
    ]);

    $this->get(Filament::getLoginUrl())
        ->assertSuccessful()
        ->assertSee('Guest maintenance notice');
});

it('shows a guest banner in the body start hook on the login page', function () {
    Banner::factory()->create([
        'content' => 'Body start guest notice',
        'render_location' => PanelsRenderHook::BODY_START,
        'is_2fa_setup' => false,
        'show_to_guests' => true,
    ]);

    $this->get(Filament::getLoginUrl())
        ->assertSuccessful()
        ->assertSee('Body start guest notice');
});

it('hides banners not opted in to guests from signed-out visitors', function () {
    Banner::factory()->create([
        'content' => 'Signed-in only notice',
        'render_location' => PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
        'is_2fa_setup' => false,
        'show_to_guests' => false,
    ]);

    $this->get(Filament::getLoginUrl())
        ->assertSuccessful()
        ->assertDontSee('Signed-in only notice');
});

it('never shows 2fa setup banners to signed-out visitors', function () {
    Banner::factory()->create([
        'content' => 'Set up 2fa notice',
        'render_location' => PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
        'is_2fa_setup' => true,
        'show_to_guests' => true,
    ]);

    $this->get(Filament::getLoginUrl())
        ->assertSuccessful()
        ->assertDontSee('Set up 2fa notice');
});

it('hides guest banners on panels whose guard the banner does not target', function () {
    Banner::factory()->create([
        'content' => 'Other guard notice',
        'render_location' => PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
        'auth_guards' => ['admin'],
        'is_2fa_setup' => false,
        'show_to_guests' => true,
    ]);

    $this->get(Filament::getLoginUrl())
        ->assertSuccessful()
        ->assertDontSee('Other guard notice');
});

it('still shows banners not opted in to guests to signed-in users', function () {
    $user = $this->createUser();
    $this->actingAs($user);
    Banner::factory()->create([
        'content' => 'Signed-in body notice',
        'render_location' => PanelsRenderHook::BODY_START,
        'is_2fa_setup' => false,
        'show_to_guests' => false,
    ]);

    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);
    (new SetRenderLocation)->handle($request, fn () => response(''));

    expect(FilamentView::renderHook(PanelsRenderHook::BODY_START)->toHtml())
        ->toContain('Signed-in body notice');
});
