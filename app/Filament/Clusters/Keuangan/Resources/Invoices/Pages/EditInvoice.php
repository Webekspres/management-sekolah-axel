<?php

namespace App\Filament\Clusters\Keuangan\Resources\Invoices\Pages;

use App\Filament\Clusters\Keuangan\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;
}
