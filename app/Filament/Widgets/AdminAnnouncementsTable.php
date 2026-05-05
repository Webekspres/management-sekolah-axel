<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AdminAnnouncementsTable extends TableWidget
{
    protected static ?int $sort = 7;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 6,
        'xl' => 6,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pengumuman untuk admin')
            ->description('Target super_admin atau kepala sekolah')
            ->query(fn (): Builder => Announcement::query()
                ->where(function ($query): void {
                    $query->whereJsonContains('target_role', 'super_admin')
                        ->orWhereJsonContains('target_role', 'kepala_sekolah');
                })
                ->latest('created_at'))
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Buka')
                    ->url(fn (Announcement $record): string => AnnouncementResource::getUrl('edit', ['record' => $record], panel: 'admin')),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
