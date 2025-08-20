<?php

namespace Visualbuilder\Filament2fa;


use Filament\Auth\Http\Responses\LoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class TwoFactorAuthResponse extends LoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->route('2fa.validate');
    }
}
