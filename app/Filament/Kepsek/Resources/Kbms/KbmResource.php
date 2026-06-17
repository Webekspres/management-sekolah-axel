<?php

namespace App\Filament\Kepsek\Resources\Kbms;

use App\Filament\Kepsek\Resources\Kbms\Pages\EditKbm;
use App\Filament\Kepsek\Resources\Kbms\Pages\ListKbms;
use App\Filament\Kepsek\Resources\Kbms\Schemas\KbmForm;
use App\Filament\Kepsek\Resources\Kbms\Tables\KbmsTable;
use App\Models\Kbm;
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

    protected static ?string $label = 'Approval KBM';

    protected static ?string $pluralLabel = 'Approval KBM';

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
