---
description: Use this instruction when tasks touch business boundaries, module scope, approval workflow, parent/student portal visibility, or payment integration.
---

<!-- Tip: Use /create-instructions in chat to generate content with agent assistance -->

# Batasan Pengembangan Sistem

- Platform web responsif via browser (desktop/mobile), bukan aplikasi mobile native.
- Single-entity untuk Homeschooling Tunas Karya Bangsa (SD/SMP/SMA), tanpa sinkronisasi Dapodik otomatis.
- Akun siswa dan orang tua adalah 1 akun terpadu, tanpa pemisahan dashboard.
- Modul Akademik tidak menyediakan video conference bawaan; gunakan layanan pihak ketiga (Zoom/Meet) bila diperlukan.
- Data krusial (pengajuan RPP dan laporan KBM harian) hanya dipublikasikan ke portal siswa/orang tua setelah approval Kepala Sekolah.
- Pembayaran digital SPP memakai payment gateway pihak ketiga; metode, pencairan, dan biaya admin mengikuti kebijakan vendor.

# Ruang Lingkup Development

Sistem adalah portal akademik terpusat untuk administrasi sekolah, pelaksanaan KBM, dan evaluasi siswa secara terintegrasi antara sekolah dan orang tua.

## 1) Modul Manajemen Data & Pengguna

- Fokus: master data sekolah, akun pengguna, konfigurasi sistem inti.
- Fungsi utama: data guru/staf/siswa, kelas & jenjang, manajemen role akses (Admin/Kepsek/Guru/Siswa-Orang Tua), profil web, pengumuman.
- Struktur data utama: `users`, `roles`, `teachers`, `students`, `classes`, `web_configurations`, `announcements`.

## 2) Modul Akademik (Perencanaan & Pelaksanaan)

- Fokus: siklus pembelajaran dari perencanaan hingga validasi pelaksanaan.
- Fungsi utama: approval pengajuan RPP, jadwal pelajaran mingguan, kalender akademik, laporan KBM harian, absensi, upload dokumentasi, revisi/persetujuan Kepsek.
- Struktur data utama: `curriculums`, `lesson_plans`, `submission_logs`, `schedules`, `academic_calendars`, `kbm_reports`, `attendances`, `kbm_documentations`, `approval_logs`.

## 3) Modul Evaluasi & Layanan Siswa

- Fokus: evaluasi pembelajaran dan transparansi informasi orang tua.
- Fungsi utama: input nilai tugas/ujian, rekap absensi, generate e-rapor, portal terpadu (1 akun) untuk jadwal, laporan KBM approved, unduh materi, nilai, dan rapor.
- Struktur data utama: `exams`, `grades`, `report_cards` (terkait `schedules` dan `kbm_reports`).

## 4) Modul Administrasi & Keuangan

- Fokus: digitalisasi administrasi dan pengelolaan keuangan.
- Fungsi utama: pembuatan tagihan SPP, pembayaran digital via gateway, log transaksi, ringkasan keuangan/statistik akademik/notifikasi untuk Kepsek.
- Struktur data utama: `invoices`, `payments`, `payment_gateway_logs`, `dashboard_relations`.

# Guardrails Untuk Agen

- Jangan menambahkan fitur di luar batasan di atas tanpa instruksi eksplisit pengguna.
- Jangan mempublikasikan data akademik ke portal siswa/orang tua jika status approval belum final.
- Jangan memecah akun siswa/orang tua menjadi dua akun terpisah kecuali diminta pengguna.
- Jangan menambahkan integrasi sinkronisasi Dapodik otomatis kecuali diminta pengguna.
