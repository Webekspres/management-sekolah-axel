# Implementation Plan: System Log Viewer

## Overview

Implementasi halaman Filament custom yang memungkinkan `super_admin` melihat, mencari, dan memfilter log aplikasi Laravel. Terdiri dari tiga komponen utama: `LogEntry` DTO, `LogFileParser` service, dan `SystemLogViewer` Filament page.

## Tasks

- [x] 1. Buat LogEntry DTO
  - Buat file `app/DataTransferObjects/LogEntry.php` menggunakan `php artisan make:class`
  - Definisikan readonly properties: `timestamp`, `level`, `environment`, `message`, `context`
  - Gunakan PHP 8 constructor property promotion dengan tipe eksplisit
  - _Requirements: 5.3_

- [x] 2. Buat LogFileParser service
  - [x] 2.1 Buat class `app/Services/LogFileParser.php` menggunakan `php artisan make:class`
    - Implementasikan method `detectLogFiles(): array` — scan `storage/logs/` untuk file `.log`, urutkan dari terbaru
    - Implementasikan method `parseLogFile(string $filename): Collection` — baca file dan kembalikan Collection of LogEntry
    - Implementasikan method `parseLogEntry(string $line, ?LogEntry $previousEntry = null): ?LogEntry` — parse satu baris PSR-3
    - Implementasikan method `isLogLineStart(string $line): bool` — cek apakah baris dimulai dengan pola `[YYYY-MM-DD HH:MM:SS]`
    - Tangani multiline entries (stack trace) dengan menambahkan ke `context` LogEntry sebelumnya
    - Tangani file >10MB: batasi ke N baris terakhir dan tampilkan warning
    - Validasi filename untuk mencegah path traversal (hanya izinkan `.log` extension)
    - _Requirements: 3.1, 5.1, 5.2, 5.3_

  - [ ]* 2.2 Tulis unit test untuk LogFileParser
    - Buat `tests/Unit/Services/LogFileParserTest.php` menggunakan `php artisan make:test --pest --unit LogFileParserTest`
    - Test parse single-line log entry dengan semua komponen benar
    - Test parse multi-line log entry dengan stack trace masuk ke `context`
    - Test semua 8 PSR-3 log levels (emergency, alert, critical, error, warning, notice, info, debug)
    - Test baris malformed dilewati atau digabung ke entry sebelumnya
    - Test `detectLogFiles()` hanya mengembalikan file `.log`
    - Test file log kosong mengembalikan Collection kosong
    - _Requirements: 5.1, 5.2, 5.3_

  - [ ]* 2.3 Tulis property test: Property 1 — Log file detection filters by extension
    - **Property 1: detectLogFiles() hanya mengembalikan file dengan ekstensi `.log`**
    - **Validates: Requirements 3.1**
    - Gunakan dataset dengan berbagai kombinasi nama file (`.log`, `.txt`, `.json`, tanpa ekstensi)
    - Jalankan minimum 100 iterasi
    - _Requirements: 3.1_

  - [ ]* 2.4 Tulis property test: Property 5 — Parse valid log line extracts all components
    - **Property 5: Parser mengekstrak semua komponen dari baris PSR-3 yang valid**
    - **Validates: Requirements 5.1, 5.3**
    - Generate baris log PSR-3 valid secara acak dengan berbagai timestamp, env, level, message
    - Verifikasi semua komponen terekstrak dengan benar
    - Jalankan minimum 100 iterasi
    - _Requirements: 5.1, 5.3_

  - [ ]* 2.5 Tulis property test: Property 6 — Multiline entries grouped with previous
    - **Property 6: Baris non-PSR-3 setelah entry valid digabung ke context entry sebelumnya**
    - **Validates: Requirements 5.2**
    - Generate sequence: satu baris PSR-3 valid diikuti N baris non-PSR-3
    - Verifikasi baris non-PSR-3 masuk ke `context` LogEntry sebelumnya
    - Jalankan minimum 100 iterasi
    - _Requirements: 5.2_

- [x] 3. Checkpoint — Pastikan semua unit dan property test lulus
  - Jalankan `php artisan test --compact --filter=LogFileParser`
  - Pastikan semua test lulus sebelum melanjutkan ke implementasi page

- [x] 4. Buat SystemLogViewer Filament Page
  - [x] 4.1 Buat page class menggunakan `php artisan make:filament-page SystemLogViewer`
    - Letakkan di `app/Filament/Pages/SystemLogViewer.php`
    - Implementasikan `HasTable` interface dan gunakan `InteractsWithTable` trait
    - Tambahkan property `public ?string $selectedLogFile = null`
    - Implementasikan `canAccess(): bool` — hanya izinkan `role = super_admin`
    - Implementasikan `mount(): void` — set `selectedLogFile` ke file terbaru via `detectLogFiles()`
    - Inject `LogFileParser` via constructor
    - Set `$navigationGroup`, `$navigationLabel`, `$navigationIcon` sesuai Requirement 6
    - _Requirements: 1.1, 6.1, 6.2, 6.3_

  - [x] 4.2 Konfigurasi table dengan kolom, filter, search, dan pagination
    - Implementasikan method `table(Table $table): Table`
    - Tambahkan kolom: `timestamp` (TextColumn), `level` (BadgeColumn dengan warna sesuai Req 2.3), `environment` (TextColumn), `message` (TextColumn, searchable)
    - Tambahkan `SelectFilter` untuk log level (Requirement 4.2)
    - Aktifkan search pada kolom message (case-insensitive, Requirement 4.4)
    - Set pagination default 25 per page (Requirement 2.5)
    - Urutkan entries dari terbaru ke terlama (Requirement 2.4)
    - Gunakan `->records()` closure dengan `LengthAwarePaginator` dari hasil `LogFileParser`
    - Tambahkan empty state messages untuk: tidak ada file log, file kosong, tidak ada hasil filter
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 4.2, 4.3, 4.4_

  - [x] 4.3 Tambahkan file selector form component
    - Implementasikan form dengan `Select` component untuk memilih file log
    - Tampilkan selector hanya jika ada lebih dari satu file log (Requirement 3.2)
    - Saat file dipilih, reload table dengan entries dari file baru (Requirement 3.3)
    - Default ke file terbaru (Requirement 3.4)
    - _Requirements: 3.2, 3.3, 3.4_

  - [x] 4.4 Tambahkan expandable context untuk stack trace
    - Tampilkan `context` (stack trace) dalam format yang dapat diperluas atau sebagai tooltip
    - Gunakan Filament table column dengan `->expandable()` atau custom view
    - _Requirements: 5.4_

- [x] 5. Buat Blade view template
  - Buat `resources/views/filament/pages/system-log-viewer.blade.php`
  - Render file selector (kondisional, hanya jika >1 file) dan table component
  - Gunakan Filament section styling yang konsisten dengan panel
  - _Requirements: 3.2_

- [x] 6. Checkpoint — Verifikasi halaman dapat diakses dan berfungsi
  - Jalankan `php artisan test --compact --filter=SystemLogViewer`
  - Pastikan route terdaftar dengan `php artisan route:list --path=system-log`

- [x] 7. Tulis feature tests untuk SystemLogViewer page
  - [x] 7.1 Buat `tests/Feature/SystemLogViewerTest.php` menggunakan `php artisan make:test --pest SystemLogViewerTest`
    - Test super_admin dapat mengakses halaman (HTTP 200)
    - Test non-super_admin mendapat HTTP 403 (Requirement 1.2)
    - Test unauthenticated user diredirect ke login (Requirement 1.3)
    - Test table menampilkan log entries dengan benar
    - Test search memfilter entries berdasarkan message (Requirement 4.4)
    - Test level filter hanya menampilkan entries yang sesuai (Requirement 4.3)
    - Test pagination menampilkan 25 entries per halaman (Requirement 2.5)
    - Test file selector muncul saat ada >1 file log (Requirement 3.2)
    - Test empty state saat tidak ada file log (Requirement 3.5)
    - Test badge colors sesuai log level (Requirement 2.3)
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.3, 2.5, 3.2, 3.5, 4.3, 4.4_

  - [ ]* 7.2 Tulis property test: Property 3 — Level filter shows only matching entries
    - **Property 3: Filter level hanya menampilkan entries dengan level yang dipilih**
    - **Validates: Requirements 4.3**
    - Generate collection entries dengan level acak, pilih satu level sebagai filter
    - Verifikasi semua hasil memiliki level yang sama persis dengan filter
    - Jalankan minimum 100 iterasi
    - _Requirements: 4.3_

  - [ ]* 7.3 Tulis property test: Property 4 — Search filter shows only matching messages
    - **Property 4: Search term hanya menampilkan entries yang message-nya mengandung term (case-insensitive)**
    - **Validates: Requirements 4.4**
    - Generate collection entries dengan message acak dan search term acak
    - Verifikasi semua hasil mengandung search term (case-insensitive)
    - Jalankan minimum 100 iterasi
    - _Requirements: 4.4_

  - [ ]* 7.4 Tulis property test: Property 2 — Default file selection chooses most recent
    - **Property 2: File log terbaru (modification time terbaru) dipilih sebagai default**
    - **Validates: Requirements 3.4**
    - Generate set file log dengan modification timestamp berbeda-beda
    - Verifikasi file dengan timestamp terbaru selalu dipilih sebagai default
    - Jalankan minimum 100 iterasi
    - _Requirements: 3.4_

- [x] 8. Jalankan Pint dan pastikan semua test lulus
  - Jalankan `vendor/bin/pint --dirty --format agent` untuk format semua file PHP yang diubah
  - Jalankan `php artisan test --compact` untuk memastikan semua test lulus
  - Pastikan tidak ada regresi pada test yang sudah ada

## Notes

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirements spesifik untuk traceability
- Design document menggunakan PHP/Laravel — tidak perlu pemilihan bahasa
- Property tests menggunakan Pest datasets dengan faker, minimum 100 iterasi per property
- Validasi path traversal wajib diimplementasikan di `LogFileParser` sebelum membaca file
