<?php

namespace App\Filament\Clusters\Keuangan\Resources\Invoices\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Clusters\Keuangan\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        if (! $record->isLockedForEditing()) {
            if (empty($data['billing_period']) && ! empty($data['due_date'])) {
                $data['billing_period'] = Invoice::billingPeriodFromDate(
                    Carbon::parse($data['due_date']),
                );
            }

            return $data;
        }

        $data['student_id'] = $record->student_id;
        $data['academic_year_id'] = $record->academic_year_id;
        $data['amount'] = $record->amount;
        $data['billing_period'] = $record->billing_period;
        $data['invoice_number'] = $record->invoice_number;
        $data['status'] = $record->status instanceof PaymentStatus
            ? $record->status->value
            : $record->status;

        return $data;
    }
}
