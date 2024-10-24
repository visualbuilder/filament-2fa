<?php

namespace Database\Seeders;

use Filament\View\PanelsRenderHook;
use Illuminate\Database\Seeder;
use Visualbuilder\Filament2fa\Models\Banner;

class TwoFactorBannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // You can adjust the count to create as many entries as you need
        Banner::factory()->create([
            'name'                  => 'Admin 2FA Setup',
            'content'               => '<p>Improve security and setup 2 Factor Authentication.&nbsp; <a href="/two-factor-authentication"><span style="text-decoration: underline;">Click here to get started with your phone now.</span></a></p>',
            'render_location'       => PanelsRenderHook::BODY_START,
            'auth_guards'           => array_keys(config('filament-2fa.auth_guards')),
            'scope'                 => null,
            'can_be_closed_by_user' => true,
            'can_truncate_message'  => false,
            'is_active'             => true,
            'is_2fa_setup'          => true,
            'active_since'          => now(),
            'text_color'            => "#fafafa",
            'icon'                  => "heroicon-o-shield-exclamation",
            'icon_color'            => "#e7b600",
            'background_type'       => 'gradient',
            'start_color'           => "#381fa8",
            'end_color'             => '#4c2ed1',
            'start_time'            => null,
            'end_time'              => null,
        ]);
    }
}
