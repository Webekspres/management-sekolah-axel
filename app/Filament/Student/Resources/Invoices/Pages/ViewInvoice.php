<?php

namespace App\Filament\Student\Resources\Invoices\Pages;

use App\Filament\Shared\Actions\PayInvoiceAction;
use App\Filament\Student\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PayInvoiceAction::make(),
        ];
    }
}
