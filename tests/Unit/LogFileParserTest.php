<?php

declare(strict_types=1);

use App\DataTransferObjects\LogEntry;
use App\Services\LogFileParser;

beforeEach(function (): void {
    $this->parser = new LogFileParser;
});

// ─── isLogLineStart ───────────────────────────────────────────────────────────

it('recognises a valid PSR-3 log line start', function (): void {
    expect($this->parser->isLogLineStart('[2024-01-15 10:30:45] production.ERROR: Something went wrong'))->toBeTrue();
});

it('rejects a stack trace line as not a log line start', function (): void {
    expect($this->parser->isLogLineStart('#0 /var/www/html/vendor/laravel/framework/src/Illuminate/Database/Connection.php(760)'))->toBeFalse();
});

it('rejects an empty line as not a log line start', function (): void {
    expect($this->parser->isLogLineStart(''))->toBeFalse();
});

it('rejects a plain text line as not a log line start', function (): void {
    expect($this->parser->isLogLineStart('Stack trace:'))->toBeFalse();
});

// ─── parseLogEntry ────────────────────────────────────────────────────────────

it('parses a single-line log entry correctly', function (): void {
    $line = '[2024-01-15 10:31:12] production.INFO: User logged in {"user_id":42}';

    $entry = $this->parser->parseLogEntry($line);

    expect($entry)->toBeInstanceOf(LogEntry::class)
        ->and($entry->timestamp)->toBe('2024-01-15 10:31:12')
        ->and($entry->environment)->toBe('production')
        ->and($entry->level)->toBe('info')
        ->and($entry->message)->toBe('User logged in {"user_id":42}')
        ->and($entry->context)->toBeNull();
});

it('returns null for a non-PSR-3 line', function (): void {
    expect($this->parser->parseLogEntry('#0 /var/www/html/app/Http/Controllers/UserController.php(42)'))->toBeNull();
});

it('normalises log level to lowercase', function (): void {
    $entry = $this->parser->parseLogEntry('[2024-01-15 10:30:45] local.ERROR: Something failed');

    expect($entry?->level)->toBe('error');
});

// ─── All PSR-3 log levels ─────────────────────────────────────────────────────

it('parses all PSR-3 log levels', function (string $level): void {
    $line = "[2024-01-15 10:30:45] production.{$level}: Test message";

    $entry = $this->parser->parseLogEntry($line);

    expect($entry)->toBeInstanceOf(LogEntry::class)
        ->and($entry->level)->toBe(strtolower($level));
})->with([
    'EMERGENCY',
    'ALERT',
    'CRITICAL',
    'ERROR',
    'WARNING',
    'NOTICE',
    'INFO',
    'DEBUG',
]);

// ─── parseLogFile (multiline / stack trace) ───────────────────────────────────

it('groups stack trace lines into the context of the preceding entry', function (): void {
    $logContent = implode("\n", [
        '[2024-01-15 10:30:45] production.ERROR: SQLSTATE[HY000]: General error',
        'Stack trace:',
        '#0 /var/www/html/vendor/laravel/framework/src/Illuminate/Database/Connection.php(760)',
        '#1 /var/www/html/app/Http/Controllers/UserController.php(42)',
        '',
        '[2024-01-15 10:31:12] production.INFO: User logged in',
    ]);

    $tmpFile = tempnam(sys_get_temp_dir(), 'log_test_').'.log';
    file_put_contents($tmpFile, $logContent);

    // Use reflection to call parseLines via parseLogFile with a temp path
    $reflection = new ReflectionClass($this->parser);
    $readLines = $reflection->getMethod('readFileLines');
    $readLines->setAccessible(true);
    $parseLines = $reflection->getMethod('parseLines');
    $parseLines->setAccessible(true);

    $lines = $readLines->invoke($this->parser, $tmpFile);
    $entries = $parseLines->invoke($this->parser, $lines);

    unlink($tmpFile);

    expect($entries)->toHaveCount(2);

    $errorEntry = $entries->first();
    expect($errorEntry->level)->toBe('error')
        ->and($errorEntry->context)->toContain('Stack trace:')
        ->and($errorEntry->context)->toContain('#0 /var/www/html/vendor/laravel/framework');

    $infoEntry = $entries->last();
    expect($infoEntry->level)->toBe('info')
        ->and($infoEntry->context)->toBeNull();
});

it('returns an empty collection for an empty log file', function (): void {
    $tmpFile = tempnam(sys_get_temp_dir(), 'log_test_').'.log';
    file_put_contents($tmpFile, '');

    $reflection = new ReflectionClass($this->parser);
    $readLines = $reflection->getMethod('readFileLines');
    $readLines->setAccessible(true);
    $parseLines = $reflection->getMethod('parseLines');
    $parseLines->setAccessible(true);

    $lines = $readLines->invoke($this->parser, $tmpFile);
    $entries = $parseLines->invoke($this->parser, $lines);

    unlink($tmpFile);

    expect($entries)->toBeEmpty();
});

// ─── detectLogFiles ───────────────────────────────────────────────────────────
// Note: detectLogFiles() uses storage_path() which requires the full app container.
// Those tests live in tests/Feature/SystemLogViewerTest.php instead.

// ─── Security: path traversal prevention ─────────────────────────────────────

it('rejects filenames with path traversal attempts', function (string $maliciousFilename): void {
    $result = $this->parser->parseLogFile($maliciousFilename);

    expect($result)->toBeEmpty();
})->with([
    '../../../etc/passwd',
    '..\\..\\windows\\system32\\config\\sam',
    '/etc/passwd.log',
    'logs/../../../etc/passwd.log',
]);

it('rejects filenames without .log extension', function (): void {
    $result = $this->parser->parseLogFile('laravel.txt');

    expect($result)->toBeEmpty();
});
