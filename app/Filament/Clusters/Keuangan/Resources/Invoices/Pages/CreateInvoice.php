<?php

namespace App\Filament\Clusters\Keuangan\Resources\Invoices\Pages;

use App\Filament\Clusters\Keuangan\Resources\Invoices\InvoiceResource;
use App\Services\InvoiceService;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = app(InvoiceService::class)->generateInvoiceNumber();
        }

        return $data;
    }
}
