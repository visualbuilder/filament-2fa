@php
    $twoFa = $this->prepareTwoFactor();
@endphp

<div class="flex items-center justify-center">
    {!! $twoFa['qr'] !!}
</div>

<p class="mt-3 text-xs text-gray-500">
    {{ __('If you can’t scan the QR, add this account manually using the secret:') }}
    <code class="font-mono">{{ $twoFa['secret'] }}</code>
</p>
