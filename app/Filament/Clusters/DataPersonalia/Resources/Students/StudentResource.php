<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students;

use App\Filament\Clusters\DataPersonalia\Resources\Students\Pages\CreateStudent;
use App\Filament\Clusters\DataPersonalia\Resources\Students\Pages\EditStudent;
use App\Filament\Clusters\DataPersonalia\Resources\Students\Pages\ListStudents;
use App\Filament\Clusters\DataPersonalia\Resources\Students\Schemas\StudentForm;
use App\Filament\Clusters\DataPersonalia\Resources\Students\Tables\StudentsTable;
use App\Filament\Concerns\AuthorizesResourceAccessWithTemporaryGrant;
use App\Models\Student;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StudentResource extends Resource
{
    use AuthorizesResourceAccessWithTemporaryGrant;

    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $cluster = null;

    protected static UnitEnum|string|null $navigationGroup = 'Data Personalia';

    protected static ?string $label = 'Siswa';

    protected static ?string $recordTitleAttribute = 'nipd';

    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
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
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
