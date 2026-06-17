# Tasks: KKM Per Kelas

## Task List

- [x] 1. Buat migrasi untuk menambahkan kolom `kkm` pada tabel `classes`
- [x] 2. Update model `SchoolClass` untuk mendukung kolom `kkm`
- [x] 3. Tambah field KKM pada `SchoolClassForm`
- [x] 4. Update `RaporService::generatePdf()` untuk menggunakan KKM kelas
- [x] 5. Update `GradeStatsWidget` untuk menggunakan KKM kelas
- [x] 6. Tulis dan jalankan tests
  - [x] 6.1 Unit test: logika resolusi KKM
  - [x] 6.2 Feature test: form kelas (field KKM, validasi, simpan null)
  - [x] 6.3 Feature test: RaporService menggunakan KKM kelas
  - [x] 6.4 Feature test: GradeStatsWidget menggunakan KKM kelas
  - [x] 6.5 Property test: form menerima semua nilai KKM valid dalam range 0-100
  - [x] 6.6 Property test: form menolak nilai KKM di luar range 0-100
  - [x] 6.7 Property test: prioritas resolusi KKM selalu diikuti
  - [x] 6.8 Property test: penanda below-kkm konsisten dengan KKM yang berlaku
  - [x] 6.9 Property test: GradeStatsWidget menggunakan KKM kelas jika tersedia
