<?php

namespace App\Filament\Student\Resources\Invoices\Tables;

use App\Enums\PaymentStatus;
use App\Filament\Shared\Actions\PayInvoiceAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Tagihan')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Keterangan'),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => PaymentStatus::labelFor($state))
                    ->color(fn ($state): string => PaymentStatus::colorFor($state)),
                TextColumn::make('due_date')
                    ->label('Jatuh tempo')
                    ->date('d M Y'),
            ])
            ->defaultSort('due_date', 'desc')
            ->recordActions([
                PayInvoiceAction::make(),
                ViewAction::make(),
            ]);
    }
}
