<?php

namespace App\Filament\Clusters\Academic\Resources\Schedules\Pages;

use App\Filament\Clusters\Academic\Resources\Schedules\ScheduleResource;
use App\Filament\Clusters\Academic\Resources\Schedules\Widgets\JadwalKalenderWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            JadwalKalenderWidget::class,
        ];
    }
}
