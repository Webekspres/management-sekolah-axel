<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\Actions\PreviewAnnouncementAction;
use App\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    public function getBreadcrumb(): string
    {
        return 'Edit Pengumuman';
    }

    protected function getHeaderActions(): array
    {
        return [
            PreviewAnnouncementAction::make(),
            DeleteAction::make(),
        ];
    }
}
