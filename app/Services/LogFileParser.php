<?php

namespace App\Services;

use App\DataTransferObjects\LogEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class LogFileParser
{
    /**
     * Maximum file size in bytes before limiting to last N lines (10MB).
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Number of lines to read from the end of large files.
     */
    private const LARGE_FILE_LINE_LIMIT = 1000;

    /**
     * PSR-3 log line pattern: [YYYY-MM-DD HH:MM:SS] env.LEVEL: message
     */
    private const LOG_LINE_PATTERN = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)$/';

    /**
     * Scan storage/logs/ for .log files, sorted by modification time (newest first).
     *
     * @return array<int, string>
     */
    public function detectLogFiles(): array
    {
        $logPath = storage_path('logs');

        if (! is_dir($logPath) || ! is_readable($logPath)) {
            return [];
        }

        $files = glob($logPath.DIRECTORY_SEPARATOR.'*.log');

        if ($files === false || empty($files)) {
            return [];
        }

        usort($files, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        return array_map(fn (string $path): string => basename($path), $files);
    }

    /**
     * Read and parse a log file, returning a Collection of LogEntry objects.
     * Validates filename to prevent path traversal.
     *
     * @return Collection<int, LogEntry>
     */
    public function parseLogFile(string $filename): Collection
    {
        if (! $this->isValidLogFilename($filename)) {
            return collect();
        }

        $filePath = storage_path('logs'.DIRECTORY_SEPARATOR.$filename);

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return collect();
        }

        $lines = $this->readFileLines($filePath);

        return $this->parseLines($lines);
    }

    /**
     * Parse a single log line. Returns a new LogEntry or null if the line
     * is a continuation (stack trace / multiline) of the previous entry.
     */
    public function parseLogEntry(string $line, ?LogEntry $previousEntry = null): ?LogEntry
    {
        if (! $this->isLogLineStart($line)) {
            return null;
        }

        if (preg_match(self::LOG_LINE_PATTERN, $line, $matches) !== 1) {
            return null;
        }

        return new LogEntry(
            timestamp: $matches[1],
            level: strtolower($matches[3]),
            environment: $matches[2],
            message: $matches[4],
        );
    }

    /**
     * Check whether a line starts with the PSR-3 timestamp pattern.
     */
    public function isLogLineStart(string $line): bool
    {
        return (bool) preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line);
    }

    /**
     * Validate that a filename is safe (no path traversal, .log extension only).
     */
    private function isValidLogFilename(string $filename): bool
    {
        // Prevent path traversal
        if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
            return false;
        }

        return str_ends_with($filename, '.log');
    }

    /**
     * Read file lines, limiting to last N lines for large files.
     *
     * @return array<int, string>
     */
    private function readFileLines(string $filePath): array
    {
        $fileSize = filesize($filePath);

        if ($fileSize === false) {
            return [];
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            return [];
        }

        $lines = explode("\n", $content);

        if ($fileSize > self::MAX_FILE_SIZE) {
            $lines = array_slice($lines, -self::LARGE_FILE_LINE_LIMIT);
        }

        return $lines;
    }

    /**
     * Parse an array of raw log lines into a Collection of LogEntry objects.
     *
     * @param  array<int, string>  $lines
     * @return Collection<int, LogEntry>
     */
    private function parseLines(array $lines): Collection
    {
        /** @var Collection<int, LogEntry> $entries */
        $entries = collect();
        $currentEntry = null;

        foreach ($lines as $line) {
            $line = rtrim($line);

            if ($line === '') {
                continue;
            }

            if ($this->isLogLineStart($line)) {
                if ($currentEntry !== null) {
                    $entries->push($currentEntry);
                }

                $currentEntry = $this->parseLogEntry($line);
            } else {
                // Continuation line (stack trace / multiline) — append to context
                if ($currentEntry !== null) {
                    $existingContext = $currentEntry->context;
                    $newContext = $existingContext !== null
                        ? $existingContext."\n".$line
                        : $line;

                    $currentEntry = new LogEntry(
                        timestamp: $currentEntry->timestamp,
                        level: $currentEntry->level,
                        environment: $currentEntry->environment,
                        message: $currentEntry->message,
                        context: $newContext,
                    );
                }
            }
        }

        if ($currentEntry !== null) {
            $entries->push($currentEntry);
        }

        return $entries;
    }
}
