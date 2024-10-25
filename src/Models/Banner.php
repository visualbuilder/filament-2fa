<?php

namespace Visualbuilder\Filament2fa\Models;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Visualbuilder\Filament2fa\Database\Factories\BannerFactory;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'content',
        'render_location',
        'auth_guards',
        'scope',
        'can_be_closed_by_user',
        'can_truncate_message',
        'is_active',
        'is_2fa_setup',
        'active_since',
        'text_color',
        'icon',
        'icon_color',
        'background_type',
        'start_color',
        'end_color',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_2fa_setup' => 'boolean',
        'can_be_closed_by_user' => 'boolean',
        'can_truncate_message' => 'boolean',
        'scope' => 'array',
        'auth_guards' => 'array'
    ];

    protected $table = 'two_factor_banners';

    protected static function booted()
    {
        parent::booted();

        // When a banner is created or updated
        static::saved(function ($banner) {
            Cache::forget('all_banners');
        });

        // When a banner is deleted
        static::deleted(function ($banner) {
            Cache::forget('all_banners');
        });
    }


    /**
     * @return BannerFactory
     */
    protected static function newFactory()
    {
        return BannerFactory::new();
    }


    public function isVisible(): bool
    {
        if ($this->is_active && $this->canViewBasedOnSchedule()) {
            return true;
        }

        return false;
    }

    public function canViewBasedOnSchedule(): bool
    {
        $start_time = Carbon::parse($this->start_time);
        $end_time = Carbon::parse($this->end_time);

        if ($this->hasNoScheduleSet()) {
            return true;
        }

        if ($this->hasOnlyEndTime() && now() < $end_time) {
            return true;
        }

        if ($this->hasOnlyStartTime() && now() > $start_time) {
            return true;
        }

        if ($this->hasSchedule() & $start_time < now() && now() < $end_time) {
            return true;
        }

        return false;
    }

    public function hasNoScheduleSet(): bool
    {
        return is_null($this->start_time) && is_null($this->end_time);
    }

    public function hasOnlyStartTime(): bool
    {
        return ! is_null($this->start_time) && is_null($this->end_time);
    }

    public function hasOnlyEndTime(): bool
    {
        return ! is_null($this->end_time) && is_null($this->start_time);
    }

    public function hasSchedule(): bool
    {
        return ! is_null($this->start_time) && ! is_null($this->end_time);
    }

    public function getLocation(): string
    {
        return match ($this->render_location) {
            PanelsRenderHook::BODY_START => 'body',
            PanelsRenderHook::PAGE_START, PanelsRenderHook::PAGE_END => 'panel',
            PanelsRenderHook::SIDEBAR_NAV_START, PanelsRenderHook::SIDEBAR_NAV_END => 'nav',
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE, PanelsRenderHook::GLOBAL_SEARCH_AFTER => 'global_search',
            default => ''
        };
    }

    public function isApplicableForUser($user)
    {
        return (!$this->is_2fa_setup
            || ($this->is_2fa_setup && $user instanceof TwoFactorAuthenticatable && !$user->hasTwoFactorEnabled()));
    }

    public function checkGuard()
    {
        return !$this->auth_guards || ($this->auth_guards && in_array(Filament::getAuthGuard(), $this->auth_guards));
    }
}
