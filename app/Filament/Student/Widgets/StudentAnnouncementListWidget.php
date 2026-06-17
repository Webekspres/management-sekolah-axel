<?php

namespace App\Filament\Student\Widgets;

use App\Filament\Student\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class StudentAnnouncementListWidget extends TableWidget
{
    protected static ?int $sort = 11;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->role === 'siswa_ortu';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pengumuman')
            ->description('Tandai dibaca untuk menyimpan status Anda')
            ->query(fn () => Announcement::query()
                ->whereJsonContains('target_role', 'siswa_ortu')
                ->latest('created_at'))
            ->columns([
                IconColumn::make('read')
                    ->label('')
                    ->boolean()
                    ->getStateUsing(fn (Announcement $record): bool => $record->isRead()),
                TextColumn::make('title')->label('Judul')->limit(70)->searchable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Buka')
                    ->url(fn (Announcement $record): string => AnnouncementResource::getUrl('view', ['record' => $record], panel: 'student')),
                Action::make('markRead')
                    ->label('Tandai dibaca')
                    ->visible(fn (Announcement $record): bool => ! $record->isRead())
                    ->action(function (Announcement $record): void {
                        AnnouncementRead::firstOrCreate([
                            'announcement_id' => $record->id,
                            'user_id' => (string) Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Pengumuman ditandai dibaca')
                            ->success()
                            ->send();

                        $this->resetTable();
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
