<?php

namespace App\Filament\Student\Resources\Invoices\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Riwayat pembayaran';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(fn ($state): string => PaymentMethod::labelFor($state)),
                TextColumn::make('amount_paid')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => PaymentStatus::labelFor($state))
                    ->color(fn ($state): string => PaymentStatus::colorFor($state)),
                TextColumn::make('paid_at')
                    ->label('Dibayar pada')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
