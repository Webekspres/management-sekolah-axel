<?php

namespace App\Filament\Kepsek\Resources\Invoices;

use App\Filament\Concerns\AuthorizesResourceAccess;
use App\Filament\Kepsek\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Kepsek\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use App\Support\FinanceRelationEagerLoads;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class InvoiceResource extends Resource
{
    use AuthorizesResourceAccess;

    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Keuangan';

    protected static ?string $label = 'Tagihan SPP';

    protected static ?string $pluralLabel = 'Tagihan SPP';

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
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

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(FinanceRelationEagerLoads::forInvoice());
    }
}
