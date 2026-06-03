<?php

namespace App\Services;

use App\Models\Level;
use Illuminate\Support\Collection;

class LevelDefaultSppService
{
    /**
     * @return Collection<int, Level>
     */
    public function levelsOrderedByName(): Collection
    {
        return Level::query()->orderedForDisplay()->get();
    }

    /**
     * @return array{levels: array<string, float|null>}
     */
    public function formDefaults(): array
    {
        $levels = [];

        foreach ($this->levelsOrderedByName() as $level) {
            $levels[$level->id] = $level->default_spp !== null
                ? (float) $level->default_spp
                : null;
        }

        return ['levels' => $levels];
    }

    /**
     * @param  array<string, mixed>  $levels
     */
    public function updateFromFormData(array $levels): void
    {
        $validIds = Level::query()->pluck('id');

        foreach ($levels as $levelId => $amount) {
            if (! $validIds->contains($levelId)) {
                continue;
            }

            Level::query()
                ->whereKey($levelId)
                ->update(['default_spp' => max(0, (float) $amount)]);
        }
    }
}
