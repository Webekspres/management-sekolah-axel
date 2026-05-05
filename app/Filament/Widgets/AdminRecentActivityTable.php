<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use App\Support\RichText;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AdminRecentActivityTable extends TableWidget
{
    protected static ?int $sort = 31;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 6,
    ];

    public static function canView(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Aktivitas terbaru')
            ->query(fn (): Builder => ActivityLog::query()->with('user')->latest('created_at'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M H:i'),
                TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->limit(18)
                    ->default('—'),
                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->color('gray')
                    ->limit(22),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->formatStateUsing(fn (?string $state): string => RichText::excerpt($state, 140))
                    ->tooltip(fn (ActivityLog $record): string => RichText::toPlainText($record->description))
                    ->lineClamp(2),
            ])
            ->striped()
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
