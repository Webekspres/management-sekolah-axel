<?php

namespace App\Filament\Student\Resources\Announcements\Pages;

use App\Filament\Student\Resources\Announcements\AnnouncementResource;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
