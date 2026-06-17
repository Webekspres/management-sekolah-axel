<?php

namespace App\Filament\Kepsek\Resources\Invoices\Pages;

use App\Filament\Kepsek\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;
}
