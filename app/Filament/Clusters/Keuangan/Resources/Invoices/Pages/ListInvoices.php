<?php

namespace App\Filament\Clusters\Keuangan\Resources\Invoices\Pages;

use App\Filament\Clusters\Keuangan\Actions\ManageDefaultSppAction;
use App\Filament\Clusters\Keuangan\Actions\ManagePaymentSettingsAction;
use App\Filament\Clusters\Keuangan\Resources\Invoices\InvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ManagePaymentSettingsAction::make(),
            ManageDefaultSppAction::make(),
            CreateAction::make(),
        ];
    }
}
