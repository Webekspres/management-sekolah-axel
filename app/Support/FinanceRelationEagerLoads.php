<?php

namespace App\Support;

class FinanceRelationEagerLoads
{
    /**
     * @return array<int|string, mixed>
     */
    public static function forInvoice(): array
    {
        return [
            'student.user',
            'student.schoolClass',
            'academicYear',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function forPayment(): array
    {
        return [
            'invoice' => fn ($query) => $query->with(self::forInvoice()),
        ];
    }
}
