# Tasks: Admin Menu Cleanup

## Tasks

- [x] 1. Sembunyikan menu "Data Nilai" dari navigasi admin
  - [x] 1.1 Tambah `protected static bool $shouldRegisterNavigation = false;` di `GradeResource`
  - [x] 1.2 Pastikan URL direct ke `/academic/grades` masih bisa diakses (canAccess tetap `super_admin`)

- [x] 2. Sembunyikan menu "Data Rapor" dari navigasi admin
  - [x] 2.1 Tambah `protected static bool $shouldRegisterNavigation = false;` di `RaporResource`
  - [x] 2.2 Pastikan URL direct ke `/academic/rapors` masih bisa diakses (canAccess tetap `super_admin`)

- [x] 3. Sembunyikan menu "KKM Mata Pelajaran" dari navigasi admin
  - [x] 3.1 Tambah `protected static bool $shouldRegisterNavigation = false;` di `SubjectKkmResource`
  - [x] 3.2 Pastikan URL direct ke `/academic/subject-kkms` masih bisa diakses (canAccess tetap `super_admin`)
