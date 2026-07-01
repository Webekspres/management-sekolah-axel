# Realtime Notifications — Spesifikasi Fitur

**Versi:** 1.0  
**Tanggal:** 1 Juli 2026  
**Penulis:** Buffy (AI Assistant)  
**Status:** Draft — menunggu persetujuan

---

## 1. Ringkasan Eksekutif

Fitur ini bertujuan untuk meningkatkan keandalan dan kecepatan notifikasi pada sistem manajemen sekolah (Homeschooling Tunas Karya Bangsa). Saat ini notifikasi menggunakan polling 15 detik dari Filament, namun sering tidak muncul sama sekali, khususnya setelah navigasi SPA. Pengguna sering harus me-refresh halaman manual untuk melihat notifikasi. Solusi yang diusulkan adalah **short polling yang dipercepat (2-3 detik)** + **perbaikan mekanisme polling SPA** + **notifikasi suara** + **halaman pusat notifikasi**.

---

## 2. Latar Belakang & Masalah

### 2.1 Masalah Utama

1. **Notifikasi tidak muncul realtime** — Polling saat ini 15 detik, tetapi sering notifikasi tidak muncul sama sekali sampai user me-refresh halaman.
2. **Polling berhenti setelah navigasi SPA** — Aplikasi menggunakan `->spa()` di semua panel. Komponen Livewire DatabaseNotifications kemungkinan tidak re-initialize dengan benar setelah navigasi SPA, menyebabkan polling berhenti.
3. **Tidak ada akses terminal** — Hosting adalah shared hosting cPanel tanpa akses SSH/terminal, sehingga tidak bisa menjalankan `php artisan queue:work` sebagai daemon.
4. **Tidak ada budget** — Solusi harus gratis (no cost). Layanan seperti Pusher ($49/bulan) tidak feasible.

### 2.2 Dampak

- Guru mengajukan RPP → Kepala Sekolah tidak mendapat notifikasi, menyebabkan approval tertunda
- Status pembayaran SPP berubah → Siswa/Ortu tidak tahu
- Pengumuman baru tidak sampai ke target role
- Guru tidak tahu RPP/KBM mereka sudah di-approve atau butuh revisi

---

## 3. Lingkungan & Constraints

| Aspek | Detail |
|-------|--------|
| **Framework** | Laravel 13 + Filament 5 + Livewire 4 + Tailwind CSS 4 |
| **Hosting** | Shared hosting cPanel **tanpa akses terminal** |
| **Concurrent users** | 10–50 orang |
| **Budget** | $0 (gratis) |
| **Queue driver** | Database (default) — **tidak bisa** jalankan queue worker |
| **Panel** | Admin, Kepsek, Guru, Student — semuanya SPA mode |
| **Notifikasi saat ini** | Filament `databaseNotifications()` + `databaseNotificationsPolling('15s')` |

---

## 4. Fitur yang Diminta

### 4.1 Notifikasi Realtime (Prioritas Tertinggi)

- Notifikasi muncul dalam **2–3 detik** setelah event terjadi
- Bekerja di **semua panel** (Admin, Kepsek, Guru, Student)
- Tidak berhenti setelah navigasi SPA
- Menggunakan mekanisme **short polling** (bukan WebSocket — lihat analisis teknis)

### 4.2 Suara Notifikasi

- **Suara default simpel** ("ding") ketika notifikasi baru masuk
- Diputar hanya jika halaman sedang aktif (tidak di background tab)
- Opsional: bisa di-toggle on/off oleh user

### 4.3 Pusat Notifikasi (Halaman History)

- Halaman khusus yang menampilkan **semua notifikasi** (tidak hanya yang belum dibaca)
- Bisa di-**search** berdasarkan judul/konten
- Bisa di-**filter** berdasarkan:
  - Status baca / belum baca
  - Jenis notifikasi (RPP, KBM, SPP, Pengumuman)
  - Tanggal (range)
- Bisa di-sort (terbaru → terlama, atau sebaliknya)

### 4.4 Perbaikan Notifikasi yang Tidak Muncul

- Investigasi dan perbaiki akar masalah: polling Livewire DatabaseNotifications yang berhenti setelah navigasi SPA
- Pastikan polling tetap berjalan di semua halaman

---

## 5. Analisis Teknis

### 5.1 Pendekatan yang Dipilih: Short Polling dengan Alpine.js

**Mengapa WebSocket/SSE tidak cocok:**

| Pendekatan | Cocok? | Alasan |
|------------|--------|--------|
| **WebSocket (Reverb/Pusher)** | ❌ | Butuh daemon persistent → tidak mungkin di shared hosting tanpa terminal |
| **Server-Sent Events (SSE)** | ❌ | Butuh koneksi long-lived → host akan kill proses |
| **Long Polling** | ⚠️ | Berisiko di shared hosting dengan koneksi timeout agresif |
| **Short Polling** | ✅ | Paling stabil, pakai HTTP request normal, compatible dengan semua hosting |

### 5.2 Arsitektur yang Diusulkan

```
[Browser] ←→ [Alpine.js polling 2-3 detik] → [GET /notifications/unread]
                                                          ↓
                                              [Read from notifications table]
                                                          ↓
                                              [Return JSON: count + data]
                                                          ↓
[Browser] ← [Update bell icon badge + dropdown + sound effect]
```

1. **Custom Livewire component** atau **Alpine.js polling** menggantikan polling bawaan Filament
2. Request GET ringan ke endpoint yang return JSON (hanya count + 5 notifikasi terbaru)
3. Jika ada notifikasi baru (berdasarkan `created_at` > last check), play sound + update UI
4. Tidak menggunakan queue — notifikasi ditulis langsung ke database (sync)

### 5.3 Mapping Event → Notifikasi

| Event | Trigger | Target | Jenis |
|-------|---------|--------|-------|
| RPP diajukan | `LessonPlanObserver::created()` / `NotificationService` | Semua Kepsek aktif | Broadcast |
| RPP disetujui | `LessonPlanObserver::updated()` | Guru pengaju | Personal |
| RPP revisi | `LessonPlanObserver::updated()` | Guru pengaju | Personal |
| KBM diajukan | `KbmObserver::created()` | Semua Kepsek aktif | Broadcast |
| KBM disetujui | `KbmObserver::updated()` | Guru pengaju | Personal |
| KBM revisi | `KbmObserver::updated()` | Guru pengaju | Personal |
| Rapor disetujui | `RaporObserver::updated()` | Siswa pemilik rapor | Personal |
| Pengumuman baru | `Announcement::created()` | Semua user sesuai target_role | Broadcast |
| Materi baru | `LessonPlanMaterial::created()` | Semua siswa di kelas | Broadcast |
| Pembayaran SPP sukses | `PaymentService::verifyPayment()` | Siswa terkait | Personal |
| Pembayaran SPP gagal | `PaymentService::verifyPayment()` | Siswa terkait | Personal |

### 5.4 Database

Tabel yang sudah ada: `notifications` (dari Laravel `Notifiable` trait — via migrasi default `create_notifications_table`)

Tabel ini sudah cukup untuk menyimpan semua notifikasi. Tidak perlu migrasi baru untuk storage.

Tabel baru yang mungkin diperlukan:
- `notification_preferences` — untuk menyimpan preferensi user (sound on/off, dll) — opsional

---

## 6. Desain UI/UX

### 6.1 Bell Icon (Existing — perlu diperbaiki)

- Posisi: kanan atas, di samping foto profil (existing)
- Badge: menampilkan jumlah notifikasi **belum dibaca**
- Dropdown: menampilkan 5–10 notifikasi terbaru (existing, perlu diperbaiki)
- Klik notifikasi → mark as read + navigasi ke halaman terkait

### 6.2 Suara Notifikasi

- File audio: `public/sounds/notification.mp3` — suara "ding" simpel
- Diputar via HTML5 `Audio` API
- Hanya diputar jika:
  - Halaman aktif (document.visibilityState === 'visible')
  - Notifikasi baru (belum pernah dimunculkan sebelumnya)
  - User belum menonaktifkan suara

### 6.3 Halaman Pusat Notifikasi (Baru)

- Route: `/admin/notifications`, `/kepsek/notifications`, `/guru/notifications`, `/student/notifications`
- Halaman Filament dengan tabel (Table widget atau List page)
- Kolom:
  - Icon status (dibaca/belum)
  - Judul notifikasi (link)
  - Isi notifikasi (preview)
  - Tanggal
- Actions: Tandai dibaca, Tandai semua dibaca, Hapus
- Filter: status, jenis, tanggal
- Search: berdasarkan judul

---

## 7. Implementasi Detail

### 7.1 Komponen yang Aplikasi Dibuat/Diubah

| File | Tipe | Deskripsi |
|------|------|-----------|
| `app/Livewire/NotificationBell.php` | **Baru** | Livewire component untuk bell icon dengan polling 3 detik |
| `resources/views/livewire/notification-bell.blade.php` | **Baru** | View untuk bell icon, dropdown, dan audio |
| `app/Http/Controllers/NotificationController.php` | **Baru** | API endpoint untuk polling ringan (return JSON) |
| `app/Filament/Pages/NotificationCenter.php` | **Baru** | Halaman Filament untuk pusat notifikasi (history) |
| `app/Services/NotificationService.php` | **Revisi** | Tambah dukungan notifikasi SPP |
| `resources/js/notification-sound.js` | **Baru** | JavaScript untuk memutar suara notifikasi |
| `public/sounds/notification.mp3` | **Baru** | File suara notifikasi |
| `app/Providers/Filament/*PanelProvider.php` | **Revisi** | Turunkan polling ke 3 detik + register custom component |

### 7.2 Langkah Implementasi

1. **Buat endpoint API polling ringan** — `GET /api/notifications/unread` mengembalikan count + data terbaru (tanpa Livewire overhead)
2. **Buat Livewire component NotificationBell** — dengan polling 3 detik, menampilkan badge + dropdown
3. **Tambahkan suara notifikasi** — play sound saat ada notifikasi baru
4. **Buat halaman NotificationCenter** — Filament page dengan tabel, filter, search
5. **Perbaiki root cause SPA polling** — pastikan component polling tidak berhenti setelah navigasi
6. **Update PanelProviders** — register component baru, update polling interval
7. **Update NotificationService** — tambah notifikasi untuk event SPP

### 7.3 Optimasi Performa

- Endpoint polling hanya return count + 5 notifikasi terbaru (minim bandwidth)
- Index database pada `notifications` table untuk `notifiable_id`, `read_at`, `created_at`
- Cache count notifikasi belum dibaca (opsional, jika perlu)

---

## 8. Alasan Tidak Menggunakan WebSocket

### 8.1 Constraint Hosting

Shared hosting cPanel:
- ❌ Tidak bisa install Reverb (butuh daemon Node.js/PHP persistent)
- ❌ Tidak bisa pakai Pusher (berbayar, $49/bulan)
- ❌ Tidak bisa jalankan `php artisan queue:work` (tidak ada terminal)
- ❌ SSE/long-polling berisiko di-kill oleh resource limiter

### 8.2 Alternatif yang Tidak Cocok

| Solusi | Masalah |
|--------|---------|
| **Pusher** | Berbayar. Bahkan plan paling murah $49/bulan. |
| **Reverb (Laravel)** | Butuh Node.js daemon atau `php artisan reverb:start` → tidak bisa di shared hosting |
| **Laravel Echo** | Membutuhkan Pusher atau Reverb sebagai backend |
| **Socket.io + Node.js** | Butuh VPS, di luar scope |
| **Firebase Cloud Messaging** | Gratis tapi butuh koneksi eksternal, kompleks |

**Kesimpulan:** Untuk shared hosting tanpa terminal, **short polling 3 detik** adalah satu-satunya pendekatan yang stabil, gratis, dan compatible.

---

## 9. User Story

**Sebagai** Kepala Sekolah  
**Saya ingin** mendapat notifikasi realtime ketika guru mengajukan RPP atau laporan KBM  
**Sehingga** saya bisa segera melakukan review dan approval tanpa harus refresh halaman  

**Sebagai** Guru  
**Saya ingin** mendapat notifikasi + suara ketika RPP/KBM saya di-approve atau diminta revisi  
**Sehingga** saya tidak perlu terus-menerus mengecek status  

**Sebagai** Siswa/Ortu  
**Saya ingin** mendapat notifikasi ketika status pembayaran SPP berubah atau ada pengumuman baru  
**Sehingga** saya tidak ketinggalan informasi penting  

**Sebagai** Semua User  
**Saya ingin** bisa melihat history notifikasi di halaman khusus  
**Sehingga** notifikasi yang terlewat bisa tetap diakses  

---

## 10. Prioritas & Milestone

### MVP (Fase 1) — Core Realtime Fix
1. ✅ Perbaiki polling SPA agar tidak berhenti setelah navigasi
2. ✅ Turunkan polling interval dari 15 detik → 3 detik
3. ✅ Tambahkan suara notifikasi default

### Fase 2 — Pusat Notifikasi
1. ✅ Halaman NotificationCenter dengan tabel + filter + search
2. ✅ API endpoint polling ringan

### Fase 3 — Penyempurnaan
1. ✅ Notifikasi untuk event SPP
2. ✅ Preferensi user (sound on/off)
3. ✅ Fitur "Tandai semua dibaca"

---

## 11. Pertanyaan yang Belum Terjawab

Berikut adalah pertanyaan yang perlu dikonfirmasi sebelum implementasi:

1. Apakah durasi polling 3 detik sudah cukup cepat, atau perlu 2 detik?
2. Apakah notifikasi perlu auto-dismiss setelah beberapa waktu?
3. Apakah notifikasi yang sudah dibaca perlu otomatis terhapus setelah X hari?
4. Format suara "ding" — lebih suara bell klasik atau digital modern?
5. Apakah halaman notifikasi perlu link ke halaman terkait (misal: klik notifikasi RPP → langsung ke halaman detail RPP)?
6. Perlu fitur "notifikasi realtime" di dashboard berupa feed/live activity log?

---

## 12. Glossary

| Istilah | Arti |
|---------|------|
| **SPA** | Single Page Application — navigasi tanpa reload halaman penuh |
| **Short Polling** | HTTP request berulang tiap beberapa detik untuk cek data baru |
| **SSE** | Server-Sent Events — koneksi HTTP satu arah dari server ke client |
| **Livewire** | Full-stack framework untuk Laravel yang membuat UI dinamis tanpa JS |
| **Filament** | Laravel UI framework berbasis Livewire + Tailwind CSS |
| **RPP** | Rencana Pelaksanaan Pembelajaran |
| **KBM** | Kegiatan Belajar Mengajar |
| **SPP** | Sumbangan Pembinaan Pendidikan (biaya sekolah) |

---

*Dokumen ini akan di-update seiring dengan perkembangan implementasi.*
