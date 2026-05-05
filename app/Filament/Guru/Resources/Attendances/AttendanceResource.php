<?php

namespace App\Filament\Guru\Resources\Attendances;

use App\Filament\Guru\Resources\Attendances\Pages\EditAttendance;
use App\Filament\Guru\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Guru\Resources\Attendances\Schemas\AttendanceForm;
use App\Filament\Guru\Resources\Attendances\Tables\AttendancesTable;
use App\Models\Attendance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
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

    public static function form(Schema $schema): Schema
    {
        return AttendanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'kbm.schedule.schoolClass',
            'kbm.schedule.subjectForDisplay',
        ]);

        $user = auth()->user();

        if (! $user?->teacher) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereHas('kbm.schedule', fn (Builder $q) => $q->where('teacher_id', $user->teacher->id))
            ->with(['student' => fn ($q) => $q->withoutGlobalScopes()->with('user')]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendances::route('/'),
            'edit' => EditAttendance::route('/{record}/edit'),
        ];
    }
}
