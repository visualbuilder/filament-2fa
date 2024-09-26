<?php

namespace Optimacloud\Filament2fa\Database\Factories;

use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Optimacloud\Filament2fa\Models\TwoFactorBanner;

class TwoFactorBannerFactory extends Factory
{
    protected $model = TwoFactorBanner::class;

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
