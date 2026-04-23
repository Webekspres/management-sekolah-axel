<?php

namespace App\Filament\Kepsek\Resources\Kbms;

use App\Filament\Kepsek\Clusters\AcademicCluster;
use App\Filament\Kepsek\Resources\Kbms\Pages\ListKbms;
use App\Filament\Kepsek\Resources\Kbms\Tables\KbmsTable;
use App\Models\Kbm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KbmResource extends Resource
{
    protected static ?string $model = Kbm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $cluster = AcademicCluster::class;

    protected static ?string $label = 'Approval KBM';

    protected static ?string $pluralLabel = 'Approval KBM';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return KbmsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKbms::route('/'),
        ];
    }
}
