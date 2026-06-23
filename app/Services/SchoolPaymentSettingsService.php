<?php

namespace App\Services;

use App\Support\SchoolPaymentSettings;

class SchoolPaymentSettingsService
{
    /**
     * @return array{
     *     bank_name: string,
     *     account_number: string,
     *     account_holder: string,
     *     school_whatsapp: string,
     * }
     */
    public function formDefaults(): array
    {
        return [
            'bank_name' => SchoolPaymentSettings::get(SchoolPaymentSettings::BANK_NAME) ?? '',
            'account_number' => SchoolPaymentSettings::get(SchoolPaymentSettings::ACCOUNT_NUMBER) ?? '',
            'account_holder' => SchoolPaymentSettings::get(SchoolPaymentSettings::ACCOUNT_HOLDER) ?? '',
            'school_whatsapp' => SchoolPaymentSettings::get(SchoolPaymentSettings::SCHOOL_WHATSAPP) ?? '',
        ];
    }

    /**
     * @param  array{
     *     bank_name?: string,
     *     account_number?: string,
     *     account_holder?: string,
     *     school_whatsapp?: string,
     * }  $data
     */
    public function updateFromFormData(array $data): void
    {
        SchoolPaymentSettings::set(SchoolPaymentSettings::BANK_NAME, trim((string) ($data['bank_name'] ?? '')));
        SchoolPaymentSettings::set(SchoolPaymentSettings::ACCOUNT_NUMBER, trim((string) ($data['account_number'] ?? '')));
        SchoolPaymentSettings::set(SchoolPaymentSettings::ACCOUNT_HOLDER, trim((string) ($data['account_holder'] ?? '')));
        SchoolPaymentSettings::set(SchoolPaymentSettings::SCHOOL_WHATSAPP, trim((string) ($data['school_whatsapp'] ?? '')));
    }
}
