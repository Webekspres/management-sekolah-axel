<?php

namespace App\Filament\Clusters\Academic\Resources\AcademicYears;

use App\Filament\Clusters\Academic\Resources\AcademicYears\Pages\CreateAcademicYear;
use App\Filament\Clusters\Academic\Resources\AcademicYears\Pages\EditAcademicYear;
use App\Filament\Clusters\Academic\Resources\AcademicYears\Pages\ListAcademicYears;
use App\Filament\Clusters\Academic\Resources\AcademicYears\Schemas\AcademicYearForm;
use App\Filament\Clusters\Academic\Resources\AcademicYears\Tables\AcademicYearsTable;
use App\Filament\Concerns\AuthorizesResourceAccessWithTemporaryGrant;
use App\Models\AcademicYear;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AcademicYearResource extends Resource
{
    use AuthorizesResourceAccessWithTemporaryGrant;

    protected static ?string $model = AcademicYear::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = null;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?string $label = 'Tahun Ajaran';

    protected static ?string $pluralLabel = 'Daftar Tahun Ajaran';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AcademicYearForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcademicYearsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcademicYears::route('/'),
            'create' => CreateAcademicYear::route('/create'),
            'edit' => EditAcademicYear::route('/{record}/edit'),
        ];
    }
}
