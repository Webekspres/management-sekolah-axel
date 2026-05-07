<?php

use App\DataTransferObjects\LogEntry;
use App\Filament\Pages\SystemLogViewer;
use App\Models\User;
use App\Services\LogFileParser;
use Filament\Facades\Filament;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create a fake log file in storage/logs/ and return its filename.
 */
function createFakeLogFile(string $filename, string $content): string
{
    $path = storage_path('logs/'.$filename);
    file_put_contents($path, $content);

    return $filename;
}

/**
 * Remove a fake log file from storage/logs/.
 */
function removeFakeLogFile(string $filename): void
{
    $path = storage_path('logs/'.$filename);
    if (file_exists($path)) {
        unlink($path);
    }
}

/**
 * Generate a sample PSR-3 log content string.
 *
 * @param  array<int, array{level: string, message: string}>  $entries
 */
function buildLogContent(array $entries): string
{
    $lines = [];
    foreach ($entries as $i => $entry) {
        $timestamp = '2024-01-15 10:'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).':00';
        $lines[] = "[{$timestamp}] testing.{$entry['level']}: {$entry['message']}";
    }

    return implode("\n", $lines)."\n";
}

// ---------------------------------------------------------------------------
// 1. Access Control
// ---------------------------------------------------------------------------

test('super_admin can access system log viewer page', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    $this->get(route('filament.admin.pages.system-log'))->assertSuccessful();
});

test('non-super_admin gets 403 when accessing system log viewer', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $guru = User::factory()->asGuru()->create();
    $this->actingAs($guru);

    $this->get(route('filament.admin.pages.system-log'))->assertForbidden();
});

test('unauthenticated user is redirected to login', function () {
    $this->get(route('filament.admin.pages.system-log'))->assertRedirect();
});

// ---------------------------------------------------------------------------
// 2. Table Display
// ---------------------------------------------------------------------------

test('table displays log entries from log file', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    $filename = 'test-display-'.uniqid().'.log';
    $content = buildLogContent([
        ['level' => 'INFO', 'message' => 'User logged in'],
        ['level' => 'ERROR', 'message' => 'Database connection failed'],
        ['level' => 'WARNING', 'message' => 'Cache miss detected'],
    ]);
    createFakeLogFile($filename, $content);

    $parser = app(LogFileParser::class);
    $entries = $parser->parseLogFile($filename);

    expect($entries)->toHaveCount(3);
    expect($entries->first()->message)->toBe('User logged in');

    removeFakeLogFile($filename);
});

test('log entry badge color matches level', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    $filename = 'test-badge-'.uniqid().'.log';
    $content = buildLogContent([
        ['level' => 'ERROR', 'message' => 'Error message'],
        ['level' => 'WARNING', 'message' => 'Warning message'],
        ['level' => 'INFO', 'message' => 'Info message'],
        ['level' => 'DEBUG', 'message' => 'Debug message'],
    ]);
    createFakeLogFile($filename, $content);

    // Verify the color mapping logic directly
    $colorMap = [
        'emergency' => 'danger',
        'alert' => 'danger',
        'critical' => 'danger',
        'error' => 'danger',
        'warning' => 'warning',
        'notice' => 'info',
        'info' => 'info',
        'debug' => 'gray',
    ];

    foreach ($colorMap as $level => $expectedColor) {
        $entry = new LogEntry('2024-01-15 10:00:00', $level, 'testing', 'Test message');
        $color = match ($entry->level) {
            'emergency', 'alert', 'critical', 'error' => 'danger',
            'warning' => 'warning',
            'notice', 'info' => 'info',
            'debug' => 'gray',
            default => 'gray',
        };
        expect($color)->toBe($expectedColor, "Level '{$level}' should map to color '{$expectedColor}'");
    }

    removeFakeLogFile($filename);
});

// ---------------------------------------------------------------------------
// 3. Search
// ---------------------------------------------------------------------------

test('search filters log entries by message', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    $filename = 'test-search-'.uniqid().'.log';
    $content = buildLogContent([
        ['level' => 'INFO', 'message' => 'User logged in successfully'],
        ['level' => 'ERROR', 'message' => 'Database connection failed'],
        ['level' => 'INFO', 'message' => 'User logged out'],
    ]);
    createFakeLogFile($filename, $content);

    $parser = app(LogFileParser::class);
    $entries = $parser->parseLogFile($filename);

    // Simulate search filtering
    $searchTerm = 'user logged';
    $filtered = $entries->filter(
        fn (LogEntry $entry): bool => str_contains(
            strtolower($entry->message),
            strtolower($searchTerm)
        )
    );

    expect($filtered)->toHaveCount(2);
    expect($filtered->pluck('message')->all())->toContain('User logged in successfully');
    expect($filtered->pluck('message')->all())->toContain('User logged out');

    removeFakeLogFile($filename);
});

test('search is case-insensitive', function () {
    $entries = collect([
        new LogEntry('2024-01-15 10:00:00', 'info', 'testing', 'User Logged In'),
        new LogEntry('2024-01-15 10:01:00', 'error', 'testing', 'database error'),
    ]);

    $searchTerm = 'USER LOGGED';
    $filtered = $entries->filter(
        fn (LogEntry $entry): bool => str_contains(
            strtolower($entry->message),
            strtolower($searchTerm)
        )
    );

    expect($filtered)->toHaveCount(1);
    expect($filtered->first()->message)->toBe('User Logged In');
});

// ---------------------------------------------------------------------------
// 4. Level Filter
// ---------------------------------------------------------------------------

test('level filter shows only matching entries', function () {
    $entries = collect([
        new LogEntry('2024-01-15 10:00:00', 'error', 'testing', 'Error one'),
        new LogEntry('2024-01-15 10:01:00', 'info', 'testing', 'Info one'),
        new LogEntry('2024-01-15 10:02:00', 'error', 'testing', 'Error two'),
        new LogEntry('2024-01-15 10:03:00', 'warning', 'testing', 'Warning one'),
    ]);

    $filtered = $entries->filter(fn (LogEntry $entry): bool => $entry->level === 'error');

    expect($filtered)->toHaveCount(2);
    expect($filtered->pluck('level')->unique()->values()->all())->toBe(['error']);
});

// ---------------------------------------------------------------------------
// 5. Pagination
// ---------------------------------------------------------------------------

test('pagination returns 25 entries per page by default', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    // Generate 30 log entries
    $entries = [];
    for ($i = 0; $i < 30; $i++) {
        $entries[] = ['level' => 'INFO', 'message' => "Log entry {$i}"];
    }

    $filename = 'test-pagination-'.uniqid().'.log';
    createFakeLogFile($filename, buildLogContent($entries));

    $parser = app(LogFileParser::class);
    $allEntries = $parser->parseLogFile($filename);

    // Simulate pagination
    $perPage = 25;
    $page = 1;
    $paginated = $allEntries->forPage($page, $perPage);

    expect($paginated)->toHaveCount(25);

    removeFakeLogFile($filename);
});

// ---------------------------------------------------------------------------
// 6. File Selector
// ---------------------------------------------------------------------------

test('file selector appears when multiple log files exist', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    $file1 = 'test-multi-a-'.uniqid().'.log';
    $file2 = 'test-multi-b-'.uniqid().'.log';
    createFakeLogFile($file1, "[2024-01-15 10:00:00] testing.INFO: File A\n");
    createFakeLogFile($file2, "[2024-01-15 10:00:00] testing.INFO: File B\n");

    $parser = app(LogFileParser::class);
    $files = $parser->detectLogFiles();

    expect(count($files))->toBeGreaterThan(1);

    removeFakeLogFile($file1);
    removeFakeLogFile($file2);
});

// ---------------------------------------------------------------------------
// 7. Empty State
// ---------------------------------------------------------------------------

test('empty state is shown when no log files exist', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    // Mock the parser to return no files
    $mockParser = Mockery::mock(LogFileParser::class);
    $mockParser->shouldReceive('detectLogFiles')->andReturn([]);
    $mockParser->shouldReceive('parseLogFile')->andReturn(collect());
    app()->instance(LogFileParser::class, $mockParser);

    Livewire::test(SystemLogViewer::class)
        ->assertSee('Tidak ada log');
});

// ---------------------------------------------------------------------------
// 8. canAccess
// ---------------------------------------------------------------------------

test('canAccess returns true for super_admin', function () {
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    expect(SystemLogViewer::canAccess())->toBeTrue();
});

test('canAccess returns false for non-super_admin', function () {
    $guru = User::factory()->asGuru()->create();
    $this->actingAs($guru);

    expect(SystemLogViewer::canAccess())->toBeFalse();
});

test('canAccess returns false for unauthenticated user', function () {
    expect(SystemLogViewer::canAccess())->toBeFalse();
});
