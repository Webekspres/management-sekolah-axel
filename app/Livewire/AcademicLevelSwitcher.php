<?php

namespace App\Livewire;

use App\Models\Level;
use Livewire\Component;

class AcademicLevelSwitcher extends Component
{
    public ?string $active_academic_level_id = null;

    public function mount()
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

    public function updatedActiveAcademicLevelId($value)
    {
        session(['active_academic_level_id' => $value]);
        $this->redirect(request()->header('Referer') ?? '/');
    }

    public function render()
    {
        $levels = Level::pluck('name', 'id');

        return view('livewire.academic-level-switcher', compact('levels'));
    }
}
