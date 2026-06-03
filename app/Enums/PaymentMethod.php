<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Qris = 'qris';
    case VaBni = 'va_bni';
    case VaBca = 'va_bca';
    case VaMandiri = 'va_mandiri';
    case Transfer = 'transfer';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Qris => __('pembayaran.method.qris'),
            self::VaBni => __('pembayaran.method.va_bni'),
            self::VaBca => __('pembayaran.method.va_bca'),
            self::VaMandiri => __('pembayaran.method.va_mandiri'),
            self::Transfer => __('pembayaran.method.transfer'),
            self::Cash => __('pembayaran.method.cash'),
        };
    }

    public function groupLabel(): string
    {
        return $this->requiresGateway()
            ? __('pembayaran.method_group.online')
            : __('pembayaran.method_group.offline');
    }

    public function requiresGateway(): bool
    {
        return match ($this) {
            self::Qris, self::VaBni, self::VaBca, self::VaMandiri => true,
            self::Transfer, self::Cash => false,
        };
    }

    public function allowsStudentConfirmation(): bool
    {
        return $this === self::Transfer;
    }

    public function isCash(): bool
    {
        return $this === self::Cash;
    }

    public static function labelFor(mixed $state): string
    {
        if ($state instanceof self) {
            return $state->label();
        }

        return self::tryFrom((string) $state)?->label() ?? (string) $state;
    }

    /**
     * @return array<string, string>
     */
    public static function optionsForStudent(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function optionsForAdmin(): array
    {
        return self::optionsForStudent();
    }
}
