<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class LogEntry
{
    public function __construct(
        public readonly string $timestamp,
        public readonly string $level,
        public readonly string $environment,
        public readonly string $message,
        public readonly ?string $context = null,
    ) {}
}
