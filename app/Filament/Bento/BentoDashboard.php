<?php

namespace App\Filament\Bento;

use Filament\Pages\Dashboard;

class BentoDashboard extends Dashboard
{
    /**
     * @return array<string, int | null> | int
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }
}
