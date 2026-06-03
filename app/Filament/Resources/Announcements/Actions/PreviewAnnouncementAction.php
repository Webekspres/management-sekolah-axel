<?php

namespace App\Filament\Resources\Announcements\Actions;

use App\Models\Announcement;
use App\Support\AnnouncementRichContentPreview;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class PreviewAnnouncementAction
{
    public static function make(): Action
    {
        return Action::make('previewAnnouncement')
            ->label('Pratinjau')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->visible(fn (CreateRecord|EditRecord $livewire): bool => self::canPreview($livewire))
            ->modalHeading(fn (CreateRecord|EditRecord $livewire): string => filled($livewire->data['title'] ?? null)
                ? (string) $livewire->data['title']
                : 'Pratinjau Pengumuman')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalContent(fn (CreateRecord|EditRecord $livewire): HtmlString => new HtmlString(
                AnnouncementRichContentPreview::make(
                    $livewire->data['content'] ?? null,
                    $livewire->data['title'] ?? null,
                )->toHtml(),
            ));
    }

    public static function canPreview(CreateRecord|EditRecord $livewire): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($livewire instanceof CreateRecord) {
            return true;
        }

        $record = $livewire->record;

        if (! $record instanceof Announcement) {
            return false;
        }

        if ($record->isCreatedBy($user)) {
            return true;
        }

        if (blank($record->created_by) && $user->can('update', $record)) {
            return true;
        }

        return false;
    }
}
