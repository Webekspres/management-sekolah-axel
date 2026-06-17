<?php

namespace App\Filament\Clusters\Academic\Resources\Grades;

use App\Filament\Clusters\Academic\AcademicCluster;
use App\Filament\Clusters\Academic\Resources\Grades\Pages\CreateGrade;
use App\Filament\Clusters\Academic\Resources\Grades\Pages\EditGrade;
use App\Filament\Clusters\Academic\Resources\Grades\Pages\ListGrades;
use App\Filament\Clusters\Academic\Resources\Grades\Schemas\GradeForm;
use App\Filament\Clusters\Academic\Resources\Grades\Tables\GradesTable;
use App\Models\Grade;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $cluster = AcademicCluster::class;

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $label = 'Nilai';

    protected static ?string $pluralLabel = 'Data Nilai';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Grade::class) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return GradeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GradesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student.user', 'subject', 'academicYear']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrades::route('/'),
            'create' => CreateGrade::route('/create'),
            'edit' => EditGrade::route('/{record}/edit'),
        ];
    }
}
