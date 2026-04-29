<?php

namespace App\Filament\Clusters\Academic\Resources\Kbms;

use App\Filament\Clusters\Academic\Resources\Kbms\Pages\CreateKbm;
use App\Filament\Clusters\Academic\Resources\Kbms\Pages\EditKbm;
use App\Filament\Clusters\Academic\Resources\Kbms\Pages\ListKbms;
use App\Filament\Clusters\Academic\Resources\Kbms\Schemas\KbmForm;
use App\Filament\Clusters\Academic\Resources\Kbms\Tables\KbmsTable;
use App\Models\Kbm;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class KbmResource extends Resource
{
    protected static ?string $model = Kbm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $cluster = null;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $label = 'Laporan KBM';

    protected static ?string $pluralLabel = 'Laporan KBM';

    // Admin-only view: super_admin only.
    // Guru uses Filament/Guru/Resources/Kbms, Kepsek uses Filament/Kepsek/Resources/Kbms.
    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->role === 'super_admin';
    }

    public static function form(Schema $schema): Schema
    {
        return KbmForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KbmsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKbms::route('/'),
            'create' => CreateKbm::route('/create'),
            'edit' => EditKbm::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'schedule.teacher.user',
                'schedule.schoolClass',
                'schedule.subjectForDisplay',
                'lessonPlan.subjectForDisplay',
            ]);
    }
}
