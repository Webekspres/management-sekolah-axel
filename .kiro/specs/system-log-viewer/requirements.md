# Requirements Document

## Introduction

Fitur System Log Viewer adalah halaman khusus admin di dalam panel Filament yang memungkinkan pengguna dengan role `super_admin` untuk melihat, mencari, dan memfilter log aplikasi Laravel secara langsung dari antarmuka web. Halaman ini menggunakan komponen Filament (Table, Columns, Filters, Actions) tanpa dependensi paket pihak ketiga tambahan, membaca file log dari storage Laravel secara langsung.

## Glossary

- **System_Log_Viewer**: Halaman Filament yang menampilkan isi log aplikasi Laravel.
- **Log_Entry**: Satu baris entri log yang terdiri dari timestamp, level, environment, dan pesan log.
- **Log_Level**: Tingkat keparahan log sesuai standar PSR-3: `emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`.
- **Admin**: Pengguna dengan nilai `role` = `super_admin` pada tabel `users`.
- **Log_File**: File log Laravel yang tersimpan di direktori `storage/logs/`, umumnya bernama `laravel.log` atau berformat `laravel-YYYY-MM-DD.log`.
- **Log_Parser**: Komponen internal yang mem-parsing baris teks log menjadi struktur Log_Entry.

---

## Requirements

### Requirement 1: Kontrol Akses

**User Story:** Sebagai admin sistem, saya ingin halaman log hanya dapat diakses oleh super_admin, sehingga informasi sensitif sistem tidak terekspos ke pengguna lain.

#### Acceptance Criteria

1. THE System_Log_Viewer SHALL hanya dapat diakses oleh pengguna dengan `role` = `super_admin`.
2. WHEN pengguna yang tidak memiliki role `super_admin` mencoba mengakses halaman System_Log_Viewer, THE System_Log_Viewer SHALL menolak akses dan mengembalikan respons HTTP 403.
3. WHEN pengguna belum terautentikasi mencoba mengakses halaman System_Log_Viewer, THE System_Log_Viewer SHALL mengalihkan pengguna ke halaman login.

---

### Requirement 2: Tampilan Daftar Log

**User Story:** Sebagai admin, saya ingin melihat daftar entri log dalam format tabel, sehingga saya dapat memantau aktivitas dan error sistem dengan mudah.

#### Acceptance Criteria

1. THE System_Log_Viewer SHALL menampilkan entri log dalam komponen Filament Table.
2. THE System_Log_Viewer SHALL menampilkan kolom berikut untuk setiap Log_Entry: timestamp, log level, environment, dan pesan log.
3. THE System_Log_Viewer SHALL menampilkan log level sebagai badge berwarna sesuai tingkat keparahan:
   - `emergency`, `alert`, `critical`, `error` → warna merah (`danger`)
   - `warning` → warna kuning (`warning`)
   - `notice`, `info` → warna biru (`info`)
   - `debug` → warna abu-abu (`gray`)
4. THE System_Log_Viewer SHALL menampilkan entri log diurutkan dari yang terbaru ke yang terlama secara default.
5. THE System_Log_Viewer SHALL menampilkan entri log dengan pagination untuk membatasi jumlah baris yang ditampilkan per halaman.

---

### Requirement 3: Pemilihan File Log

**User Story:** Sebagai admin, saya ingin memilih file log mana yang ingin saya lihat, sehingga saya dapat memeriksa log dari tanggal atau sesi tertentu.

#### Acceptance Criteria

1. THE System_Log_Viewer SHALL mendeteksi semua file log yang tersedia di direktori `storage/logs/` dengan ekstensi `.log`.
2. WHEN lebih dari satu file log tersedia, THE System_Log_Viewer SHALL menampilkan komponen pemilih file log (select/dropdown) di bagian atas halaman.
3. WHEN admin memilih file log, THE System_Log_Viewer SHALL memuat dan menampilkan entri dari file log yang dipilih.
4. THE System_Log_Viewer SHALL menampilkan file log terbaru sebagai pilihan default.
5. IF tidak ada file log yang ditemukan di direktori `storage/logs/`, THEN THE System_Log_Viewer SHALL menampilkan pesan informatif bahwa tidak ada file log yang tersedia.

---

### Requirement 4: Pencarian dan Filter Log

**User Story:** Sebagai admin, saya ingin mencari dan memfilter entri log, sehingga saya dapat menemukan informasi yang relevan dengan cepat.

#### Acceptance Criteria

1. THE System_Log_Viewer SHALL menyediakan fitur pencarian teks pada kolom pesan log.
2. THE System_Log_Viewer SHALL menyediakan filter berdasarkan Log_Level menggunakan komponen Filament SelectFilter.
3. WHEN admin menerapkan filter Log_Level, THE System_Log_Viewer SHALL hanya menampilkan Log_Entry yang sesuai dengan level yang dipilih.
4. WHEN admin memasukkan teks pencarian, THE System_Log_Viewer SHALL menampilkan hanya Log_Entry yang pesannya mengandung teks tersebut (case-insensitive).

---

### Requirement 5: Parsing Log

**User Story:** Sebagai admin, saya ingin log ditampilkan dalam format yang terstruktur dan mudah dibaca, sehingga saya tidak perlu membaca teks mentah.

#### Acceptance Criteria

1. THE Log_Parser SHALL mem-parsing setiap baris log Laravel dengan format standar: `[YYYY-MM-DD HH:MM:SS] env.LEVEL: message`.
2. WHEN sebuah baris log tidak sesuai format standar, THE Log_Parser SHALL mengelompokkan baris tersebut sebagai bagian dari pesan Log_Entry sebelumnya (stack trace / multiline).
3. THE Log_Parser SHALL mengekstrak timestamp, environment, level, dan pesan dari setiap Log_Entry yang valid.
4. THE System_Log_Viewer SHALL menampilkan stack trace atau konteks tambahan dari Log_Entry dalam format yang dapat diperluas (expandable) atau sebagai tooltip.

---

### Requirement 6: Navigasi Panel

**User Story:** Sebagai admin, saya ingin halaman System Log Viewer mudah ditemukan di navigasi panel Filament, sehingga saya dapat mengaksesnya dengan cepat.

#### Acceptance Criteria

1. THE System_Log_Viewer SHALL terdaftar sebagai item navigasi di panel Filament admin dengan label "System Log".
2. THE System_Log_Viewer SHALL menggunakan ikon yang sesuai dari set Heroicon.
3. THE System_Log_Viewer SHALL dikelompokkan dalam grup navigasi yang relevan (misalnya "Sistem" atau "Manajemen").
