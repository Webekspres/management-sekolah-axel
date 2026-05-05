<?php

namespace App\Filament\Bento;

use Filament\Pages\Dashboard;

/**
 * Dashboard dengan grid 12 kolom (desktop) agar columnSpan widget membentuk layout bento konsisten.
 */
class BentoDashboard extends Dashboard
{
    /**
     * @return array<string, int | null> | int
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 12,
            'md' => 12,
            'lg' => 12,
            'xl' => 12,
        ];
    }
}
