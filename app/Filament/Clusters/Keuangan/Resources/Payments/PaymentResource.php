<?php

namespace App\Filament\Clusters\Keuangan\Resources\Payments;

use App\Filament\Clusters\Keuangan\KeuanganCluster;
use App\Filament\Clusters\Keuangan\Resources\Payments\Pages\ListPayments;
use App\Filament\Clusters\Keuangan\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use App\Support\FinanceRelationEagerLoads;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $cluster = KeuanganCluster::class;

    protected static ?string $label = 'Pembayaran';

    protected static ?string $pluralLabel = 'Pembayaran';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Payment::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(FinanceRelationEagerLoads::forPayment());
    }
}
