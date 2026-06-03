<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'UNPAID';
    case Pending = 'PENDING';
    case Paid = 'PAID';
    case Failed = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => __('pembayaran.status.unpaid'),
            self::Pending => __('pembayaran.status.pending'),
            self::Paid => __('pembayaran.status.paid'),
            self::Failed => __('pembayaran.status.failed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'warning',
            self::Pending => 'info',
            self::Paid => 'success',
            self::Failed => 'danger',
        };
    }

    public static function labelFor(mixed $state): string
    {
        if ($state instanceof self) {
            return $state->label();
        }

        return self::tryFrom((string) $state)?->label() ?? (string) $state;
    }

    public static function colorFor(mixed $state): string
    {
        if ($state instanceof self) {
            return $state->color();
        }

        return self::tryFrom((string) $state)?->color() ?? 'gray';
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
