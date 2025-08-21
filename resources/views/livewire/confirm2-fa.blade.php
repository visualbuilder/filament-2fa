<x-filament-panels::page.simple>
    <div x-data="{ isSubmitting: @entangle('isSubmitting') }">
        <form wire:submit.prevent="submit">
            {{ $this->form }}
            <div class="mt-3 text-end">
                <x-filament::button type="submit" x-bind:disabled="isSubmitting">
                    <div class="flex items-center">
                        <template x-if="isSubmitting">
                            <div class="flex items-center">
                                <x-filament::loading-indicator class="h-5 w-5 mr-2"/>
                                <span>Verifying 2FA...</span>
                            </div>
                        </template>
                        <template x-if="!isSubmitting">
                            <span>Submit</span>
                        </template>
                    </div>
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page.simple>
