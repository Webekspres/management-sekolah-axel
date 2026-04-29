<?php

namespace App\Livewire;

use App\Models\Level;
use Illuminate\View\View;
use Livewire\Component;

class AcademicLevelSwitcher extends Component
{
    public ?string $active_academic_level_id = null;

    public function mount(): void
    {
        $this->active_academic_level_id = session('active_academic_level_id');

        if (! $this->active_academic_level_id) {
            $firstLevel = Level::first();
            if ($firstLevel) {
                $this->active_academic_level_id = $firstLevel->id;
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
        $levels = Level::pluck('name', 'id');

        return view('livewire.academic-level-switcher', compact('levels'));
    }
}
