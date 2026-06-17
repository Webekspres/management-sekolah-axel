<?php

namespace App\Filament\Clusters\Keuangan\Resources\Invoices\Pages;

use App\Filament\Clusters\Keuangan\Resources\Invoices\InvoiceResource;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\InvoiceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = app(InvoiceService::class)->generateInvoiceNumber();
        }

        if (empty($data['billing_period']) && ! empty($data['due_date'])) {
            $data['billing_period'] = Invoice::billingPeriodFromDate(Carbon::parse($data['due_date']));
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $student = Student::query()->findOrFail($data['student_id']);
        $academicYear = AcademicYear::query()->findOrFail($data['academic_year_id']);

        return app(InvoiceService::class)->createForStudent(
            student: $student,
            academicYear: $academicYear,
            description: $data['description'],
            dueDate: Carbon::parse($data['due_date']),
            amount: (float) $data['amount'],
            billingPeriod: $data['billing_period'],
            invoiceNumber: $data['invoice_number'],
        );
    }
}
