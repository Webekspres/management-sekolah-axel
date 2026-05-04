<?php

namespace App\Filament\Student\Resources\Announcements\Pages;

use App\Filament\Student\Resources\Announcements\AnnouncementResource;
use App\Models\AnnouncementRead;
use Filament\Resources\Pages\ViewRecord;

class ViewAnnouncement extends ViewRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        AnnouncementRead::firstOrCreate([
            'announcement_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);
    }
}
