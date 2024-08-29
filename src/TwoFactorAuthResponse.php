<?php

namespace Optimacloud\Filament2fa;

use Filament\Http\Responses\Auth\LoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class TwoFactorAuthResponse extends LoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector   
    {
        return redirect()->route('2fa.validate');
    }
}
