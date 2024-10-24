<?php

namespace Visualbuilder\Filament2fa\Enums;

enum ScheduleStatus: string implements \Filament\Support\Contracts\HasLabel
{
    case Due = 'due';
    case Visible = 'visible';
    case Fulfilled = 'fulfilled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Due => 'Due',
            self::Visible => 'Visible',
            self::Fulfilled => 'Fulfilled',
        };
    }
}
