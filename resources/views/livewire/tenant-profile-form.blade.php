<div>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-end mt-6">
            <x-filament::button type="submit">
                {{ __('filament.save') }}
            </x-filament::button>
        </div>
    </form>
</div>
