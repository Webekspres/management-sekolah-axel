# Design Document: Activity Log (Audit Trail)

## Overview

Fitur ini mengimplementasikan audit trail terpusat dengan meng-extend sistem `activity_logs` yang sudah ada di proyek. Tidak ada dependency baru yang ditambahkan — desain sepenuhnya berbasis pada tabel, model, dan konvensi yang sudah ada.

**Keputusan utama:**

- Tabel `activity_logs` di-extend dengan dua kolom baru: `log_name` (kategori modul) dan `properties` (JSON untuk old/new values saat update).
- Trait `LogsActivity` dibuat sendiri menggunakan Eloquent model events (`created`, `updated`, `deleted`), menggantikan `recordActivity()` manual di `LessonPlan` dan `Kbm`.
- Login/logout dicatat via Laravel event listeners yang menulis ke tabel yang sama.
- `ActivityLogPage` mengikuti pola `TemporaryAccessLogList` yang sudah ada.

---

## Architecture

```mermaid
graph TD
    subgraph "Event Sources"
        A[Eloquent Model Events<br/>created / updated / deleted]
        B[Auth Events<br/>Login / Logout]
        C[Custom Domain Events<br/>approve / generate / download]
    end

    subgraph "Logging Layer"
        D[LogsActivity Trait<br/>app/Models/Traits/LogsActivity.php]
        E[AuthActivityListener<br/>app/Listeners/AuthActivityListener.php]
        F[recordActivity() helper<br/>dipanggil manual di domain methods]
    end

    subgraph "Storage"
        G[(activity_logs table<br/>+ log_name + properties)]
    end

    subgraph "Presentation"
        H[ActivityLogPage<br/>app/Filament/Pages/ActivityLogPage.php]
    end

    A --> D --> G
    B --> E --> G
    C --> F --> G
    G --> H
```

**Alur data:**

1. Model event terjadi → `LogsActivity` trait menangkap via `static::created/updated/deleted` → tulis ke `activity_logs`.
2. User login/logout → `AuthActivityListener` menangkap `Illuminate\Auth\Events\Login/Logout` → tulis ke `activity_logs`.
3. Domain action (approve RPP, generate rapor, dll.) → method domain memanggil `$this->recordActivity()` → tulis ke `activity_logs`.
4. `ActivityLogPage` membaca dari `ActivityLog::query()` dengan eager load `user`.

---

## Components and Interfaces

### 1. Migration: Extend `activity_logs`

File: `database/migrations/YYYY_MM_DD_extend_activity_logs_table.php`

Menambahkan dua kolom ke tabel yang sudah ada:

- `log_name` (`string`, nullable) — kategori modul: `auth`, `spp`, `absensi`, `rpp`, `rapor`, `jadwal`, `user`, `siswa`, `guru`.
- `properties` (`json`, nullable) — menyimpan `['old' => [...], 'new' => [...]]` saat update.

Index tambahan pada `log_name` dan `user_id` (sudah ada foreign key, tapi perlu index eksplisit untuk filter).

### 2. Trait `LogsActivity`

File: `app/Models/Traits/LogsActivity.php`

```php
trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => static::writeActivityLog($model, 'created'));
        static::updated(fn (Model $model) => static::writeActivityLog($model, 'updated'));
        static::deleted(fn (Model $model) => static::writeActivityLog($model, 'deleted'));
    }

    private static function writeActivityLog(Model $model, string $action): void
    {
        ActivityLog::create([
            'user_id'     => auth()->id(),          // null jika tidak ada sesi
            'action'      => $action,
            'entity_type' => static::class,
            'entity_id'   => $model->getKey(),
            'log_name'    => static::getActivityLogName(),
            'description' => $action . ' ' . class_basename(static::class),
            'properties'  => $action === 'updated'
                ? ['old' => $model->getOriginal(), 'new' => $model->getDirty()]
                : null,
        ]);
    }

    /**
     * Override di model untuk set log_name yang tepat.
     */
    public static function getActivityLogName(): string
    {
        return 'general';
    }
}
```

Setiap model yang menggunakan trait ini **harus** meng-override `getActivityLogName()` untuk mengembalikan nama modul yang sesuai.

**Model yang perlu ditambahkan trait:**

| Model | `getActivityLogName()` |
|---|---|
| `Attendance` | `'absensi'` |
| `Payment` | `'spp'` |
| `Invoice` | `'spp'` |
| `Rapor` | `'rapor'` |
| `Schedule` | `'jadwal'` |
| `User` | `'user'` |
| `Student` | `'siswa'` |
| `Teacher` | `'guru'` |

**Refactor model yang sudah ada:**

`LessonPlan` dan `Kbm` sudah punya `recordActivity()` manual untuk domain events (submit, approve, revise). Pendekatan yang diambil:

- Tambahkan `LogsActivity` trait untuk menangkap CRUD otomatis (`created`, `updated`, `deleted`).
- Pertahankan `recordActivity()` manual untuk domain events yang butuh `action` dan `description` spesifik (misalnya `lesson_plan_approved`).
- `getActivityLogName()` → `'rpp'` untuk `LessonPlan`, `'kbm'` untuk `Kbm`.

### 3. `AuthActivityListener`

File: `app/Listeners/AuthActivityListener.php`

```php
class AuthActivityListener
{
    public function handleLogin(Login $event): void
    {
        ActivityLog::create([
            'user_id'     => $event->user->id,
            'action'      => 'login',
            'entity_type' => User::class,
            'entity_id'   => $event->user->id,
            'log_name'    => 'auth',
            'description' => 'User login: ' . $event->user->name,
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user === null) {
            return;
        }

        ActivityLog::create([
            'user_id'     => $event->user->id,
            'action'      => 'logout',
            'entity_type' => User::class,
            'entity_id'   => $event->user->id,
            'log_name'    => 'auth',
            'description' => 'User logout: ' . $event->user->name,
        ]);
    }
}
```

Listener didaftarkan di `AppServiceProvider::boot()` menggunakan `Event::listen()`, konsisten dengan pola `registerGlobalDeletionValidation()` yang sudah ada.

### 4. `ActivityLogPage`

File: `app/Filament/Pages/ActivityLogPage.php`

Mengikuti pola `TemporaryAccessLogList` dan `SystemLogViewer`:

```php
class ActivityLogPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static ?string $navigationLabel = 'Activity Log';
    protected static ?string $title = 'Activity Log';
    protected static ?string $slug = 'activity-log';
    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';
    protected static ?int $navigationSort = 100; // setelah System Log (99)

    protected string $view = 'filament.pages.activity-log-page';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public function table(Table $table): Table { ... }
}
```

**Kolom tabel:**

| Kolom | Sumber | Keterangan |
|---|---|---|
| User | `user.name` | Dengan email sebagai description |
| Action | `action` | Badge dengan warna per event |
| Modul | `log_name` | Badge |
| Entity | `entity_type` + `entity_id` | `class_basename(entity_type)` + ID |
| Deskripsi | `description` | Wrap, limit 80 char |
| Waktu | `created_at` | Format `d M Y H:i` + since |

**Badge colors untuk `action`:**

| Action | Warna |
|---|---|
| `created` | `success` |
| `updated` | `warning` |
| `deleted` | `danger` |
| `login`, `logout` | `info` |
| `downloaded`, `generated` | `primary` |
| `approved` | `success` |

**Filters:**

- `SelectFilter` → `user_id` (searchable, relationship ke `user`)
- `SelectFilter` → `action` (semua event types)
- `SelectFilter` → `log_name` (semua modul)
- `Filter` custom → date range pada `created_at`

**Search:** pada `description` dan `user.name` via `->searchable()` dengan join.

---

## Data Models

### Skema `activity_logs` (setelah migration)

```
activity_logs
├── id           ULID (PK)
├── user_id      ULID (FK → users, nullable)   ← sudah ada, ubah ke nullable
├── action       VARCHAR(255)                   ← sudah ada
├── entity_type  VARCHAR(255)                   ← sudah ada
├── entity_id    VARCHAR(255) nullable          ← sudah ada
├── log_name     VARCHAR(100) nullable          ← BARU
├── description  TEXT                           ← sudah ada
├── properties   JSON nullable                  ← BARU
└── created_at   TIMESTAMP                      ← sudah ada
```

**Catatan penting:** Kolom `user_id` saat ini `NOT NULL` dengan foreign key constraint. Perlu diubah ke `nullable` untuk mendukung logging dari seeder/command tanpa user aktif (Requirement 2.6).

**Index yang ditambahkan:**

- `INDEX(log_name)` — untuk filter modul
- `INDEX(action)` — untuk filter event type
- `INDEX(created_at)` — sudah ada implisit, tapi perlu eksplisit untuk sort performa

### Model `ActivityLog` (update)

```php
class ActivityLog extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id',
        'log_name', 'description', 'properties',  // tambah log_name & properties
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'properties' => 'array',  // tambah cast untuk JSON
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Causer invariant

*For any* authenticated user performing any model operation (create, update, or delete) on any model that uses the `LogsActivity` trait, the resulting `ActivityLog` entry's `user_id` SHALL equal that user's `id`.

**Validates: Requirements 2.3, 9.6**

### Property 2: Model event logging completeness

*For any* model that uses the `LogsActivity` trait, performing a `created`, `updated`, or `deleted` operation SHALL produce exactly one `ActivityLog` entry with the matching `action` value and the correct `entity_type`.

**Validates: Requirements 2.1**

### Property 3: Update properties round-trip

*For any* model update where attributes are changed, the resulting `ActivityLog.properties` SHALL contain both the original values (under key `old`) and the new values (under key `new`), such that `properties['old'][field] !== properties['new'][field]` for every changed field.

**Validates: Requirements 2.2**

### Property 4: Auth event logging

*For any* user who successfully logs in, an `ActivityLog` entry SHALL be created with `action = 'login'` and `log_name = 'auth'` and `user_id = user.id`. Conversely, *for any* user who logs out, an `ActivityLog` entry SHALL be created with `action = 'logout'` and `log_name = 'auth'`.

**Validates: Requirements 3.1, 3.2**

### Property 5: Failed login produces no log

*For any* login attempt with invalid credentials, NO `ActivityLog` entry SHALL be created.

**Validates: Requirements 3.4**

### Property 6: Access control invariant

*For any* user whose `role` is not `super_admin`, attempting to access the `ActivityLogPage` SHALL result in a 403 response, regardless of what other roles or permissions that user may have.

**Validates: Requirements 1.1, 1.2**

### Property 7: Filter AND logic

*For any* combination of active filters (user, action, log_name, date range) applied simultaneously on the `ActivityLogPage`, every record returned SHALL satisfy ALL active filter conditions.

**Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5**

### Property 8: Table is read-only

*For any* user (including `super_admin`), the `ActivityLogPage` table SHALL expose no create, edit, or delete actions — the table is strictly read-only.

**Validates: Requirements 8.5**

---

## Error Handling

### Logging failures tidak boleh mengganggu operasi utama

Semua penulisan ke `activity_logs` di dalam `LogsActivity` trait dan `AuthActivityListener` harus dibungkus dengan `try/catch` yang silent — jika logging gagal (misalnya database down sementara), operasi utama (save model, login) tetap berhasil.

```php
private static function writeActivityLog(Model $model, string $action): void
{
    try {
        ActivityLog::create([...]);
    } catch (\Throwable) {
        // Log ke Laravel logger tapi jangan propagate exception
        logger()->warning('ActivityLog write failed', ['model' => static::class, 'action' => $action]);
    }
}
```

### `user_id` nullable

Kolom `user_id` diubah ke nullable. Jika `auth()->id()` mengembalikan `null` (seeder, artisan command, queue job), log tetap ditulis dengan `user_id = null`. Ini lebih baik daripada melempar exception atau skip logging.

### `entity_id` nullable

Sudah nullable di skema existing. Untuk auth events, `entity_id` diisi dengan `user.id` (bukan null) karena subject-nya adalah user itu sendiri.

---

## Testing Strategy

### Unit Tests

Fokus pada logika murni yang tidak butuh database:

- `LogsActivity` trait: verifikasi bahwa `writeActivityLog()` membentuk array data yang benar untuk setiap action type.
- `AuthActivityListener`: verifikasi bahwa `handleLogin()` dan `handleLogout()` membentuk data yang benar dari event object.

### Feature Tests (Pest)

Menggunakan `RefreshDatabase`. Setiap test group menggunakan factory untuk setup data.

**Access control:**

```
tests/Feature/ActivityLog/ActivityLogAccessTest.php
- super_admin dapat mengakses halaman
- setiap role non-super_admin mendapat 403
- unauthenticated redirect ke login
```

**Model event logging:**

```
tests/Feature/ActivityLog/ModelActivityLoggingTest.php
- create model → ActivityLog dibuat dengan action='created'
- update model → ActivityLog dibuat dengan action='updated' + properties berisi old/new
- delete model → ActivityLog dibuat dengan action='deleted'
- user_id = auth user's id (Property 1 & 2)
- tanpa auth → user_id = null
```

**Auth event logging:**

```
tests/Feature/ActivityLog/AuthActivityLoggingTest.php
- login berhasil → ActivityLog dengan action='login', log_name='auth'
- logout → ActivityLog dengan action='logout', log_name='auth'
- login gagal → tidak ada ActivityLog
```

**Filament page:**

```
tests/Feature/ActivityLog/ActivityLogPageTest.php
- tabel menampilkan kolom yang benar
- filter user_id mengembalikan hanya record user tersebut
- filter action mengembalikan hanya record dengan action tersebut
- filter log_name mengembalikan hanya record modul tersebut
- filter date range mengembalikan hanya record dalam rentang
- multiple filter aktif → AND logic
- tidak ada action create/edit/delete di tabel
- search pada description menemukan record yang sesuai
```

### Property-Based Testing

Menggunakan [Pest Arch](https://pestphp.com/) dan pendekatan data-driven dengan Pest datasets untuk mensimulasikan "for any" semantics. Karena proyek menggunakan Pest v4, property-based testing diimplementasikan dengan datasets yang mencakup berbagai kombinasi input.

**Property 1 & 2 — Causer invariant & model event completeness:**

```php
dataset('loggable_models', [
    [Attendance::class, 'absensi'],
    [Payment::class, 'spp'],
    [Invoice::class, 'spp'],
    [Rapor::class, 'rapor'],
    [Schedule::class, 'jadwal'],
    [User::class, 'user'],
]);

it('logs model events with correct causer', function (string $modelClass, string $logName) {
    $actor = User::factory()->create();
    actingAs($actor);
    $model = $modelClass::factory()->create();
    expect(ActivityLog::where('user_id', $actor->id)->where('action', 'created')->exists())->toBeTrue();
})->with('loggable_models');
```

**Property 6 — Access control:**

```php
dataset('non_admin_roles', ['guru', 'kepala_sekolah', 'siswa_ortu']);

it('denies access to non-super_admin roles', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    actingAs($user)->get('/admin/activity-log')->assertForbidden();
})->with('non_admin_roles');
```

**Property 7 — Filter AND logic:**
Diimplementasikan sebagai feature test dengan multiple filter combinations menggunakan Livewire testing helpers.

**Minimum 100 iterasi** tidak berlaku untuk Pest datasets (bukan PBT library seperti QuickCheck). Cakupan dicapai melalui datasets yang komprehensif dan kombinasi filter yang exhaustive.

**Tag format untuk referensi:**

```php
// Feature: activity-log, Property 1: causer invariant
// Feature: activity-log, Property 6: access control invariant
```
