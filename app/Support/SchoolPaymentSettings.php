<?php

namespace App\Support;

use App\Models\Setting;

class SchoolPaymentSettings
{
    public const BANK_NAME = 'bank_name';

    public const ACCOUNT_NUMBER = 'account_number';

    public const ACCOUNT_HOLDER = 'account_holder';

    public const SCHOOL_WHATSAPP = 'school_whatsapp';

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = Setting::query()->where('key', $key)->value('value');

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    /**
     * @return array{bank_name: ?string, account_number: ?string, account_holder: ?string}
     */
    public static function bankDetails(): array
    {
        return [
            'bank_name' => self::get(self::BANK_NAME),
            'account_number' => self::get(self::ACCOUNT_NUMBER),
            'account_holder' => self::get(self::ACCOUNT_HOLDER),
        ];
    }

    public static function formatBankInstructions(): string
    {
        $bank = self::bankDetails();

        $lines = array_filter([
            $bank['bank_name'] ? 'Bank: '.$bank['bank_name'] : null,
            $bank['account_number'] ? 'No. Rekening: '.$bank['account_number'] : null,
            $bank['account_holder'] ? 'Atas nama: '.$bank['account_holder'] : null,
        ]);

        return $lines !== []
            ? implode("\n", $lines)
            : 'Informasi rekening belum diatur. Hubungi pihak sekolah.';
    }
}
