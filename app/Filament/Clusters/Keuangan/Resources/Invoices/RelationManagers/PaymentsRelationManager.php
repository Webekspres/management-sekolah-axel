<?php

namespace App\Filament\Clusters\Keuangan\Resources\Invoices\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Riwayat pembayaran';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('payment_method')
                ->label('Metode')
                ->options(PaymentMethod::optionsForAdmin())
                ->required(),
            Select::make('status')
                ->label('Status')
                ->options(PaymentStatus::options())
                ->default(PaymentStatus::Paid->value)
                ->required(),
        ]);
    }

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
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Catat pembayaran')
                    ->mutateFormDataUsing(function (array $data): array {
                        $invoice = $this->getOwnerRecord();
                        $data['invoice_id'] = $invoice->id;
                        $data['amount_paid'] = $invoice->amount;

                        return $data;
                    })
                    ->using(function (array $data) {
                        $invoice = $this->getOwnerRecord();
                        $method = PaymentMethod::from($data['payment_method']);
                        $status = PaymentStatus::from($data['status']);

                        return app(PaymentService::class)->recordManualPayment($invoice, $method, $status);
                    }),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === PaymentStatus::Pending)
                    ->action(function ($record): void {
                        app(PaymentService::class)->verifyPayment($record);
                        Notification::make()->title(__('pembayaran.notifications.verified'))->success()->send();
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === PaymentStatus::Pending)
                    ->action(function ($record): void {
                        app(PaymentService::class)->rejectPayment($record);
                        Notification::make()->title(__('pembayaran.notifications.rejected'))->warning()->send();
                    }),
            ]);
    }
}
