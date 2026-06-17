<?php

namespace App\Filament\Clusters\Academic\Resources\Schedules\Pages;

use App\Filament\Clusters\Academic\Resources\Schedules\ScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchedule extends EditRecord
{
    protected static string $resource = ScheduleResource::class;

    public function getTitle(): string
    {
        return 'Ubah Jadwal';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
