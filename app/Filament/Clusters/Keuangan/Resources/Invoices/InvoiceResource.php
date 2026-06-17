<?php

namespace App\Filament\Clusters\Keuangan\Resources\Invoices;

use App\Filament\Clusters\Keuangan\KeuanganCluster;
use App\Filament\Clusters\Keuangan\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Clusters\Keuangan\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Clusters\Keuangan\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Clusters\Keuangan\Resources\Invoices\RelationManagers\PaymentsRelationManager;
use App\Filament\Clusters\Keuangan\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Clusters\Keuangan\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use App\Support\FinanceRelationEagerLoads;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $cluster = KeuanganCluster::class;

    protected static ?string $label = 'Tagihan SPP';

    protected static ?string $pluralLabel = 'Tagihan SPP';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Invoice::class) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(FinanceRelationEagerLoads::forInvoice());
    }
}
