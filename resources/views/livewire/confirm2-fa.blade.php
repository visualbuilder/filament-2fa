<x-filament-panels::page.simple>
    <div
        x-data="{
            isSubmittingLocal: false,
            totpDigits: {{ (int) config('two-factor.totp.digits', 6) }},
            recoveryLen: {{ (int) config('two-factor.recovery.length', 8) }},
            init() { Livewire.hook('message.processed', () => { this.isSubmittingLocal = false }) },
            handleOtpInput(v) {
                if (!v) return;
                const len = v.length;
                const isTotp = len === this.totpDigits && /^\d+$/.test(v);
                const isRecovery = len === this.recoveryLen && /[a-zA-Z]/.test(v);
                if (!(isTotp || isRecovery)) return;

                if (!this.isSubmittingLocal) this.isSubmittingLocal = true;
                $wire.set('data.totp_code', v).then(() => $wire.submit());
            }
        }"
    >
        <form wire:submit.prevent="submit" class="space-y-4">
            {{ $this->form }}

            {{-- Reserve height, but only SHOW the spinner when verifying --}}
            <div class="mt-6 h-6 relative">
                <div
                    x-cloak
                    x-show="isSubmittingLocal"
                    x-transition.opacity.duration.150ms
                    class="absolute inset-0 flex items-center gap-2 text-sm font-medium
                           text-primary-600 dark:text-primary-400"
                    role="status" aria-live="polite"
                >
                    <x-filament::loading-indicator class="h-5 w-5" />
                    <span>{{ __('Verifying 2FA…') }}</span>
                </div>
            </div>
        </form>
    </div>
</x-filament-panels::page.simple>
