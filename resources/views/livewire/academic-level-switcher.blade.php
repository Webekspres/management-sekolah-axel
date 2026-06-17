<div class="fi-academic-level-switcher w-56 shrink-0">
    <x-filament::input.wrapper class="w-full">
        @if ($warningMessage)
            <div class="rounded-md border border-warning-300 bg-warning-50 px-3 py-2 text-xs text-warning-800 dark:border-warning-700 dark:bg-warning-950/30 dark:text-warning-200">
                {{ $warningMessage }}
            </div>
        @elseif ($isLockedForStudent)
            <x-filament::input.select class="w-full" disabled>
                @foreach ($levels as $id => $name)
                    <option value="{{ $id }}" @selected($active_academic_level_id === $id)>{{ $name }}</option>
                @endforeach
            </x-filament::input.select>
        @else
            <x-filament::input.select class="w-full" wire:model.live="active_academic_level_id">
                @foreach ($levels as $id => $name)
                    <option value="{{ $id }}" @selected($active_academic_level_id === $id)>{{ $name }}</option>
                @endforeach
            </x-filament::input.select>
        @endif
    </x-filament::input.wrapper>
</div>
