<?php

namespace App\Filament\Clusters\Academic\Resources\Attendances;

use App\Filament\Clusters\Academic\Resources\Attendances\Pages\CreateAttendance;
use App\Filament\Clusters\Academic\Resources\Attendances\Pages\EditAttendance;
use App\Filament\Clusters\Academic\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Clusters\Academic\Resources\Attendances\Schemas\AttendanceForm;
use App\Filament\Clusters\Academic\Resources\Attendances\Tables\AttendancesTable;
use App\Models\Attendance;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChalkboardTeacher;

    protected static ?string $cluster = null;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $label = 'Absensi';

    protected static ?string $pluralLabel = 'Data Absensi';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->role === 'super_admin';
    }

    public static function form(Schema $schema): Schema
    {
        return AttendanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendances::route('/'),
            'create' => CreateAttendance::route('/create'),
            'edit' => EditAttendance::route('/{record}/edit'),
        ];
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
}
