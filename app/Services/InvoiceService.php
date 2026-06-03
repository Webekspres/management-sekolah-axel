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
    ): Invoice {
        return Invoice::query()->create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'amount' => $amount ?? $this->resolveAmountForStudent($student),
            'description' => $description,
            'status' => PaymentStatus::Unpaid,
            'due_date' => $dueDate->toDateString(),
        ]);
    }

    /**
     * @return int Number of invoices created
     */
    public function bulkGenerateForSchoolClass(
        SchoolClass $schoolClass,
        AcademicYear $academicYear,
        string $description,
        Carbon $dueDate,
    ): int {
        $students = $schoolClass->students()->get();
        $created = 0;

        foreach ($students as $student) {
            $exists = Invoice::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('description', $description)
                ->exists();

            if ($exists) {
                continue;
            }

            $this->createForStudent($student, $academicYear, $description, $dueDate);
            $created++;
        }

        return $created;
    }

    /**
     * @param  Collection<int, Student>  $students
     */
    public function bulkGenerateForStudents(
        Collection $students,
        AcademicYear $academicYear,
        string $description,
        Carbon $dueDate,
    ): int {
        $created = 0;

        foreach ($students as $student) {
            $exists = Invoice::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('description', $description)
                ->exists();

            if ($exists) {
                continue;
            }

            $this->createForStudent($student, $academicYear, $description, $dueDate);
            $created++;
        }

        return $created;
    }
}
