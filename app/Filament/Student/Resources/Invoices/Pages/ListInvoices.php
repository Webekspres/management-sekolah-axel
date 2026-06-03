<?php

namespace App\Filament\Student\Resources\Invoices\Pages;

use App\Filament\Student\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;
}
