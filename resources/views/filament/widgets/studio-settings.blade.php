<x-filament-widgets::widget>
    <x-filament::section :heading="__('starter.studio_settings')">
        <form wire:submit="save">
            <label for="studio-name">{{ __('starter.studio_name') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input id="studio-name" wire:model="studioName" required maxlength="100" />
            </x-filament::input.wrapper>
            @error('studioName') <p role="alert">{{ $message }}</p> @enderror
            <label for="contact-email">{{ __('starter.contact_email') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input id="contact-email" type="email" wire:model="contactEmail" required maxlength="254" />
            </x-filament::input.wrapper>
            @error('contactEmail') <p role="alert">{{ $message }}</p> @enderror
            <x-filament::button type="submit">{{ __('starter.save') }}</x-filament::button>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
