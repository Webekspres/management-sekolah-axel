<?php

namespace App\Filament\Clusters\Academic\Resources\Schedules;

use App\Filament\Clusters\Academic\AcademicCluster;
use App\Filament\Clusters\Academic\Resources\Schedules\Pages\CreateSchedule;
use App\Filament\Clusters\Academic\Resources\Schedules\Pages\EditSchedule;
use App\Filament\Clusters\Academic\Resources\Schedules\Pages\ListSchedules;
use App\Filament\Clusters\Academic\Resources\Schedules\Schemas\ScheduleForm;
use App\Filament\Clusters\Academic\Resources\Schedules\Tables\SchedulesTable;
use App\Models\Schedule;
use App\Models\User;
use App\Support\TemporaryAccessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $cluster = AcademicCluster::class;

    protected static ?string $label = 'Jadwal Pelajaran';

    protected static ?string $pluralLabel = 'Jadwal Pelajaran';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, ['super_admin', 'kepala_sekolah', 'guru'], true)) {
            return true;
        }

        return app(TemporaryAccessManager::class)
            ->hasTemporaryPolicyGrant($user, 'viewAny', Schedule::class);
    }

    public static function form(Schema $schema): Schema
    {
        return ScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchedulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchedules::route('/'),
            'create' => CreateSchedule::route('/create'),
            'edit' => EditSchedule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User $user */
        $user = auth()->user();

        if ($user && $user->role === 'guru' && $user->teacher) {
            $query->where('teacher_id', $user->teacher->id);
        }

        return $query;
    }
}
