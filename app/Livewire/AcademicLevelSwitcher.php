<?php

namespace App\Livewire;

use App\Models\Level;
use App\Models\UserPolicyAbility;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class AcademicLevelSwitcher extends Component
{
    public ?string $active_academic_level_id = null;

    public function mount(): void
    {
        $allowedLevels = $this->getAllowedLevels();

        $this->active_academic_level_id = session('active_academic_level_id');

        // If current session level is not in allowed levels, reset to first allowed
        if ($allowedLevels !== null && ! $allowedLevels->has($this->active_academic_level_id)) {
            $this->active_academic_level_id = $allowedLevels->keys()->first();
            session(['active_academic_level_id' => $this->active_academic_level_id]);
        }

        if (! $this->active_academic_level_id) {
            $firstLevel = ($allowedLevels ?? Level::pluck('name', 'id'))->keys()->first();
            if ($firstLevel) {
                $this->active_academic_level_id = $firstLevel;
                session(['active_academic_level_id' => $this->active_academic_level_id]);
            }
        }
    }

    public function updatedActiveAcademicLevelId(string $value): void
    {
        session(['active_academic_level_id' => $value]);
        $this->redirect(request()->header('Referer') ?? '/');
    }

    public function render(): View
    {
        $allowedLevels = $this->getAllowedLevels();
        $levels = $allowedLevels ?? Level::pluck('name', 'id');
        $showSwitcher = $allowedLevels === null || $allowedLevels->count() > 1;

        return view('livewire.academic-level-switcher', compact('levels', 'showSwitcher'));
    }

    /**
     * Returns the levels this user is allowed to switch to based on temporary access.
     * Returns null if user has permanent role access (no restriction).
     * Returns a Collection if user only has temporary access (restricted to assigned levels).
     *
     * @return Collection<string, string>|null
     */
    private function getAllowedLevels(): ?Collection
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        // Users with permanent roles see all levels
        if (in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true)) {
            return null;
        }

        // For users with only temporary access, restrict to assigned levels
        $assignedLevelIds = UserPolicyAbility::query()
            ->forUser($user->id)
            ->direct()
            ->notExpired()
            ->whereNotNull('level_id')
            ->pluck('level_id')
            ->unique()
            ->values();

        if ($assignedLevelIds->isEmpty()) {
            // Has temporary access but no level restriction — show all levels
            $hasTempAccess = UserPolicyAbility::query()
                ->forUser($user->id)
                ->direct()
                ->notExpired()
                ->exists();

            return $hasTempAccess ? null : null;
        }

        return Level::whereIn('id', $assignedLevelIds)
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
