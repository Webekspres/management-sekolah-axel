<?php

namespace App\Filament\Clusters\DataPersonalia\Resources\Teachers;

use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Tables\TeachersTable;
use App\Models\Teacher;
use App\Models\User;
use App\Support\TemporaryAccessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $cluster = null;

    protected static string|\UnitEnum|null $navigationGroup = 'Data Personalia';

    protected static ?string $label = 'Guru';

    protected static ?string $pluralLabel = 'Daftar Guru';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nip';

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
            ->hasTemporaryPolicyGrant($user, 'viewAny', Teacher::class);
    }

    public static function form(Schema $schema): Schema
    {
        return TeacherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeachersTable::configure($table);
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
            'index' => ListTeachers::route('/'),
            'create' => CreateTeacher::route('/create'),
            'edit' => EditTeacher::route('/{record}/edit'),
        ];
    }
}
