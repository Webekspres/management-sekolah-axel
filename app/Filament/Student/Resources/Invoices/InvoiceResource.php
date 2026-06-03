<?php

namespace App\Filament\Student\Resources\Invoices;

use App\Filament\Student\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Student\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Student\Resources\Invoices\RelationManagers\PaymentsRelationManager;
use App\Filament\Student\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class InvoiceResource extends Resource
{
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
            'view' => ViewInvoice::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $student = auth()->user()?->student;

        if ($student === null) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('student_id', $student->id)
            ->with(['academicYear', 'payments']);
    }
}
