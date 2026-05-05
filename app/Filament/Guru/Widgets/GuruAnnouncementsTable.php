<?php

namespace App\Filament\Guru\Widgets;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GuruAnnouncementsTable extends TableWidget
{
    protected static ?int $sort = 10;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 6,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'guru';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pengumuman untuk guru')
            ->query(fn (): Builder => Announcement::query()
                ->whereJsonContains('target_role', 'guru')
                ->latest('created_at'))
            ->columns([
                TextColumn::make('title')->label('Judul')->limit(60),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Buka')
                    ->url(fn (Announcement $record): string => AnnouncementResource::getUrl('edit', ['record' => $record], panel: 'guru')),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
