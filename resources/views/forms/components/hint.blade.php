<div class="text-sm text-gray-500 dark:text-gray-400">
    {{ __('filament-2fa::two-factor.confirm_otp_hint', ['otpLength' => config('two-factor.totp.digits'), 'recoveryLength' => config('two-factor.recovery.length')]) }}
</div>