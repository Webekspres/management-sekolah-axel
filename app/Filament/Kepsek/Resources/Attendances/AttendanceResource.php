<?php

namespace App\Filament\Kepsek\Resources\Attendances;

use App\Filament\Kepsek\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Kepsek\Resources\Attendances\Tables\AttendancesTable;
use App\Models\Attendance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $cluster = null;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $label = 'Absensi';

    protected static ?string $pluralLabel = 'Data Absensi';

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'kbm.schedule.schoolClass',
                'kbm.schedule.subjectForDisplay',
                'kbm.schedule.teacher.user',
            ])
            ->with(['student' => fn ($q) => $q->withoutGlobalScopes()->with('user')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendances::route('/'),
        ];
    }
}
