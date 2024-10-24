<?php

namespace Visualbuilder\Filament2fa\Database\Factories;

use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Visualbuilder\Filament2fa\Models\Banner;

class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition()
    {
        return [
            'name' => fake()->title(),
            'content' => fake()->text(),
            'render_location' => PanelsRenderHook::BODY_START,
            'auth_guards' => ['web'],
            'scope' => null,
            'can_be_closed_by_user' => false,
            'can_truncate_message' => false,
            'is_active' => true,
            'is_2fa_setup'=>true,
            'active_since' => now(),
            'text_color' => "#FF0000",
            'icon' => "academic-cap",
            'icon_color' => "#FF0000",
            'background_type' => 'solid',
            'start_color' => "#FF0000",
            'end_color' => null,
            'start_time' => null,
            'end_time' => null,
        ];
    }
}
