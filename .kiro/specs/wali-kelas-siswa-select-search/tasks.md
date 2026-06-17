# Tasks: Wali Kelas & Guru Pengajar Select Search Bugfix

## Task List

- [x] 1. Exploratory Testing — Buktikan Bug Ada
  - [x] 1.1 Tulis test untuk membuktikan pencarian nama guru di `SchoolClassForm` (Wali Kelas) gagal pada kode unfixed
  - [x] 1.2 Tulis test untuk membuktikan pencarian nama guru di `ScheduleForm` (Guru Pengajar) gagal pada kode unfixed
  - [x] 1.3 Tulis test untuk membuktikan guru dengan `nip = null` tidak dapat ditemukan pada kode unfixed
  - [x] 1.4 Jalankan exploratory tests dan konfirmasi semua gagal (membuktikan bug ada)

- [x] 2. Fix Implementation — Perbaiki Query Pencarian
  - [x] 2.1 Fix `SchoolClassForm.php`: ubah `modifyQueryUsing` agar melakukan JOIN ke `users` dan mencari di `users.name` dan `teachers.nip`
  - [x] 2.2 Fix `ScheduleForm.php`: terapkan perubahan yang sama seperti `SchoolClassForm.php`
  - [x] 2.3 Jalankan `vendor/bin/pint --dirty --format agent` untuk memformat kode

- [x] 3. Fix Checking — Verifikasi Bug Teratasi
  - [x] 3.1 Jalankan exploratory tests dari Task 1 pada kode yang sudah diperbaiki — semua harus LULUS
  - [x] 3.2 Tulis test tambahan: pencarian nama menemukan guru yang benar di `SchoolClassForm`
  - [x] 3.3 Tulis test tambahan: pencarian nama menemukan guru yang benar di `ScheduleForm`
  - [x] 3.4 Tulis test: guru dengan `nip = null` dapat ditemukan via pencarian nama setelah fix

- [x] 4. Preservation Checking — Verifikasi Tidak Ada Regresi
  - [x] 4.1 Tulis test: pencarian via NIP tetap menghasilkan hasil yang benar setelah fix
  - [x] 4.2 Tulis test: preload dropdown (tanpa search) tetap menampilkan semua guru
  - [x] 4.3 Tulis test: label format `Nama (NIP)` atau `Nama` tetap benar setelah guru dipilih
  - [x] 4.4 Tulis test: `teacher_id` yang tersimpan ke database tetap benar setelah memilih guru
  - [x] 4.5 Tulis test: validasi bentrok jadwal di `ScheduleForm` tetap berfungsi setelah fix

- [x] 5. Run All Tests
  - [x] 5.1 Jalankan seluruh test suite yang relevan dan pastikan semua lulus
