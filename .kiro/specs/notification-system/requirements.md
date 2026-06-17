# Requirements Document

## Introduction

Fitur Sistem Notifikasi Global untuk aplikasi manajemen sekolah berbasis Laravel + Filament + Livewire. Sistem ini secara otomatis mengirimkan notifikasi kepada semua role user yang relevan setiap kali terjadi aksi yang melibatkan user lain. Cakupan notifikasi meliputi: pengajuan dan review RPP/KBM (guru & kepsek), rapor tersedia (siswa), pengumuman baru (semua role sesuai target), dan materi baru yang dapat diunduh (siswa di kelas terkait). Notifikasi disimpan secara persisten di database sehingga dapat dibaca ulang kapan saja. UI notifikasi ditampilkan melalui bell icon di navbar setiap panel Filament (semua panel), dengan polling otomatis untuk memperbarui tampilan tanpa WebSocket.

## Glossary

- **Notification_System**: Subsistem yang bertanggung jawab membuat, menyimpan, dan menampilkan notifikasi kepada user.
- **Notification**: Entitas yang merepresentasikan satu pesan notifikasi yang ditujukan kepada satu user tertentu, tersimpan di tabel `notifications`.
- **Observer**: Laravel Model Observer yang menangkap event pada model domain secara decoupled dari controller.
- **Notification_Widget**: Komponen Livewire yang menampilkan daftar notifikasi di navbar panel dengan bell icon dan badge jumlah notifikasi belum dibaca.
- **Guru**: User dengan role `guru` yang mengajar dan mengajukan RPP serta laporan KBM.
- **Kepsek**: User dengan role `kepala_sekolah` yang mereview dan menyetujui/menolak RPP dan laporan KBM.
- **Siswa**: User dengan role `siswa_ortu` yang menerima notifikasi rapor, pengumuman, dan materi baru.
- **LessonPlan**: Model RPP (Rencana Pelaksanaan Pembelajaran) yang memiliki siklus status: DRAFT → PENDING → APPROVED / REVISED.
- **Kbm**: Model Laporan Kegiatan Belajar Mengajar yang memiliki siklus status: DRAFT → PENDING → APPROVED / REVISED.
- **Rapor**: Model rapor siswa yang memiliki siklus status: DRAFT → FINALIZED → APPROVED.
- **Announcement**: Model pengumuman yang memiliki field `target_role` (array) untuk menentukan role penerima.
- **LessonPlanMaterial**: Model materi pembelajaran yang terhubung ke `LessonPlan` dan dapat diunduh oleh siswa di kelas terkait.
- **Unread_Count**: Jumlah notifikasi milik seorang user yang belum ditandai sebagai dibaca (`is_read = false`).
- **Polling_Interval**: Interval waktu (dalam detik) antara setiap permintaan otomatis Livewire untuk memperbarui data notifikasi.

---

## Requirements

### Requirement 1: Notifikasi Pengajuan RPP ke Kepsek

**User Story:** Sebagai Kepsek, saya ingin menerima notifikasi ketika seorang Guru mengajukan RPP untuk approval, sehingga saya dapat segera mengetahui ada RPP yang perlu direview.

#### Acceptance Criteria

1. WHEN status `LessonPlan` berubah menjadi `PENDING`, THE `Notification_System` SHALL membuat satu `Notification` baru untuk setiap user dengan role `kepala_sekolah` yang aktif.
2. THE `Notification` SHALL menyertakan judul yang mengidentifikasi nama Guru pengaju dan mata pelajaran RPP.
3. THE `Notification` SHALL menyertakan pesan yang menyebutkan nama kelas dan tanggal implementasi RPP.
4. WHEN tidak ada user dengan role `kepala_sekolah` yang aktif, THE `Notification_System` SHALL tidak membuat `Notification` apapun dan tidak melempar exception.

---

### Requirement 2: Notifikasi Hasil Review RPP ke Guru

**User Story:** Sebagai Guru, saya ingin menerima notifikasi ketika Kepsek menyetujui atau meminta revisi RPP saya, sehingga saya dapat segera mengetahui hasil review dan mengambil tindakan yang diperlukan.

#### Acceptance Criteria

1. WHEN status `LessonPlan` berubah menjadi `APPROVED`, THE `Notification_System` SHALL membuat satu `Notification` untuk user yang terhubung dengan `Teacher` pemilik `LessonPlan` tersebut.
2. WHEN status `LessonPlan` berubah menjadi `REVISED`, THE `Notification_System` SHALL membuat satu `Notification` untuk user yang terhubung dengan `Teacher` pemilik `LessonPlan` tersebut.
3. THE `Notification` untuk status `APPROVED` SHALL menyertakan judul yang mengindikasikan RPP telah disetujui beserta nama mata pelajaran.
4. THE `Notification` untuk status `REVISED` SHALL menyertakan pesan yang memuat catatan revisi (`revision_note`) dari Kepsek.
5. IF `LessonPlan` tidak memiliki relasi `Teacher` yang valid, THEN THE `Notification_System` SHALL mencatat error ke application log dan tidak membuat `Notification`.

---

### Requirement 3: Notifikasi Pengajuan Laporan KBM ke Kepsek

**User Story:** Sebagai Kepsek, saya ingin menerima notifikasi ketika seorang Guru mengajukan laporan KBM untuk approval, sehingga saya dapat segera mengetahui ada laporan yang perlu direview.

#### Acceptance Criteria

1. WHEN status `Kbm` berubah menjadi `PENDING`, THE `Notification_System` SHALL membuat satu `Notification` baru untuk setiap user dengan role `kepala_sekolah` yang aktif.
2. THE `Notification` SHALL menyertakan judul yang mengidentifikasi nama Guru dan tanggal KBM.
3. THE `Notification` SHALL menyertakan pesan yang menyebutkan nama mata pelajaran dan kelas terkait KBM.
4. WHEN tidak ada user dengan role `kepala_sekolah` yang aktif, THE `Notification_System` SHALL tidak membuat `Notification` apapun dan tidak melempar exception.

---

### Requirement 4: Notifikasi Hasil Review Laporan KBM ke Guru

**User Story:** Sebagai Guru, saya ingin menerima notifikasi ketika Kepsek menyetujui atau meminta revisi laporan KBM saya, sehingga saya dapat segera mengetahui hasil review.

#### Acceptance Criteria

1. WHEN status `Kbm` berubah menjadi `APPROVED`, THE `Notification_System` SHALL membuat satu `Notification` untuk user yang terhubung dengan Guru pemilik jadwal `Kbm` tersebut.
2. WHEN status `Kbm` berubah menjadi `REVISED`, THE `Notification_System` SHALL membuat satu `Notification` untuk user yang terhubung dengan Guru pemilik jadwal `Kbm` tersebut.
3. THE `Notification` untuk status `APPROVED` SHALL menyertakan judul yang mengindikasikan laporan KBM telah disetujui beserta tanggal KBM.
4. THE `Notification` untuk status `REVISED` SHALL menyertakan pesan yang memuat catatan revisi (`revision_note`) dari Kepsek.
5. IF `Kbm` tidak memiliki relasi `Schedule` → `Teacher` yang valid, THEN THE `Notification_System` SHALL mencatat error ke application log dan tidak membuat `Notification`.

---

### Requirement 5: Persistensi Notifikasi di Database

**User Story:** Sebagai user (Guru atau Kepsek), saya ingin notifikasi yang saya terima tersimpan secara permanen di database, sehingga saya dapat membacanya kembali kapan saja meskipun sudah menutup browser.

#### Acceptance Criteria

1. THE `Notification_System` SHALL menyimpan setiap `Notification` ke tabel `notifications` dengan kolom: `id`, `user_id`, `title`, `message`, `is_read`, `created_at`.
2. THE `Notification` SHALL dibuat dengan nilai awal `is_read = false`.
3. THE `Notification` SHALL menyimpan `created_at` dengan nilai timestamp saat notifikasi dibuat.
4. WHEN `Notification` dibuat, THE `Notification_System` SHALL memastikan `user_id` merujuk pada user yang valid dan aktif di tabel `users`.

---

### Requirement 11: Notifikasi Rapor Tersedia ke Siswa

**User Story:** Sebagai Siswa, saya ingin menerima notifikasi ketika rapor saya telah disetujui dan tersedia untuk dilihat, sehingga saya dapat segera mengaksesnya.

#### Acceptance Criteria

1. WHEN status `Rapor` berubah menjadi `APPROVED`, THE `Notification_System` SHALL membuat satu `Notification` untuk user yang terhubung dengan `Student` pemilik `Rapor` tersebut.
2. THE `Notification` SHALL menyertakan judul yang mengindikasikan rapor telah tersedia beserta nama tahun ajaran.
3. THE `Notification` SHALL menyertakan pesan yang menginformasikan siswa untuk mengakses rapor mereka.
4. IF `Rapor` tidak memiliki relasi `Student` → `User` yang valid, THEN THE `Notification_System` SHALL mencatat error ke application log dan tidak membuat `Notification`.

---

### Requirement 12: Notifikasi Pengumuman Baru ke Semua Role

**User Story:** Sebagai user (semua role), saya ingin menerima notifikasi ketika ada pengumuman baru yang ditujukan kepada role saya, sehingga saya tidak melewatkan informasi penting.

#### Acceptance Criteria

1. WHEN `Announcement` baru dibuat, THE `Notification_System` SHALL membuat satu `Notification` untuk setiap user aktif yang role-nya termasuk dalam field `target_role` pengumuman tersebut.
2. THE `Notification` SHALL menyertakan judul yang mengidentifikasi judul pengumuman.
3. THE `Notification` SHALL menyertakan pesan ringkas dari konten pengumuman (maksimal 100 karakter, diakhiri "..." jika terpotong).
4. WHEN `target_role` pengumuman kosong atau tidak ada user aktif yang cocok, THE `Notification_System` SHALL tidak membuat `Notification` apapun dan tidak melempar exception.
5. THE `Notification_System` SHALL hanya mengirim notifikasi kepada user yang `is_active = true`.

---

### Requirement 13: Notifikasi Materi Baru ke Siswa

**User Story:** Sebagai Siswa, saya ingin menerima notifikasi ketika ada materi pembelajaran baru yang dapat diunduh untuk kelas saya, sehingga saya dapat segera mengaksesnya.

#### Acceptance Criteria

1. WHEN `LessonPlanMaterial` baru dibuat, THE `Notification_System` SHALL membuat satu `Notification` untuk setiap user `siswa_ortu` aktif yang terhubung dengan `Student` di kelas yang sama dengan `LessonPlan` terkait.
2. THE `Notification` SHALL menyertakan judul yang mengidentifikasi nama mata pelajaran dan nama file materi.
3. THE `Notification` SHALL menyertakan pesan yang menyebutkan nama kelas dan nama guru pengajar.
4. IF `LessonPlanMaterial` tidak memiliki relasi `LessonPlan` → `SchoolClass` yang valid, THEN THE `Notification_System` SHALL mencatat error ke application log dan tidak membuat `Notification`.
5. WHEN tidak ada siswa aktif di kelas terkait, THE `Notification_System` SHALL tidak membuat `Notification` apapun dan tidak melempar exception.

---

### Requirement 6: Tampilan Notifikasi di Navbar (Bell Icon)

**User Story:** Sebagai user (semua role), saya ingin melihat notifikasi saya melalui bell icon di navbar panel Filament, sehingga saya dapat dengan mudah mengakses notifikasi tanpa meninggalkan halaman yang sedang dibuka.

#### Acceptance Criteria

1. THE `Notification_Widget` SHALL menampilkan bell icon di area navbar pada semua panel (`guru`, `kepsek`, `student`, `admin`).
2. WHILE `Unread_Count` lebih dari nol, THE `Notification_Widget` SHALL menampilkan badge numerik pada bell icon yang menunjukkan jumlah notifikasi belum dibaca milik user yang sedang login.
3. WHEN user mengklik bell icon, THE `Notification_Widget` SHALL menampilkan daftar notifikasi terbaru milik user yang sedang login, diurutkan dari yang paling baru.
4. THE `Notification_Widget` SHALL menampilkan maksimal 10 notifikasi terbaru dalam daftar dropdown.
5. WHEN daftar notifikasi ditampilkan, THE `Notification_Widget` SHALL menampilkan judul, pesan ringkas, dan waktu relatif (contoh: "5 menit yang lalu") untuk setiap notifikasi.
6. WHILE notifikasi belum dibaca, THE `Notification_Widget` SHALL menampilkan indikator visual yang membedakan notifikasi belum dibaca dari yang sudah dibaca.

---

### Requirement 7: Menandai Notifikasi sebagai Dibaca

**User Story:** Sebagai user, saya ingin notifikasi otomatis ditandai sebagai dibaca ketika saya membukanya, sehingga badge jumlah notifikasi belum dibaca berkurang secara akurat.

#### Acceptance Criteria

1. WHEN user mengklik satu notifikasi di daftar dropdown, THE `Notification_System` SHALL mengubah nilai `is_read` notifikasi tersebut menjadi `true`.
2. WHEN user mengklik tombol "Tandai Semua Dibaca", THE `Notification_System` SHALL mengubah nilai `is_read` menjadi `true` untuk semua notifikasi milik user yang sedang login.
3. WHEN `is_read` diubah menjadi `true`, THE `Notification_Widget` SHALL memperbarui `Unread_Count` dan badge pada bell icon secara langsung tanpa memuat ulang halaman.
4. THE `Notification_System` SHALL hanya mengizinkan user untuk menandai notifikasi miliknya sendiri sebagai dibaca.

---

### Requirement 8: Pembaruan UI Otomatis via Polling

**User Story:** Sebagai user, saya ingin daftar notifikasi diperbarui secara otomatis tanpa harus me-refresh halaman, sehingga saya dapat menerima notifikasi baru dalam waktu yang wajar.

#### Acceptance Criteria

1. THE `Notification_Widget` SHALL melakukan polling ke server setiap 15 detik untuk memeriksa notifikasi baru.
2. WHEN polling menemukan notifikasi baru, THE `Notification_Widget` SHALL memperbarui `Unread_Count` dan daftar notifikasi secara otomatis.
3. WHILE user tidak sedang membuka dropdown notifikasi, THE `Notification_Widget` SHALL tetap melakukan polling di background.
4. THE `Notification_Widget` SHALL menggunakan mekanisme polling bawaan Livewire (`wire:poll`) sehingga tidak memerlukan WebSocket atau koneksi persisten.

---

### Requirement 9: Isolasi Notifikasi per User

**User Story:** Sebagai user, saya ingin hanya melihat notifikasi yang ditujukan kepada saya, sehingga privasi dan relevansi informasi terjaga.

#### Acceptance Criteria

1. THE `Notification_Widget` SHALL hanya menampilkan notifikasi dengan `user_id` yang sesuai dengan `id` user yang sedang login.
2. THE `Notification_System` SHALL tidak mengizinkan user mengakses atau memodifikasi notifikasi milik user lain.
3. WHEN query notifikasi dieksekusi, THE `Notification_System` SHALL selalu menyertakan filter `user_id = auth()->id()` pada query tersebut.

---

### Requirement 10: Decoupled Event Handling via Observer

**User Story:** Sebagai developer, saya ingin logika pengiriman notifikasi dipisahkan dari logika bisnis model domain, sehingga kode tetap bersih dan mudah dipelihara.

#### Acceptance Criteria

1. THE `Notification_System` SHALL menggunakan Laravel Model Observer untuk mendeteksi event pada `LessonPlan`, `Kbm`, `Rapor`, `Announcement`, dan `LessonPlanMaterial`.
2. THE `Observer` SHALL dipanggil secara otomatis oleh framework tanpa memerlukan pemanggilan eksplisit dari controller atau action Filament.
3. Observer untuk `LessonPlan`, `Kbm`, dan `Rapor` SHALL hanya memproses event `updated` dan hanya bereaksi ketika kolom `status` berubah nilainya.
4. Observer untuk `Announcement` dan `LessonPlanMaterial` SHALL memproses event `created`.
5. IF pembuatan `Notification` gagal karena exception, THEN THE `Observer` SHALL mencatat error ke application log tanpa menggagalkan transaksi utama.
