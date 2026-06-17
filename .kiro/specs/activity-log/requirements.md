# Requirements Document: Activity Log (Audit Trail)

## Introduction

Fitur Activity Log mengimplementasikan audit trail terpusat untuk seluruh aktivitas pengguna di aplikasi manajemen sekolah. Setiap aksi penting — mulai dari login/logout hingga operasi CRUD di semua modul (SPP, absensi, RPP, rapor, jadwal, dll.) — dicatat secara otomatis menggunakan package `spatie/laravel-activitylog`. Hanya `super_admin` yang dapat mengakses halaman log ini melalui panel Filament, menggunakan Page dengan `InteractsWithTable` sesuai konvensi proyek.

## Glossary

- **Activity_Log_System**: Sistem pencatatan dan tampilan audit trail aktivitas pengguna
- **Activity**: Satu entri log yang merepresentasikan satu aksi yang dilakukan oleh seorang User
- **Causer**: User yang melakukan aksi yang dicatat
- **Subject**: Model Eloquent yang menjadi objek dari aksi (misalnya: `Spp`, `Attendance`, `Rpp`)
- **Event**: Jenis aksi yang dicatat — `created`, `updated`, `deleted`, `restored`, `login`, `logout`, `downloaded`, `generated`, `approved`
- **Log_Name**: Kategori log untuk mengelompokkan aktivitas (misalnya: `auth`, `spp`, `absensi`, `rpp`, `rapor`)
- **Description**: Teks deskriptif yang menjelaskan aksi secara ringkas (misalnya: "Guru mengunduh rapor siswa Budi")
- **Properties**: Data JSON tambahan yang disimpan bersama Activity, berisi `old` dan `attributes` untuk perubahan data
- **Super_Admin**: Pengguna dengan `role === 'super_admin'` yang memiliki akses eksklusif ke halaman Activity Log
- **Activity_Log_Page**: Halaman Filament (`Page` + `InteractsWithTable`) yang menampilkan daftar Activity

## Requirements

### Requirement 1: Kontrol Akses Halaman Activity Log

**User Story:** As a Super_Admin, I want exclusive access to the activity log page, so that sensitive audit data is not exposed to other roles.

#### Acceptance Criteria

1. THE Activity_Log_System SHALL restrict access to the Activity_Log_Page so that only users with `role === 'super_admin'` can view it, enforced via `canAccess()`.
2. WHEN a user with a role other than `super_admin` attempts to access the Activity_Log_Page URL directly, THE Activity_Log_System SHALL redirect the user to the Filament 403 page.
3. WHEN an unauthenticated user attempts to access the Activity_Log_Page, THE Activity_Log_System SHALL redirect the user to the login page.
4. THE Activity_Log_Page SHALL appear in the Filament navigation under the group `'Sistem'` with a sort order that places it after other system pages, visible only to Super_Admin.

### Requirement 2: Pencatatan Aktivitas Model via Spatie

**User Story:** As a Super_Admin, I want all CRUD operations on every module's models to be automatically logged, so that I have a complete audit trail of data changes.

#### Acceptance Criteria

1. THE Activity_Log_System SHALL automatically log `created`, `updated`, and `deleted` events for all Eloquent models that use the `LogsActivity` trait from `spatie/laravel-activitylog`.
2. WHEN a model is updated, THE Activity_Log_System SHALL store both the old attribute values and the new attribute values in the Activity's `properties` field.
3. THE Activity_Log_System SHALL record the authenticated User as the `causer` for every model event Activity.
4. THE Activity_Log_System SHALL set the `log_name` on each Activity to a value that identifies the module (e.g., `'spp'`, `'absensi'`, `'rpp'`, `'rapor'`, `'jadwal'`, `'user'`).
5. THE Activity_Log_System SHALL set a human-readable `description` on each Activity that identifies the action and the subject (e.g., `"created Spp"`, `"updated Attendance"`).
6. WHEN a model event occurs without an authenticated user (e.g., a seeder or scheduled command), THE Activity_Log_System SHALL record the Activity with a null `causer`.

### Requirement 3: Pencatatan Login dan Logout

**User Story:** As a Super_Admin, I want login and logout events to be recorded in the activity log, so that I can audit user session activity.

#### Acceptance Criteria

1. WHEN a user successfully authenticates, THE Activity_Log_System SHALL create an Activity with `event = 'login'`, `log_name = 'auth'`, and the authenticated User as both `causer` and `subject`.
2. WHEN a user logs out, THE Activity_Log_System SHALL create an Activity with `event = 'logout'`, `log_name = 'auth'`, and the authenticated User as both `causer` and `subject`.
3. THE Activity_Log_System SHALL record login and logout Activities via Laravel event listeners bound to `Illuminate\Auth\Events\Login` and `Illuminate\Auth\Events\Logout`.
4. IF a login attempt fails due to invalid credentials, THEN THE Activity_Log_System SHALL NOT create an Activity for the failed attempt.

### Requirement 4: Pencatatan Aktivitas Khusus (Custom Events)

**User Story:** As a Super_Admin, I want domain-specific actions — such as downloading a report, generating a new report, or approving an RPP — to be recorded in the activity log, so that I can audit high-value business actions beyond basic CRUD.

#### Acceptance Criteria

1. WHEN a Siswa downloads a rapor PDF, THE Activity_Log_System SHALL create an Activity with `event = 'downloaded'`, `log_name = 'rapor'`, the Siswa as `causer`, and the rapor model as `subject`.
2. WHEN a Guru generates a new rapor, THE Activity_Log_System SHALL create an Activity with `event = 'generated'`, `log_name = 'rapor'`, the Guru as `causer`, and the rapor model as `subject`.
3. WHEN a Kepsek approves an RPP, THE Activity_Log_System SHALL create an Activity with `event = 'approved'`, `log_name = 'rpp'`, the Kepsek as `causer`, and the RPP model as `subject`.
4. WHEN a Guru or Admin inputs an SPP payment, THE Activity_Log_System SHALL create an Activity with `event = 'created'`, `log_name = 'spp'`, the acting User as `causer`, and the SPP model as `subject`.
5. WHEN a Guru or Wali_Kelas inputs absensi, THE Activity_Log_System SHALL create an Activity with `event = 'created'` or `'updated'`, `log_name = 'absensi'`, the acting User as `causer`, and the Attendance model as `subject`.
6. THE Activity_Log_System SHALL record custom events using `activity()->causedBy($user)->performedOn($model)->event($event)->log($description)` so that all Activities are stored in the same `activity_log` table.

### Requirement 5: Tampilan Tabel Activity Log

**User Story:** As a Super_Admin, I want to view all activity log entries in a paginated table, so that I can browse and inspect the audit trail efficiently.

#### Acceptance Criteria

1. THE Activity_Log_Page SHALL display Activity records in a table with the following columns: Causer (nama user), Role Causer, Event (jenis aksi), Subject Type (nama model), Subject ID, Log Name (modul), Description, dan Waktu (`created_at`).
2. THE Activity_Log_Page SHALL display the Causer's name and role in the user column, with the email shown as a description below the name.
3. THE Activity_Log_Page SHALL display the `event` column as a badge with distinct colors: `created` → `success`, `updated` → `warning`, `deleted` → `danger`, `login`/`logout` → `info`, `downloaded`/`generated` → `primary`, `approved` → `success`.
4. THE Activity_Log_Page SHALL display the `created_at` column formatted as `d M Y H:i` with a relative time description (e.g., "2 jam yang lalu").
5. THE Activity_Log_Page SHALL paginate the table with a default page size of 25 records, sorted by `created_at` descending.
6. THE Activity_Log_Page SHALL support full-text search on the `description` and `causer` name columns.

### Requirement 6: Filter Activity Log

**User Story:** As a Super_Admin, I want to filter the activity log by user, event type, module, and date range, so that I can quickly find relevant entries without scrolling through all records.

#### Acceptance Criteria

1. THE Activity_Log_Page SHALL provide a `SelectFilter` for `causer_id` that lists all Users by name and is searchable.
2. THE Activity_Log_Page SHALL provide a `SelectFilter` for `event` with options: `created`, `updated`, `deleted`, `login`, `logout`, `downloaded`, `generated`, `approved`.
3. THE Activity_Log_Page SHALL provide a `SelectFilter` for `log_name` (modul) with options corresponding to all registered log names: `auth`, `spp`, `absensi`, `rpp`, `rapor`, `jadwal`, `user`.
4. THE Activity_Log_Page SHALL provide a date-range `Filter` that filters Activity records where `created_at` falls between the selected start date and end date (inclusive).
5. WHEN multiple filters are active simultaneously, THE Activity_Log_Page SHALL apply all filters with AND logic so that only records matching all active criteria are shown.
6. WHEN no filters are active, THE Activity_Log_Page SHALL display all Activity records ordered by `created_at` descending.

### Requirement 7: Navigasi Panel Filament

**User Story:** As a Super_Admin, I want the activity log to appear in the Filament sidebar navigation, so that I can access it quickly from any page in the admin panel.

#### Acceptance Criteria

1. THE Activity_Log_Page SHALL register in the Filament navigation with `$navigationGroup = 'Sistem'`, `$navigationLabel = 'Activity Log'`, and `$navigationIcon = Heroicon::OutlinedClipboardDocumentList`.
2. THE Activity_Log_Page SHALL use `$slug = 'activity-log'` as its URL segment.
3. THE Activity_Log_Page SHALL set `$navigationSort` so that it appears after the System Log Viewer entry within the `'Sistem'` group.
4. THE Activity_Log_Page SHALL NOT appear in the navigation for users whose role is not `super_admin`, enforced by the same `canAccess()` check used for direct URL access.

### Requirement 8: Retensi dan Performa Data Log

**User Story:** As a developer, I want the activity log to remain performant as data grows, so that the Activity_Log_Page loads quickly even with tens of thousands of records.

#### Acceptance Criteria

1. THE Activity_Log_System SHALL use the `activity_log` table provided by `spatie/laravel-activitylog` with its default indexes on `causer_id`, `subject_id`, `subject_type`, and `created_at`.
2. THE Activity_Log_Page SHALL eager load the `causer` relationship when rendering the table to prevent N+1 queries.
3. WHEN the `activity_log` table contains up to 50,000 records, THE Activity_Log_Page SHALL load the first page within 1,000 milliseconds.
4. THE Activity_Log_System SHALL provide an Artisan command (via `spatie/laravel-activitylog`'s built-in `activitylog:clean`) to prune Activity records older than a configurable number of days, defaulting to 365 days.
5. THE Activity_Log_System SHALL NOT delete Activity records through the Filament UI — the table is read-only for all users including Super_Admin.

### Requirement 9: Testing Activity Log

**User Story:** As a developer, I want comprehensive tests for the activity log feature, so that I can ensure correctness and prevent regressions.

#### Acceptance Criteria

1. THE test suite SHALL include a feature test verifying that only `super_admin` can access the Activity_Log_Page, and all other roles receive a 403 response.
2. THE test suite SHALL include a feature test verifying that login and logout events create Activity records with the correct `event`, `log_name`, and `causer`.
3. THE test suite SHALL include a feature test verifying that creating, updating, and deleting a model with `LogsActivity` produces Activity records with the correct `event` and `properties`.
4. THE test suite SHALL include a feature test verifying that the `causer_id`, `event`, `log_name`, and date-range filters on the Activity_Log_Page return only matching records.
5. THE test suite SHALL include a feature test verifying that the Activity_Log_Page table is read-only and exposes no create, edit, or delete actions.
6. FOR ALL Activity records created by model events, the `causer_id` SHALL equal the ID of the authenticated user at the time of the event (invariant property).
