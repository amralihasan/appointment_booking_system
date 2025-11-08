<div class="fi-resource-relation-manager flex flex-col gap-y-6">
    <x-filament-panels::form
        wire:submit="save"
    >
        {{ $this->form }}

        <div class="fi-form-actions flex items-center justify-end gap-x-3">
            <x-filament::button
                type="submit"
                color="primary"
            >
                Save
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</div>

