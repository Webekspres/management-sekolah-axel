# Design Document: System Log Viewer

## Overview

The System Log Viewer is a custom Filament page that provides super_admin users with a web-based interface to view, search, and filter Laravel application logs. The page reads log files directly from the `storage/logs/` directory and displays parsed log entries in a Filament table component.

This feature does not use Eloquent models or database storage. Instead, it implements a custom data source by parsing log files on-demand and presenting them through Filament's table interface with full support for pagination, search, and filtering.

**Key Design Decisions:**

- **Custom Page with Table**: Uses Filament's `InteractsWithTable` trait on a custom page rather than a resource, since there is no underlying Eloquent model
- **Direct File Reading**: Reads log files directly from filesystem rather than storing logs in database
- **PSR-3 Log Format**: Parses standard Laravel log format: `[YYYY-MM-DD HH:MM:SS] env.LEVEL: message`
- **In-Memory Processing**: Parses and filters logs in memory for simplicity, suitable for typical log file sizes
- **Authorization at Page Level**: Uses `canAccess()` method to restrict access to super_admin role

## Architecture

### Component Structure

```
┌─────────────────────────────────────────────────────────┐
│ SystemLogViewer (Filament Page)                         │
│ - Implements HasTable                                   │
│ - Uses InteractsWithTable trait                         │
│ - Authorization: super_admin only                       │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌────────────────────────────────────────────────┐    │
│  │ File Selector (Form Component)                  │    │
│  │ - Dropdown of available log files               │    │
│  │ - Default: most recent file                     │    │
│  └────────────────────────────────────────────────┘    │
│                                                          │
│  ┌────────────────────────────────────────────────┐    │
│  │ Table Component                                 │    │
│  │ - Columns: timestamp, level, environment, msg   │    │
│  │ - Search: message text                          │    │
│  │ - Filter: log level                             │    │
│  │ - Pagination: 25 per page default               │    │
│  └────────────────────────────────────────────────┘    │
│                                                          │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
         ┌────────────────────────────────────┐
         │ LogFileParser (Service Class)      │
         │ - parseLogFile(string $path)       │
         │ - parseLogEntry(string $line)      │
         │ - detectLogFiles()                 │
         └────────────────────────────────────┘
                          │
                          ▼
              ┌───────────────────────┐
              │ storage/logs/         │
              │ - laravel.log         │
              │ - laravel-YYYY-MM-DD  │
              └───────────────────────┘
```

### Data Flow

1. **Page Load**: SystemLogViewer page loads, detects available log files
2. **File Selection**: User selects log file (or uses default: most recent)
3. **File Reading**: LogFileParser reads selected file from filesystem
4. **Parsing**: Parser converts raw log text into structured LogEntry objects
5. **Filtering**: Apply search and filter criteria to parsed entries
6. **Pagination**: Create LengthAwarePaginator with filtered results
7. **Display**: Filament table renders paginated entries with badges and formatting

### File Organization

```
app/
├── Filament/
│   └── Pages/
│       └── SystemLogViewer.php          # Main page class
├── Services/
│   └── LogFileParser.php                # Log parsing service
└── DataTransferObjects/
    └── LogEntry.php                     # DTO for parsed log entries

resources/views/filament/pages/
└── system-log-viewer.blade.php          # Page view template

tests/Feature/
└── SystemLogViewerTest.php              # Feature tests
```

## Components and Interfaces

### 1. SystemLogViewer Page Class

**Location**: `app/Filament/Pages/SystemLogViewer.php`

**Responsibilities:**

- Implement authorization via `canAccess()` method
- Provide file selector form component
- Configure table with columns, filters, and search
- Integrate with LogFileParser service
- Handle pagination of parsed log entries

**Key Methods:**

```php
public static function canAccess(): bool
// Returns true only for super_admin role

public function mount(): void
// Initialize selected log file (default to most recent)

public function table(Table $table): Table
// Configure table with custom records() closure
// Define columns, filters, search, pagination

protected function getLogFiles(): array
// Return array of available log files

protected function getSelectedLogFile(): ?string
// Return currently selected log file path
```

**Properties:**

```php
public ?string $selectedLogFile = null;
// Stores currently selected log file name
```

### 2. LogFileParser Service

**Location**: `app/Services/LogFileParser.php`

**Responsibilities:**

- Detect available log files in storage/logs/
- Read and parse log file contents
- Handle multiline log entries (stack traces)
- Convert raw text to LogEntry DTOs

**Key Methods:**

```php
public function detectLogFiles(): array
// Scan storage/logs/ for .log files
// Return array of filenames sorted by date (newest first)

public function parseLogFile(string $filename): Collection
// Read file from storage/logs/{$filename}
// Parse all entries
// Return Collection of LogEntry objects

protected function parseLogEntry(string $line, ?LogEntry $previousEntry = null): ?LogEntry
// Parse single line matching PSR-3 format
// Return LogEntry or null if line is continuation of previous entry

protected function isLogLineStart(string $line): bool
// Check if line starts with [YYYY-MM-DD HH:MM:SS] pattern
```

### 3. LogEntry DTO

**Location**: `app/DataTransferObjects/LogEntry.php`

**Responsibilities:**

- Represent a single parsed log entry
- Provide structured access to log components

**Properties:**

```php
public readonly string $timestamp;      // ISO 8601 timestamp
public readonly string $level;          // emergency|alert|critical|error|warning|notice|info|debug
public readonly string $environment;    // production|local|staging|etc
public readonly string $message;        // Main log message
public readonly ?string $context;       // Stack trace or additional context (optional)
```

**Constructor:**

```php
public function __construct(
    string $timestamp,
    string $level,
    string $environment,
    string $message,
    ?string $context = null
)
```

### 4. Blade View Template

**Location**: `resources/views/filament/pages/system-log-viewer.blade.php`

**Content:**

```blade
<x-filament::page>
    <div class="space-y-6">
        @if (count($this->getLogFiles()) > 1)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content p-6">
                    {{ $this->fileSelectForm }}
                </div>
            </div>
        @endif

        {{ $this->table }}
    </div>
</x-filament::page>
```

## Data Models

### LogEntry DTO Structure

```php
[
    'timestamp' => '2024-01-15 10:30:45',
    'level' => 'error',
    'environment' => 'production',
    'message' => 'SQLSTATE[HY000]: General error',
    'context' => 'Stack trace: #0 /var/www/...'  // Optional
]
```

### Log File Format (PSR-3)

```
[2024-01-15 10:30:45] production.ERROR: SQLSTATE[HY000]: General error
Stack trace:
#0 /var/www/html/vendor/laravel/framework/src/Illuminate/Database/Connection.php(760): PDOStatement->execute()
#1 /var/www/html/vendor/laravel/framework/src/Illuminate/Database/Connection.php(720): Illuminate\Database\Connection->runQueryCallback()
...

[2024-01-15 10:31:12] production.INFO: User logged in {"user_id":42}
[2024-01-15 10:31:45] production.WARNING: Cache miss for key: user_preferences_42
```

### Parsing Logic

**Single-line entry:**

```
[2024-01-15 10:31:12] production.INFO: User logged in {"user_id":42}
```

Parsed as:

- timestamp: `2024-01-15 10:31:12`
- environment: `production`
- level: `INFO`
- message: `User logged in {"user_id":42}`
- context: `null`

**Multi-line entry:**

```
[2024-01-15 10:30:45] production.ERROR: SQLSTATE[HY000]: General error
Stack trace:
#0 /var/www/html/vendor/laravel/framework/...
#1 /var/www/html/vendor/laravel/framework/...
```

Parsed as:

- timestamp: `2024-01-15 10:30:45`
- environment: `production`
- level: `ERROR`
- message: `SQLSTATE[HY000]: General error`
- context: `Stack trace:\n#0 /var/www/html/vendor/laravel/framework/...\n#1 ...`

## Error Handling

### File System Errors

**Scenario**: Log directory doesn't exist or is not readable

- **Detection**: Check `Storage::exists('logs')` and `is_readable()`
- **Response**: Display empty state with message "No log files available"
- **User Impact**: Non-blocking, informative message

**Scenario**: Selected log file is deleted while viewing

- **Detection**: File existence check before reading
- **Response**: Reset to default file or show empty state
- **User Impact**: Graceful fallback, notification shown

### Parsing Errors

**Scenario**: Log file contains malformed entries

- **Detection**: Regex pattern match fails
- **Response**: Skip malformed lines, continue parsing
- **Logging**: Log parsing errors to separate error log
- **User Impact**: Partial data shown, no crash

**Scenario**: Log file is too large (>100MB)

- **Detection**: Check filesize before reading
- **Response**: Show warning, limit to last N lines
- **User Impact**: Performance maintained, warning displayed

### Permission Errors

**Scenario**: Non-super_admin attempts to access page

- **Detection**: `canAccess()` returns false
- **Response**: HTTP 403 Forbidden
- **User Impact**: Access denied, redirected

**Scenario**: Unauthenticated user attempts access

- **Detection**: Filament auth middleware
- **Response**: Redirect to login page
- **User Impact**: Standard login flow

### Empty States

**Scenario**: No log files exist

- **Display**: Empty state with icon and message
- **Message**: "No log files available. Logs will appear here once the application generates them."

**Scenario**: Log file is empty

- **Display**: Empty table state
- **Message**: "This log file is empty."

**Scenario**: Search/filter returns no results

- **Display**: Empty table state
- **Message**: "No log entries match your search criteria."

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Log file detection filters by extension

*For any* set of files in the `storage/logs/` directory, the `detectLogFiles()` method SHALL return only files with the `.log` extension, excluding all other file types.

**Validates: Requirements 3.1**

### Property 2: Default file selection chooses most recent

*For any* non-empty set of log files with different modification timestamps, the default selected log file SHALL be the one with the most recent modification time.

**Validates: Requirements 3.4**

### Property 3: Level filter shows only matching entries

*For any* collection of log entries with mixed levels and any selected log level filter value, the filtered results SHALL contain only entries where the `level` field exactly matches the selected filter value.

**Validates: Requirements 4.3**

### Property 4: Search filter shows only matching messages

*For any* collection of log entries and any search term string, the filtered results SHALL contain only entries where the `message` field contains the search term in a case-insensitive manner.

**Validates: Requirements 4.4**

### Property 5: Parse valid log line extracts all components

*For any* log line string matching the PSR-3 format `[YYYY-MM-DD HH:MM:SS] env.LEVEL: message`, the parser SHALL correctly extract the timestamp, environment, level, and message components into a structured LogEntry object.

**Validates: Requirements 5.1, 5.3**

### Property 6: Multiline entries grouped with previous

*For any* sequence of log lines where a valid PSR-3 formatted line is followed by one or more lines that do not match the PSR-3 format, the parser SHALL append the non-matching lines to the `context` field of the preceding LogEntry.

**Validates: Requirements 5.2**

## Testing Strategy

The `LogFileParser` service contains pure transformation logic (parsing text into structured data) that is well-suited for property-based testing. The Filament page, UI rendering, and file I/O are better covered by example-based tests.

**Property-Based Testing Library**: Use [Pest's `dataset()` with `faker`](https://pestphp.com/docs/datasets) for data generation. Each property test should run a minimum of **100 iterations**.

**Tag format**: `Feature: system-log-viewer, Property {number}: {property_text}`

**Dual Testing Approach**:

- **Unit/property tests**: Verify LogFileParser parsing logic with both specific examples and generated inputs
- **Feature tests**: Verify Filament page behavior with example-based scenarios
- **Integration tests**: Verify end-to-end scenarios with real log files

### Unit Tests (LogFileParser)

**Test File**: `tests/Unit/Services/LogFileParserTest.php`

Test cases:

1. **Parse single-line log entry**
   - Given: `[2024-01-15 10:31:12] production.INFO: User logged in`
   - Expect: LogEntry with correct timestamp, level, environment, message

2. **Parse multi-line log entry with stack trace**
   - Given: Error log with stack trace
   - Expect: LogEntry with message and context containing full stack trace

3. **Parse all PSR-3 log levels**
   - Given: Entries for each level (emergency, alert, critical, error, warning, notice, info, debug)
   - Expect: Correct level extracted for each

4. **Handle malformed log lines**
   - Given: Lines not matching PSR-3 format
   - Expect: Lines skipped or appended to previous entry context

5. **Detect log files**
   - Given: Multiple .log files in storage/logs/
   - Expect: Array of filenames sorted by date (newest first)

6. **Handle empty log file**
   - Given: Empty file
   - Expect: Empty collection returned

### Feature Tests (SystemLogViewer Page)

**Test File**: `tests/Feature/SystemLogViewerTest.php`

Test cases:

1. **Super admin can access page**
   - Given: User with role = 'super_admin'
   - When: Visit /admin/system-log
   - Expect: HTTP 200, page renders

2. **Non-super admin cannot access page**
   - Given: User with role = 'guru'
   - When: Visit /admin/system-log
   - Expect: HTTP 403

3. **Unauthenticated user redirected to login**
   - Given: No authenticated user
   - When: Visit /admin/system-log
   - Expect: Redirect to login page

4. **Table displays log entries**
   - Given: Log file with 10 entries
   - When: View page
   - Expect: Table shows 10 rows with correct data

5. **Search filters log entries**
   - Given: Log file with various messages
   - When: Search for "User logged in"
   - Expect: Only matching entries shown

6. **Level filter works**
   - Given: Log file with mixed levels
   - When: Filter by "error"
   - Expect: Only error-level entries shown

7. **Pagination works**
   - Given: Log file with 100 entries
   - When: View page
   - Expect: 25 entries per page, pagination controls visible

8. **File selector shows multiple files**
   - Given: Multiple log files exist
   - When: View page
   - Expect: Dropdown with all files, most recent selected

9. **Empty state shown when no logs**
   - Given: No log files exist
   - When: View page
   - Expect: Empty state message displayed

10. **Badge colors match log levels**
    - Given: Log entries with different levels
    - When: View table
    - Expect: Error/critical = red, warning = yellow, info = blue, debug = gray

### Integration Tests

Test cases:

1. **End-to-end log viewing**
   - Create real log file with test data
   - Visit page as super_admin
   - Verify all entries displayed correctly
   - Clean up test file

2. **File switching**
   - Create multiple log files
   - Select different file from dropdown
   - Verify table updates with new file's entries

## Implementation Notes

### Performance Considerations

1. **File Size Limits**: For files >10MB, consider reading only the last N lines
2. **Caching**: Cache parsed results for 60 seconds to avoid re-parsing on pagination
3. **Memory**: Use generators for large files to avoid loading entire file into memory
4. **Lazy Loading**: Only parse visible page of results, not entire file

### Security Considerations

1. **Path Traversal**: Validate filename to prevent directory traversal attacks
2. **File Type**: Only allow .log extension files
3. **Authorization**: Strictly enforce super_admin role check
4. **Sensitive Data**: Logs may contain sensitive information - ensure proper access control

### Filament-Specific Patterns

1. **Custom Records**: Use `->records()` closure with `LengthAwarePaginator`
2. **Badge Colors**: Use Filament's color system (danger, warning, info, gray)
3. **Empty States**: Use `->emptyStateIcon()`, `->emptyStateHeading()`, `->emptyStateDescription()`
4. **Navigation**: Use `$navigationGroup = 'Sistem'` to group with other system pages
5. **Icons**: Use `Heroicon::OutlinedDocumentText` or similar for log icon

### Code Quality

1. **Type Safety**: Use strict types and return type declarations
2. **Dependency Injection**: Inject LogFileParser via constructor
3. **Single Responsibility**: Keep parsing logic separate from presentation
4. **Testability**: Design for easy mocking and testing
5. **Laravel Conventions**: Follow Laravel coding standards, use Pint for formatting
