<?php

namespace App\Filament\Kepsek\Resources\Rapors;

use App\Enums\UserRole;
use App\Filament\Kepsek\Resources\Rapors\Pages\ListRapors;
use App\Filament\Kepsek\Resources\Rapors\Tables\RaporsTable;
use App\Models\Rapor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RaporResource extends Resource
{
    protected static ?string $model = Rapor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static ?string $label = 'Approval Rapor';

    protected static ?string $pluralLabel = 'Approval Rapor';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasUserRole(UserRole::KepalaSekolah, UserRole::SuperAdmin) ?? false;
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

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return RaporsTable::configureForKepsek($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student.user', 'academicYear']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRapors::route('/'),
        ];
    }
}
