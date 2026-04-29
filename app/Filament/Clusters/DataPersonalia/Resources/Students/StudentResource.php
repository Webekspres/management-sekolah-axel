<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Students;

use App\Filament\Clusters\DataPersonalia\Resources\Students\Pages\CreateStudent;
use App\Filament\Clusters\DataPersonalia\Resources\Students\Pages\EditStudent;
use App\Filament\Clusters\DataPersonalia\Resources\Students\Pages\ListStudents;
use App\Filament\Clusters\DataPersonalia\Resources\Students\Schemas\StudentForm;
use App\Filament\Clusters\DataPersonalia\Resources\Students\Tables\StudentsTable;
use App\Models\Student;
use App\Models\User;
use App\Support\TemporaryAccessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = null;

    protected static UnitEnum|string|null $navigationGroup = 'Data Personalia';

    protected static ?string $label = 'Siswa';

    protected static ?string $recordTitleAttribute = 'nipd';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        return app(TemporaryAccessManager::class)
            ->hasTemporaryPolicyGrant($user, 'viewAny', Student::class);
    }

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
