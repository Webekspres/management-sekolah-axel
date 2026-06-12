<?php

namespace App\Filament\Clusters\Keuangan\Resources\Payments\Tables;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\PaymentService;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.invoice_number')
                    ->label('No. Tagihan')
                    ->searchable(),
                TextColumn::make('invoice.student.user.name')
                    ->label('Siswa')
                    ->searchable(),
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
                    ->label('Dibayar')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(PaymentStatus::options()),
                SelectFilter::make('payment_method')
                    ->label('Metode')
                    ->options(PaymentMethod::optionsForAdmin()),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize(fn ($record): bool => auth()->user()?->can('verify', $record) ?? false)
                    ->visible(fn ($record): bool => $record->status === PaymentStatus::Pending)
                    ->action(function ($record): void {
                        try {
                            app(PaymentService::class)->verifyPayment($record);
                            Notification::make()->title(__('pembayaran.notifications.verified'))->success()->send();
                        } catch (DomainException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize(fn ($record): bool => auth()->user()?->can('reject', $record) ?? false)
                    ->visible(fn ($record): bool => $record->status === PaymentStatus::Pending)
                    ->action(function ($record): void {
                        try {
                            app(PaymentService::class)->rejectPayment($record);
                            Notification::make()->title(__('pembayaran.notifications.rejected'))->warning()->send();
                        } catch (DomainException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
