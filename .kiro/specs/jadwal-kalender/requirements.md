# Requirements Document

## Introduction

Fitur ini mengubah tampilan halaman Jadwal Pelajaran dari tabel Filament biasa menjadi tampilan kalender interaktif menggunakan library `guava/calendar` (Filament plugin). Kalender menampilkan jadwal pelajaran mingguan yang berulang dalam format bulanan, dengan kemampuan beralih ke tampilan harian ketika pengguna mengklik suatu hari. Event jadwal yang berlangsung pada jam yang sama digabungkan menjadi satu event dengan format ringkas untuk menghemat ruang tampilan.

## Glossary

- **CalendarWidget**: Kelas Livewire dari library `guava/calendar` yang menjadi dasar widget kalender di Filament.
- **CalendarEvent**: Value object dari library `guava/calendar` yang merepresentasikan satu event di kalender.
- **Event Gabungan**: Satu `CalendarEvent` yang merepresentasikan beberapa jadwal dengan mata pelajaran dan waktu yang sama namun kelas berbeda.
- **Jadwal**: Record di tabel `schedules` yang berisi informasi kelas, mata pelajaran, guru, hari, jam mulai, dan jam selesai.
- **Tampilan Bulanan**: View kalender `dayGridMonth` yang menampilkan satu bulan penuh dengan event per hari.
- **Tampilan Harian**: View kalender `timeGridDay` yang menampilkan detail jadwal dalam satu hari tertentu.
- **JadwalKalenderWidget**: Nama kelas widget kalender yang akan dibuat untuk fitur ini.
- **ScheduleResource**: Resource Filament yang sudah ada untuk manajemen jadwal pelajaran.
- **FetchInfo**: Value object dari `guava/calendar` yang berisi rentang tanggal yang sedang ditampilkan kalender.

---

## Requirements

### Requirement 1: Instalasi dan Konfigurasi Library

**User Story:** Sebagai developer, saya ingin library `guava/calendar` terpasang dan terkonfigurasi dengan benar, sehingga widget kalender dapat berfungsi di dalam panel Filament.

#### Acceptance Criteria

1. THE System SHALL memiliki package `guava/calendar` versi yang kompatibel dengan Filament v5 terpasang via Composer.
2. WHEN perintah `php artisan filament:assets` dijalankan, THE System SHALL mempublikasikan aset JavaScript dari `guava/calendar`.
3. THE System SHALL menyertakan direktif `@source` untuk path vendor `guava/calendar` di dalam file CSS tema Filament kustom yang aktif.

---

### Requirement 2: Widget Kalender Bulanan sebagai Tampilan Utama

**User Story:** Sebagai pengguna (admin, kepala sekolah, atau guru), saya ingin melihat jadwal pelajaran dalam format kalender bulanan, sehingga saya dapat memahami distribusi jadwal secara visual dalam satu bulan.

#### Acceptance Criteria

1. THE JadwalKalenderWidget SHALL meng-extend kelas `CalendarWidget` dari library `guava/calendar`.
2. THE JadwalKalenderWidget SHALL menampilkan tampilan bulanan (`dayGridMonth`) sebagai tampilan default saat pertama kali dibuka.
3. THE JadwalKalenderWidget SHALL menampilkan event jadwal pelajaran pada setiap hari yang memiliki jadwal.
4. WHEN pengguna mengklik tombol navigasi bulan (sebelumnya/berikutnya), THE JadwalKalenderWidget SHALL memperbarui tampilan kalender ke bulan yang dipilih.
5. THE JadwalKalenderWidget SHALL ditampilkan pada halaman index `ScheduleResource` sebagai pengganti atau pelengkap tabel yang ada.

---

### Requirement 3: Tampilan Harian via Klik Hari

**User Story:** Sebagai pengguna, saya ingin mengklik suatu hari di kalender bulanan untuk melihat detail jadwal hari tersebut, sehingga saya dapat melihat informasi jadwal yang lebih lengkap dan terperinci.

#### Acceptance Criteria

1. WHEN pengguna mengklik sebuah kotak hari di tampilan bulanan, THE JadwalKalenderWidget SHALL beralih ke tampilan harian (`timeGridDay`) untuk hari yang diklik.
2. WHEN tampilan harian aktif, THE JadwalKalenderWidget SHALL menampilkan semua jadwal pada hari tersebut dalam format timeline berdasarkan jam.
3. WHEN tampilan harian aktif, THE JadwalKalenderWidget SHALL menyediakan tombol atau navigasi untuk kembali ke tampilan bulanan.
4. WHEN pengguna mengklik tombol navigasi hari (sebelumnya/berikutnya) di tampilan harian, THE JadwalKalenderWidget SHALL berpindah ke hari sebelumnya atau berikutnya.

---

### Requirement 4: Pengambilan Data Jadwal sebagai Event Kalender

**User Story:** Sebagai pengguna, saya ingin melihat data jadwal pelajaran yang tersimpan di database ditampilkan sebagai event di kalender, sehingga informasi yang ditampilkan selalu akurat dan terkini.

#### Acceptance Criteria

1. WHEN kalender memuat data untuk rentang tanggal tertentu, THE JadwalKalenderWidget SHALL mengambil semua record `Jadwal` yang hari-nya (`day_of_week`) jatuh dalam rentang tanggal yang disediakan oleh `FetchInfo`.
2. THE JadwalKalenderWidget SHALL memuat relasi `schoolClass`, `subject`, dan `teacher.user` secara eager loading saat mengambil data jadwal.
3. WHEN data jadwal diambil, THE JadwalKalenderWidget SHALL mengonversi setiap jadwal menjadi `CalendarEvent` dengan tanggal konkret yang sesuai dengan hari dalam rentang tampilan kalender.
4. IF tidak ada jadwal untuk hari tertentu, THEN THE JadwalKalenderWidget SHALL menampilkan hari tersebut tanpa event (kosong).

#### Correctness Properties

- **Property 4.A (Kelengkapan Event):** Untuk semua kombinasi rentang tanggal yang valid, jumlah event yang dikembalikan oleh `getEvents` harus sama dengan jumlah jadwal yang hari-nya (`day_of_week`) jatuh dalam rentang tersebut (sebelum penggabungan).
- **Property 4.B (Konsistensi Tanggal Event):** Untuk semua event yang dihasilkan, tanggal `start` event harus merupakan hari yang sesuai dengan nilai `day_of_week` jadwal sumber dalam rentang tampilan yang aktif.

---

### Requirement 5: Format Event Gabungan

**User Story:** Sebagai pengguna, saya ingin jadwal dengan mata pelajaran yang sama pada jam yang sama digabungkan menjadi satu event ringkas, sehingga tampilan kalender tidak terlalu padat dan mudah dibaca.

#### Acceptance Criteria

1. WHEN beberapa jadwal memiliki `subject_id` yang sama, `day_of_week` yang sama, `start_time` yang sama, dan `end_time` yang sama, THE JadwalKalenderWidget SHALL menggabungkannya menjadi satu `CalendarEvent`.
2. THE JadwalKalenderWidget SHALL memformat judul event gabungan dengan pola: `HH:MM: [Nama Mata Pelajaran] - [Kelas A], [Kelas B], [Kelas C]`.
3. WHEN jadwal tidak memiliki pasangan yang dapat digabungkan (unik), THE JadwalKalenderWidget SHALL memformat judulnya dengan pola: `HH:MM: [Nama Mata Pelajaran] - [Nama Kelas]`.
4. THE JadwalKalenderWidget SHALL mengurutkan nama kelas dalam event gabungan secara alfabetis.

#### Correctness Properties

- **Property 5.A (Kelengkapan Penggabungan):** Untuk semua kumpulan jadwal dengan mata pelajaran, hari, jam mulai, dan jam selesai yang sama, semua nama kelas harus muncul dalam judul satu event gabungan — tidak ada kelas yang hilang.
- **Property 5.B (Tidak Ada Duplikasi):** Untuk semua kumpulan jadwal yang diproses, setiap kombinasi unik (subject + day + start_time + end_time) harus menghasilkan tepat satu `CalendarEvent` — tidak ada event duplikat.
- **Property 5.C (Idempotency Penggabungan):** Menjalankan logika penggabungan dua kali pada data yang sama harus menghasilkan jumlah event yang sama dengan menjalankannya sekali.

---

### Requirement 6: Scoping Jadwal Berdasarkan Role Pengguna

**User Story:** Sebagai guru, saya ingin kalender hanya menampilkan jadwal mengajar saya sendiri, sehingga saya tidak terganggu oleh jadwal guru lain.

#### Acceptance Criteria

1. WHILE pengguna yang login memiliki role `guru`, THE JadwalKalenderWidget SHALL hanya menampilkan jadwal yang `teacher_id`-nya sesuai dengan ID teacher pengguna tersebut.
2. WHILE pengguna yang login memiliki role `super_admin` atau `kepala_sekolah`, THE JadwalKalenderWidget SHALL menampilkan semua jadwal dari semua guru.
3. IF pengguna memiliki temporary policy grant untuk melihat jadwal, THEN THE JadwalKalenderWidget SHALL menerapkan pembatasan level yang sesuai dari `TemporaryAccessManager`.
4. IF pengguna tidak memiliki akses ke halaman jadwal, THEN THE JadwalKalenderWidget SHALL tidak menampilkan data apapun.

---

### Requirement 7: Akses Halaman Kalender

**User Story:** Sebagai pengguna yang berwenang, saya ingin dapat mengakses halaman kalender jadwal, sehingga saya dapat melihat jadwal pelajaran.

#### Acceptance Criteria

1. WHEN pengguna dengan role `super_admin`, `kepala_sekolah`, atau `guru` mengakses halaman jadwal, THE ScheduleResource SHALL menampilkan halaman dengan `JadwalKalenderWidget`.
2. IF pengguna tidak terautentikasi atau tidak memiliki role yang diizinkan, THEN THE ScheduleResource SHALL menolak akses dan mengarahkan ke halaman login atau menampilkan pesan error.
3. THE ScheduleResource SHALL mempertahankan kemampuan CRUD (Create, Edit, Delete) jadwal yang sudah ada, terpisah dari tampilan kalender.
