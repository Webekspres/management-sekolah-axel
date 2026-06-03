<?php

namespace App\Filament\Shared\Actions;

use App\Enums\PaymentMethod;
use App\Filament\Student\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\PaymentService;
use App\Support\SchoolPaymentSettings;
use DomainException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Livewire\Livewire;

class PayInvoiceAction
{
    public static function make(): Action
    {
        return Action::make('bayar_tagihan')
            ->label('Bayar tagihan')
            ->icon(Heroicon::OutlinedCreditCard)
            ->color('primary')
            ->visible(fn (Invoice $record): bool => app(PaymentService::class)->canStudentPay($record))
            ->modalHeading(__('pembayaran.pay_modal.title'))
            ->modalDescription(fn (Invoice $record): string => __('pembayaran.pay_modal.subtitle', [
                'description' => $record->description,
                'amount' => 'Rp '.number_format((float) $record->amount, 0, ',', '.'),
            ]))
            ->modalSubmitActionLabel(__('pembayaran.pay_modal.submit_transfer'))
            ->schema([
                Select::make('payment_method')
                    ->label('Cara pembayaran')
                    ->options(PaymentMethod::optionsForStudent())
                    ->default(PaymentMethod::Transfer->value)
                    ->required()
                    ->live()
                    ->native(false),
                Section::make(__('pembayaran.method_group.online'))
                    ->description(__('pembayaran.pay_modal.online_hint'))
                    ->visible(fn (Get $get): bool => PaymentMethod::tryFrom((string) $get('payment_method'))?->requiresGateway() ?? false)
                    ->schema([]),
                Section::make(__('pembayaran.pay_modal.bank_section'))
                    ->description(__('pembayaran.pay_modal.transfer_hint'))
                    ->visible(fn (Get $get): bool => $get('payment_method') === PaymentMethod::Transfer->value)
                    ->schema([
                        Placeholder::make('bank_instructions')
                            ->label('')
                            ->content(fn (): string => SchoolPaymentSettings::formatBankInstructions()),
                    ]),
                Section::make(__('pembayaran.method.cash'))
                    ->description(__('pembayaran.pay_modal.cash_hint'))
                    ->visible(fn (Get $get): bool => $get('payment_method') === PaymentMethod::Cash->value)
                    ->schema([]),
            ])
            ->action(function (Invoice $record, array $data): void {
                $student = auth()->user()?->student;

                if ($student === null) {
                    Notification::make()->title('Akun tidak terhubung ke data siswa.')->danger()->send();

                    return;
                }

                $method = PaymentMethod::from($data['payment_method']);
                $paymentService = app(PaymentService::class);

                try {
                    if ($method->isCash()) {
                        Notification::make()
                            ->title(__('pembayaran.pay_modal.cash_no_status_change'))
                            ->body(__('pembayaran.pay_modal.cash_hint'))
                            ->info()
                            ->send();

                        return;
                    }

                    if ($method->allowsStudentConfirmation()) {
                        $paymentService->confirmOfflineTransfer($record, $student);
                        Notification::make()
                            ->title(__('pembayaran.pay_modal.transfer_confirmed'))
                            ->success()
                            ->send();

                        self::reloadPageAfterPayment($record);

                        return;
                    }

                    $result = $paymentService->initiateOnlinePayment($record, $student, $method);

                    Notification::make()
                        ->title($result['result']->message ?? __('pembayaran.pay_modal.online_unavailable'))
                        ->warning()
                        ->send();

                    self::reloadPageAfterPayment($record);
                } catch (DomainException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();
                }
            });
    }

    protected static function reloadPageAfterPayment(Invoice $record): void
    {
        $studentId = auth()->user()?->student?->id;

        $fresh = $studentId === null
            ? null
            : Invoice::query()
                ->where('student_id', $studentId)
                ->whereKey($record->id)
                ->first();

        if ($fresh !== null) {
            $record->setRawAttributes($fresh->getAttributes());
            $record->syncOriginal();
        }

        if (app()->runningUnitTests()) {
            return;
        }

        $livewire = Livewire::current();

        if ($livewire === null) {
            return;
        }

        $panel = Filament::getCurrentPanel()?->getId() ?? 'student';
        $refererPath = parse_url((string) request()->header('Referer'), PHP_URL_PATH) ?? '';
        $isViewPage = str_contains($refererPath, '/invoices/')
            && ! str_ends_with(rtrim($refererPath, '/'), '/invoices');

        $url = $isViewPage
            ? InvoiceResource::getUrl('view', ['record' => $record], panel: $panel)
            : InvoiceResource::getUrl('index', panel: $panel);

        $livewire->redirect($url);
    }
}
