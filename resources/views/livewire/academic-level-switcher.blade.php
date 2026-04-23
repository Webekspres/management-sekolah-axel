<div class="relative flex items-center" style="min-width: 150px;">
    <x-filament::input.wrapper>
        <x-filament::input.select wire:model.live="active_academic_level_id">
            @foreach($levels as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </x-filament::input.select>
    </x-filament::input.wrapper>
    
    <style>
        /* Sembunyikan global search bawaan filament */
        .fi-topbar-global-search {
            display: none !important;
        }
    </style>
</div>
