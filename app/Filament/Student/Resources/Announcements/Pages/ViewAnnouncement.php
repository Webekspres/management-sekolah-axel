<?php

namespace App\Filament\Student\Resources\Announcements\Pages;

use App\Filament\Student\Resources\Announcements\AnnouncementResource;
use App\Models\AnnouncementRead;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewAnnouncement extends ViewRecord
{
    protected static string $resource = AnnouncementResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Pengumuman';
    }

    public function getBreadcrumb(): string
    {
        return 'Detail';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToAnnouncements')
                ->label('Kembali')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(fn (): string => AnnouncementResource::getUrl('index')),
        ];
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
