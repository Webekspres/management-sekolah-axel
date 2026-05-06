# Requirements Document

## Introduction

Fitur ini bertujuan mendesain ulang tampilan halaman **"Nilai Saya"** dan **"Rapor Saya"** pada panel siswa (`siswa_ortu`) menggunakan komponen Filament v5 yang tersedia (InfoList, Stats, Tables, Section, dll). Tampilan saat ini menggunakan tabel HTML manual yang kurang menarik, tidak responsif, dan sulit dibaca — terutama pada halaman Nilai Saya yang memiliki 11 kolom nilai dalam satu baris. Redesain ini tidak boleh mengurangi informasi yang sudah ada, melainkan menyajikannya dengan lebih terstruktur, lebih mudah dibaca, dan lebih konsisten dengan desain sistem Filament yang digunakan di seluruh aplikasi.

## Glossary

- **Nilai_Saya_Page**: Halaman `MyGradesPage` di Student Panel yang menampilkan nilai akademik siswa untuk tahun akademik aktif.
- **Rapor_Saya_Page**: Halaman `MyRaporPage` di Student Panel yang menampilkan daftar rapor siswa beserta status dan tombol unduh.
- **Grade_Type**: Jenis nilai akademik — PH1, PH2, PH3, PH4 (Penilaian Harian), TUGAS1, TUGAS2, TUGAS3, TUGAS4 (Tugas), ATS (Asesmen Tengah Semester), SAS (Sumatif Akhir Semester), RAPOR (nilai akhir terhitung).
- **Rapor**: Dokumen laporan hasil belajar siswa per tahun akademik dengan status DRAFT, FINALIZED, atau APPROVED.
- **Academic_Year**: Tahun akademik yang memiliki atribut `name`, `semester`, dan `is_active`.
- **Student_Panel**: Panel Filament untuk role `siswa_ortu` yang dapat diakses di `/student`.
- **InfoList**: Komponen Filament untuk menampilkan data read-only secara terstruktur menggunakan `Infolist::make()`.
- **StatsOverview**: Komponen Filament untuk menampilkan ringkasan statistik menggunakan `Stat::make()`.
- **Filament_Table**: Komponen Filament untuk menampilkan data tabular menggunakan `Table::make()` dengan kolom, filter, dan aksi.

---

## Requirements

### Requirement 1: Halaman Nilai Saya — Ringkasan Statistik Nilai

**User Story:** Sebagai siswa, saya ingin melihat ringkasan nilai saya secara sekilas di bagian atas halaman, sehingga saya dapat langsung mengetahui gambaran umum performa akademik saya tanpa harus membaca tabel secara detail.

#### Acceptance Criteria

1. WHEN siswa membuka halaman Nilai Saya dengan data nilai tersedia, THE Nilai_Saya_Page SHALL menampilkan ringkasan statistik yang mencakup: jumlah mata pelajaran, rata-rata nilai RAPOR keseluruhan, dan jumlah mata pelajaran dengan nilai RAPOR di bawah KKM (jika data KKM tersedia) atau jumlah mata pelajaran yang sudah memiliki nilai RAPOR.
2. WHEN siswa membuka halaman Nilai Saya, THE Nilai_Saya_Page SHALL menampilkan nama dan semester tahun akademik aktif secara jelas di bagian atas halaman.
3. IF siswa tidak memiliki profil siswa yang terhubung, THEN THE Nilai_Saya_Page SHALL menampilkan pesan informatif menggunakan komponen Filament yang menjelaskan bahwa profil siswa tidak ditemukan dan mengarahkan siswa untuk menghubungi administrator.
4. IF tidak ada data nilai untuk tahun akademik aktif, THEN THE Nilai_Saya_Page SHALL menampilkan pesan kosong yang informatif menggunakan komponen Filament dengan ikon yang sesuai.

---

### Requirement 2: Halaman Nilai Saya — Tampilan Nilai Per Mata Pelajaran

**User Story:** Sebagai siswa, saya ingin melihat nilai saya per mata pelajaran dengan pengelompokan yang jelas antara jenis-jenis nilai, sehingga saya dapat memahami komponen penilaian dengan mudah tanpa kebingungan akibat terlalu banyak kolom dalam satu baris.

#### Acceptance Criteria

1. WHEN siswa membuka halaman Nilai Saya dengan data nilai tersedia, THE Nilai_Saya_Page SHALL menampilkan nilai setiap mata pelajaran menggunakan komponen Filament (InfoList atau Table) dengan pengelompokan visual yang membedakan: nilai Penilaian Harian (PH1–PH4), nilai Tugas (TUGAS1–TUGAS4), nilai ATS, nilai SAS, dan nilai RAPOR.
2. WHEN menampilkan nilai per mata pelajaran, THE Nilai_Saya_Page SHALL menampilkan nilai RAPOR dengan penekanan visual yang lebih menonjol dibandingkan komponen nilai lainnya (misalnya warna berbeda, ukuran font lebih besar, atau badge).
3. WHEN nilai untuk suatu Grade_Type belum tersedia, THE Nilai_Saya_Page SHALL menampilkan tanda "—" atau label "Belum ada" pada posisi nilai tersebut.
4. THE Nilai_Saya_Page SHALL menampilkan semua 11 Grade_Type (PH1, PH2, PH3, PH4, TUGAS1, TUGAS2, TUGAS3, TUGAS4, ATS, SAS, RAPOR) untuk setiap mata pelajaran yang memiliki data nilai.
5. WHEN menampilkan nilai numerik, THE Nilai_Saya_Page SHALL memformat nilai dengan dua angka desimal (contoh: 85.00).

---

### Requirement 3: Halaman Nilai Saya — Responsivitas dan Keterbacaan

**User Story:** Sebagai siswa yang mengakses dari perangkat mobile, saya ingin tampilan nilai saya tetap dapat dibaca dengan baik, sehingga saya tidak perlu scroll horizontal yang berlebihan untuk melihat semua nilai.

#### Acceptance Criteria

1. THE Nilai_Saya_Page SHALL menggunakan komponen layout Filament (Section, Grid) untuk mengatur tampilan nilai agar responsif di berbagai ukuran layar.
2. WHEN halaman diakses dari layar kecil (mobile), THE Nilai_Saya_Page SHALL menyesuaikan layout sehingga informasi nilai tetap terbaca tanpa memerlukan scroll horizontal yang berlebihan.
3. THE Nilai_Saya_Page SHALL menggunakan komponen Filament yang konsisten dengan desain sistem yang digunakan di seluruh Student Panel (warna, tipografi, spacing).

---

### Requirement 4: Halaman Rapor Saya — Tampilan Daftar Rapor

**User Story:** Sebagai siswa, saya ingin melihat daftar rapor saya dengan tampilan yang lebih menarik dan informatif, sehingga saya dapat dengan mudah mengetahui status setiap rapor dan mengunduhnya jika sudah tersedia.

#### Acceptance Criteria

1. WHEN siswa membuka halaman Rapor Saya dengan data rapor tersedia, THE Rapor_Saya_Page SHALL menampilkan daftar rapor menggunakan komponen Filament Table dengan kolom: Tahun Akademik, Semester, Status, Tanggal Disetujui (jika APPROVED), dan kolom Aksi.
2. WHEN menampilkan status rapor, THE Rapor_Saya_Page SHALL menggunakan komponen badge/icon Filament dengan warna yang berbeda untuk setiap status: hijau untuk APPROVED, kuning untuk FINALIZED, dan abu-abu untuk DRAFT.
3. WHEN rapor berstatus APPROVED dan memiliki file, THE Rapor_Saya_Page SHALL menampilkan tombol unduh menggunakan Filament Action yang dapat diklik untuk mengunduh file rapor.
4. WHEN rapor tidak berstatus APPROVED atau tidak memiliki file, THE Rapor_Saya_Page SHALL menampilkan indikator visual yang jelas bahwa rapor belum dapat diunduh.
5. IF siswa tidak memiliki profil siswa yang terhubung, THEN THE Rapor_Saya_Page SHALL menampilkan pesan informatif menggunakan komponen Filament yang menjelaskan bahwa profil siswa tidak ditemukan.
6. IF tidak ada data rapor untuk siswa, THEN THE Rapor_Saya_Page SHALL menampilkan pesan kosong yang informatif menggunakan komponen Filament dengan ikon yang sesuai.

---

### Requirement 5: Halaman Rapor Saya — Ringkasan Statistik Rapor

**User Story:** Sebagai siswa, saya ingin melihat ringkasan status rapor saya secara sekilas, sehingga saya dapat langsung mengetahui berapa rapor yang sudah bisa diunduh tanpa harus membaca seluruh tabel.

#### Acceptance Criteria

1. WHEN siswa membuka halaman Rapor Saya dengan data rapor tersedia, THE Rapor_Saya_Page SHALL menampilkan ringkasan statistik yang mencakup: total rapor, jumlah rapor berstatus APPROVED (siap unduh), dan jumlah rapor berstatus DRAFT atau FINALIZED (belum siap).
2. THE Rapor_Saya_Page SHALL menampilkan ringkasan statistik menggunakan komponen StatsOverview atau InfoList Filament di bagian atas halaman sebelum tabel daftar rapor.

---

### Requirement 6: Konsistensi Komponen Filament

**User Story:** Sebagai pengembang, saya ingin kedua halaman menggunakan komponen Filament secara konsisten, sehingga tampilan selaras dengan panel-panel lain dalam aplikasi dan mudah dipelihara.

#### Acceptance Criteria

1. THE Nilai_Saya_Page SHALL menggunakan minimal satu komponen Filament dari kategori berikut: InfoList, Table, atau StatsOverview — menggantikan tabel HTML manual yang ada saat ini.
2. THE Rapor_Saya_Page SHALL menggunakan komponen Filament Table (bukan tabel HTML manual) untuk menampilkan daftar rapor, dengan kolom menggunakan `TextColumn`, `BadgeColumn`, atau `IconColumn` dari namespace `Filament\Tables\Columns\`.
3. THE Nilai_Saya_Page SHALL menggunakan komponen Filament Section atau Grid dari namespace `Filament\Schemas\Components\` untuk mengatur layout halaman.
4. WHEN menggunakan Filament Action untuk tombol unduh rapor, THE Rapor_Saya_Page SHALL menggunakan komponen dari namespace `Filament\Actions\` — bukan tombol HTML manual.
5. THE Nilai_Saya_Page dan THE Rapor_Saya_Page SHALL mempertahankan semua informasi yang sudah ada pada tampilan sebelumnya — tidak ada data yang dihilangkan dalam proses redesain.
