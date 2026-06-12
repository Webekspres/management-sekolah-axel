<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ym').'-';

        do {
            $number = $prefix.strtoupper(Str::random(6));
        } while (Invoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }

    public function resolveAmountForStudent(Student $student): float
    {
        if ($student->custom_spp !== null) {
            return (float) $student->custom_spp;
        }

        $student->loadMissing('schoolClass.level');

        return (float) ($student->schoolClass?->level?->default_spp ?? 0);
    }

    public function createForStudent(
        Student $student,
        AcademicYear $academicYear,
        string $description,
        Carbon $dueDate,
        ?float $amount = null,
        ?string $billingPeriod = null,
        ?string $invoiceNumber = null,
    ): Invoice {
        $billingPeriod ??= Invoice::billingPeriodFromDate($dueDate);
        $amount ??= $this->resolveAmountForStudent($student);

        $this->assertAmountPositive($amount);
        $this->assertNoDuplicateInvoice($student, $academicYear, $billingPeriod);

        return Invoice::query()->create([
            'invoice_number' => $invoiceNumber ?? $this->generateInvoiceNumber(),
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'amount' => $amount,
            'description' => $description,
            'billing_period' => $billingPeriod,
            'status' => PaymentStatus::Unpaid,
            'due_date' => $dueDate->toDateString(),
        ]);
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public function bulkGenerateForSchoolClass(
        SchoolClass $schoolClass,
        AcademicYear $academicYear,
        string $description,
        Carbon $dueDate,
    ): array {
        return $this->bulkGenerateForStudents(
            $schoolClass->students()->get(),
            $academicYear,
            $description,
            $dueDate,
        );
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return array{created: int, skipped: int}
     */
    public function bulkGenerateForStudents(
        Collection $students,
        AcademicYear $academicYear,
        string $description,
        Carbon $dueDate,
    ): array {
        $billingPeriod = Invoice::billingPeriodFromDate($dueDate);
        $created = 0;
        $skipped = 0;

        foreach ($students as $student) {
            if ($this->duplicateExists($student, $academicYear, $billingPeriod)) {
                $skipped++;

                continue;
            }

            try {
                $this->createForStudent(
                    $student,
                    $academicYear,
                    $description,
                    $dueDate,
                    billingPeriod: $billingPeriod,
                );
                $created++;
            } catch (ValidationException) {
                $skipped++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function assertNoDuplicateInvoice(Student $student, AcademicYear $academicYear, string $billingPeriod): void
    {
        if ($this->duplicateExists($student, $academicYear, $billingPeriod)) {
            throw ValidationException::withMessages([
                'billing_period' => __('pembayaran.validation.duplicate_billing_period'),
            ]);
        }
    }

    public function assertAmountPositive(float $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => __('pembayaran.validation.zero_amount'),
            ]);
        }
    }

    protected function duplicateExists(Student $student, AcademicYear $academicYear, string $billingPeriod): bool
    {
        return Invoice::query()
            ->forBillingPeriod($student, $academicYear, $billingPeriod)
            ->exists();
    }
}
