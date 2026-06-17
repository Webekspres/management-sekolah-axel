# Requirements Document

## Introduction

Fitur ini meng-upgrade tampilan PDF eRapor dari versi 1 (v1) ke versi 2 (v2). Scope perubahan **hanya** pada file Blade PDF dan CSS/styling-nya. Kode backend, query data, dan logika perhitungan nilai tidak boleh diubah. Data yang sudah ada di v1 tetap dipakai, hanya presentasinya yang diganti total.

Rapor terdiri dari 3 halaman:

- **Halaman 1**: Data Absensi per Mata Pelajaran + Daftar Nilai
- **Halaman 2**: Nilai Sikap + Nilai Pengetahuan & Keterampilan + Rekap Absensi + Kepribadian
- **Halaman 3**: Capaian Pembelajaran

---

## Glossary

- **PDF_Renderer**: Library DomPDF (`barryvdh/laravel-dompdf`) yang digunakan untuk mengkonversi Blade template ke file PDF.
- **RaporService**: Service class `App\Services\RaporService` yang mengorkestrasi pengambilan data dan pemanggilan `Pdf::loadView()`.
- **Blade_Template**: File `.blade.php` di `resources/views/rapor/` yang menjadi template HTML untuk PDF.
- **Partial**: File Blade yang di-include ke dalam template utama menggunakan `@include`.
- **Titimangsa**: Tanggal penandatanganan rapor, dibaca dari `Setting::where('key', 'titimangsa')->value('value')`, diformat dengan Carbon locale `id` (contoh: "Jakarta, 23 Desember 2025").
- **Wali_Kelas**: Guru homeroom yang terhubung ke `SchoolClass` melalui relasi `homeroomTeacher`.
- **Ketua_Litbang**: Jabatan penandatangan resmi di footer halaman 1 dan 3 ("Mengetahui Ketua Litbang HS-TKB").
- **Predikat**: Huruf A/B/C/D yang merepresentasikan rentang nilai (A: 86–100, B: 73–85, C: 60–72, D: <60).
- **KKM**: Kriteria Ketuntasan Minimal, nilai ambang batas kelulusan per mata pelajaran.
- **AttitudeScore**: Model `App\Models\AttitudeScore` dengan field `aspect`, `score`, `description`.
- **KnowledgeSkillScore**: Model `App\Models\KnowledgeSkillScore` dengan field `knowledge_score`, `knowledge_predicate`, `knowledge_description`, `skill_score`, `skill_predicate`, `skill_description`.
- **PersonalityScore**: Model `App\Models\PersonalityScore` dengan field `kedisiplinan`, `kerapihan`, `kerajinan`, `kesopanan` (nilai A/B/C/D).
- **LearningAchievement**: Model `App\Models\LearningAchievement` dengan field `topic_coverage`, `notes`.
- **Grade_Types**: Tipe nilai yang valid: `PH1`, `PH2`, `PH3`, `PH4` (Penilaian Harian), `TUGAS1`, `TUGAS2`, `TUGAS3`, `TUGAS4` (Tugas/PR), `ATS`, `SAS`, `RAPOR`.
- **Semester_Months**: Array bulan yang relevan untuk semester aktif, disediakan oleh `AttendanceSummaryService::getSemesterMonths()`.
- **v1_Template**: File `resources/views/rapor/pdf.blade.php` yang merupakan template lama (tidak dihapus, hanya di-backup).
- **v2_Template**: File `resources/views/rapor/pdf_v2.blade.php` yang merupakan entry point template baru.

---

## Requirements

### Requirement 1: Struktur File dan Backup Template Lama

**User Story:** Sebagai developer, saya ingin template v2 dibuat sebagai file terpisah tanpa menghapus v1, sehingga rollback bisa dilakukan kapan saja.

#### Acceptance Criteria

1. THE Blade_Template SHALL dibuat di path `resources/views/rapor/pdf_v2.blade.php` sebagai entry point utama.
2. THE Blade_Template SHALL meng-include partial dari direktori `resources/views/rapor/partials/` menggunakan direktif `@include`.
3. THE Blade_Template SHALL memiliki partial: `_header.blade.php`, `_footer_resmi.blade.php`, `_footer_ortu.blade.php`, `halaman1.blade.php`, `halaman2.blade.php`, `halaman3.blade.php`.
4. THE v1_Template di `resources/views/rapor/pdf.blade.php` SHALL tetap ada dan tidak dimodifikasi (hanya di-backup dengan rename ke `pdf_v1_backup.blade.php`).
5. WHEN `RaporService::generatePdf()` dipanggil, THE RaporService SHALL memanggil `Pdf::loadView('rapor.pdf_v2', ...)` untuk menggunakan template v2.

---

### Requirement 2: CSS dan Font Embedding

**User Story:** Sebagai pengguna, saya ingin PDF menggunakan font yang konsisten dan tampilan tabel yang rapi, sehingga rapor terlihat profesional.

#### Acceptance Criteria

1. THE Blade_Template SHALL meng-embed semua CSS di dalam tag `<style>` di dalam file Blade, bukan sebagai file eksternal terpisah.
2. THE Blade_Template SHALL mendefinisikan `@font-face` untuk font Courgette (digunakan pada judul header), Calibri regular dan bold (digunakan pada body), dan Times New Roman (digunakan pada deskripsi).
3. THE Blade_Template SHALL menggunakan path font relatif ke `public/fonts/` yang sudah tersedia di server.
4. THE Blade_Template SHALL menetapkan `body { font-family: Calibri, sans-serif; font-size: 9pt; }`.
5. THE Blade_Template SHALL menetapkan `.page { width: 210mm; padding: 10mm 12mm 8mm 12mm; page-break-after: always; }`.
6. THE Blade_Template SHALL menetapkan `.page:last-child { page-break-after: auto; }` agar halaman terakhir tidak menambah halaman kosong.
7. THE Blade_Template SHALL menetapkan semua tabel dengan `border-collapse: collapse; table-layout: fixed; width: 100%;`.
8. THE Blade_Template SHALL menetapkan semua `td` dan `th` dengan `border: 1px solid #000; padding: 2px 4px; word-wrap: break-word; overflow: hidden;`.
9. THE Blade_Template SHALL TIDAK menggunakan `flexbox` atau `CSS grid` karena tidak didukung oleh PDF_Renderer.
10. THE Blade_Template SHALL menggunakan `display: table` dan `display: table-cell` untuk layout multi-kolom.

---

### Requirement 3: Header Sekolah (Dipakai di 3 Halaman)

**User Story:** Sebagai pengguna, saya ingin setiap halaman rapor memiliki header sekolah yang konsisten dan informatif, sehingga identitas lembaga terlihat jelas.

#### Acceptance Criteria

1. THE Partial `_header.blade.php` SHALL menampilkan header dalam 3 baris: baris 1 judul institusi, baris 2 nama sekolah, baris 3 NPSN di kiri dan teks "TERAKREDITASI : A" di kanan.
2. THE Partial SHALL menggunakan font Courgette untuk teks judul di baris 1.
3. THE Partial SHALL menggunakan `display: table; width: 100%` dengan dua `display: table-cell` untuk menempatkan NPSN di kiri dan akreditasi di kanan pada baris 3.
4. THE Partial SHALL dipisahkan dari konten halaman dengan garis bawah `border-bottom: 2px solid #000`.
5. WHEN `_header.blade.php` di-include, THE Partial SHALL menerima variabel `$schoolClass` dan `$academicYear` yang sudah tersedia di scope template utama.

---

### Requirement 4: Identitas Siswa 2 Kolom (Halaman 1)

**User Story:** Sebagai pengguna, saya ingin identitas siswa ditampilkan dalam 2 kolom yang seimbang, sehingga informasi lebih mudah dibaca dan tidak memakan banyak ruang vertikal.

#### Acceptance Criteria

1. THE `halaman1.blade.php` SHALL menampilkan identitas siswa dalam layout 2 kolom menggunakan `display: table`.
2. THE Kolom kiri SHALL menampilkan: Nama Siswa, NIS/NISN, dan Program.
3. THE Kolom kanan SHALL menampilkan: Kelas, Semester, dan Tahun Pembelajaran.
4. THE Identitas SHALL menggunakan data dari variabel `$student`, `$schoolClass`, dan `$academicYear` yang sudah tersedia.
5. IF `$student->user?->name` bernilai null, THEN THE Blade_Template SHALL menampilkan tanda "—" sebagai fallback.

---

### Requirement 5: Tabel Absensi dengan Header Multi-Level (Halaman 1)

**User Story:** Sebagai pengguna, saya ingin tabel absensi memiliki header yang jelas menunjukkan bulan dan total sesi, sehingga data kehadiran mudah dipahami.

#### Acceptance Criteria

1. THE `halaman1.blade.php` SHALL menampilkan tabel absensi dengan header 2 level (multi-row header).
2. THE Header baris pertama SHALL memiliki kolom "Mata Pelajaran" dengan `rowspan="2"`, kolom "Jumlah Sesi Pada Bulan" dengan `colspan` sejumlah bulan dalam semester, dan kolom "Total Sesi" dengan `rowspan="2"`.
3. THE Header baris kedua SHALL menampilkan nama singkat setiap bulan dari `$semesterMonths` menggunakan `$monthNames`.
4. THE Tabel SHALL menampilkan data dari `$attendanceBySubject` dengan nilai per bulan dari `$subjectData['months'][$month]['total']`.
5. IF tidak ada data absensi, THEN THE Blade_Template SHALL menampilkan baris dengan pesan "Tidak ada data absensi" yang di-colspan seluruh kolom.

---

### Requirement 6: Tabel Daftar Nilai dengan Header Multi-Level (Halaman 1)

**User Story:** Sebagai pengguna, saya ingin tabel daftar nilai memiliki header yang mengelompokkan kolom PH dan Tugas/PR, sehingga struktur penilaian lebih mudah dipahami.

#### Acceptance Criteria

1. THE `halaman1.blade.php` SHALL menampilkan tabel daftar nilai dengan header 2 level.
2. THE Header baris pertama SHALL memiliki: "No" `rowspan="2"`, "Mata Pelajaran" `rowspan="2"`, "Penilaian Harian" `colspan="4"`, "Tugas/PR" `colspan="4"`, "ATS" `rowspan="2"`, "SAS" `rowspan="2"`, "Nilai Rapor" `rowspan="2"`, "Guru Bidang Studi" `rowspan="2"`.
3. THE Header baris kedua SHALL menampilkan: PH1, PH2, PH3, PH4, T1, T2, T3, T4.
4. THE Tabel SHALL menampilkan data dari `$gradesBySubject` dengan nilai dari `$subjectData['grades'][$type]`.
5. THE Kolom "Nilai Rapor" SHALL ditampilkan dengan `font-weight: bold`.
6. IF nilai tidak tersedia untuk suatu tipe, THEN THE Blade_Template SHALL menampilkan "—".

---

### Requirement 7: Footer Resmi (Halaman 1 dan 3)

**User Story:** Sebagai pengguna, saya ingin halaman 1 dan 3 memiliki footer tanda tangan resmi yang lengkap, sehingga rapor memiliki legalitas yang jelas.

#### Acceptance Criteria

1. THE Partial `_footer_resmi.blade.php` SHALL menampilkan titimangsa di tengah (center).
2. THE Titimangsa SHALL dibaca dari `Setting::where('key', 'titimangsa')->value('value')` dan diformat menggunakan Carbon dengan locale `id`.
3. THE Partial SHALL menampilkan "Mengetahui Ketua Litbang HS-TKB" di kolom kiri dan "Dibuat oleh Wali Kelas SMP" di kolom kanan menggunakan `display: table`.
4. THE Setiap kolom tanda tangan SHALL memiliki garis tanda tangan (`.ttd-line`) dengan `margin-top: 40px; border-bottom: 1px solid #000`.
5. THE Di bawah garis tanda tangan SHALL ditampilkan angka "0" sebagai placeholder nomor urut.
6. THE Partial SHALL menggunakan variabel `$waliKelasName` yang sudah tersedia di scope template utama.
7. IF `$waliKelasName` bernilai null, THEN THE Partial SHALL menampilkan "( __________________ )" sebagai fallback.

---

### Requirement 8: Sub-Judul Center (Halaman 2)

**User Story:** Sebagai pengguna, saya ingin halaman 2 memiliki sub-judul yang jelas setelah header, sehingga pembaca langsung mengetahui isi halaman.

#### Acceptance Criteria

1. THE `halaman2.blade.php` SHALL menampilkan teks "LAPORAN HASIL BELAJAR SISWA" dengan `font-weight: bold; text-align: center` setelah `@include('rapor.partials._header')`.
2. THE Sub-judul SHALL ditampilkan sebelum blok identitas siswa halaman 2.

---

### Requirement 9: Identitas Siswa 1 Kolom Kiri (Halaman 2)

**User Story:** Sebagai pengguna, saya ingin identitas siswa di halaman 2 ditampilkan dalam 1 kolom kiri yang lebih lengkap, sehingga konsisten dengan format rapor formal.

#### Acceptance Criteria

1. THE `halaman2.blade.php` SHALL menampilkan identitas siswa dalam 1 kolom kiri (bukan 2 kolom seperti halaman 1).
2. THE Identitas SHALL menampilkan: Nama Siswa, NIS/NISN, Kelas, Semester, Tahun Pelajaran, dan Program.
3. THE Setiap baris identitas SHALL menggunakan format "Label : Nilai" dengan lebar label yang konsisten.

---

### Requirement 10: Tabel Nilai Sikap dengan Baris Rata-Rata (Halaman 2)

**User Story:** Sebagai pengguna, saya ingin tabel nilai sikap menampilkan baris rata-rata di bagian bawah dengan predikat, sehingga ringkasan penilaian sikap langsung terlihat.

#### Acceptance Criteria

1. THE `halaman2.blade.php` SHALL menampilkan tabel nilai sikap dengan kolom: Aspek, Nilai, dan Deskripsi.
2. WHEN `$attitudeScores` tidak kosong, THE Blade_Template SHALL menampilkan baris terakhir "Rata-Rata Nilai Sikap" dengan nilai rata-rata dan predikat di kolom Deskripsi.
3. THE Predikat rata-rata SHALL dihitung menggunakan logika: A (≥86), B (≥73), C (≥60), D (<60).
4. THE Label "Rata-Rata Nilai Sikap" SHALL ditampilkan dengan `font-weight: bold`.
5. IF `$attitudeScores` kosong, THEN THE Blade_Template SHALL menampilkan baris "Belum ada data" yang di-colspan seluruh kolom.

---

### Requirement 11: Tabel Pengetahuan & Keterampilan Header Multi-Level (Halaman 2)

**User Story:** Sebagai pengguna, saya ingin tabel pengetahuan dan keterampilan memiliki header yang mengelompokkan kolom per domain, sehingga struktur penilaian lebih mudah dipahami.

#### Acceptance Criteria

1. THE `halaman2.blade.php` SHALL menampilkan tabel dengan header 2 level.
2. THE Header baris pertama SHALL memiliki: "Mata Pelajaran" `rowspan="2"`, "KKM" `rowspan="2"`, "Pengetahuan" `colspan="3"`, "Keterampilan" `colspan="3"`.
3. THE Header baris kedua SHALL menampilkan: Nilai, Predikat, Deskripsi (untuk Pengetahuan), lalu Nilai, Predikat, Deskripsi (untuk Keterampilan).
4. THE Nilai yang berada di bawah KKM SHALL ditampilkan dengan `color: #cc0000; font-weight: bold`.
5. THE Kolom deskripsi SHALL menggunakan `word-wrap: break-word` agar teks panjang tidak terpotong.

---

### Requirement 12: Rekap Absensi Format Vertikal 2 Kolom (Halaman 2)

**User Story:** Sebagai pengguna, saya ingin rekap absensi ditampilkan dalam format vertikal yang lebih mudah dibaca, bukan tabel horizontal.

#### Acceptance Criteria

1. THE `halaman2.blade.php` SHALL menampilkan rekap absensi dalam format tabel 2 kolom vertikal: kolom kiri label, kolom kanan nilai.
2. THE Tabel SHALL menampilkan baris: Sakit, Izin, Alpa, dan Total.
3. THE Format setiap baris SHALL mengikuti pola "Label : Nilai", dengan nilai "-" jika tidak ada data.
4. THE Data SHALL diambil dari `$overallAttendance['sakit']`, `$overallAttendance['izin']`, `$overallAttendance['alpa']`, `$overallAttendance['total']`.

---

### Requirement 13: Kepribadian dengan Semua Opsi A/B/C/D (Halaman 2)

**User Story:** Sebagai pengguna, saya ingin tabel kepribadian menampilkan semua opsi nilai A/B/C/D untuk setiap aspek, dengan nilai tidak aktif dicoret, sehingga pilihan yang dipilih terlihat jelas.

#### Acceptance Criteria

1. THE `halaman2.blade.php` SHALL menampilkan tabel kepribadian dengan kolom: Kedisiplinan, Kerapihan, Kerajinan, Kesopanan.
2. THE Setiap sel SHALL menampilkan semua opsi: A / B / C / D.
3. THE Nilai yang aktif (sesuai dengan nilai di `$personalityScore`) SHALL ditampilkan dengan `font-weight: bold` menggunakan class `.nilai-aktif`.
4. THE Nilai yang tidak aktif SHALL ditampilkan dengan `text-decoration: line-through` menggunakan class `.nilai-coret`.
5. IF `$personalityScore` bernilai null, THEN THE Blade_Template SHALL menampilkan pesan "Belum ada data kepribadian."

---

### Requirement 14: Footer Orang Tua (Halaman 2)

**User Story:** Sebagai pengguna, saya ingin halaman 2 memiliki footer tanda tangan untuk orang tua dan wali kelas, sehingga rapor dapat dikonfirmasi oleh orang tua.

#### Acceptance Criteria

1. THE Partial `_footer_ortu.blade.php` SHALL menampilkan titimangsa di tengah (center).
2. THE Partial SHALL menampilkan "Mengetahui Orang Tua/Wali" di kolom kiri dan "Wali Kelas" di kolom kanan menggunakan `display: table`.
3. THE Setiap kolom tanda tangan SHALL memiliki garis tanda tangan dengan `margin-top: 40px; border-bottom: 1px solid #000`.
4. THE Partial SHALL menggunakan variabel `$waliKelasName` yang sudah tersedia di scope template utama.
5. THE Titimangsa SHALL menggunakan sumber data yang sama dengan `_footer_resmi.blade.php`.

---

### Requirement 15: Tabel Capaian Pembelajaran Header Multi-Level (Halaman 3)

**User Story:** Sebagai pengguna, saya ingin tabel capaian pembelajaran memiliki header yang mengelompokkan kolom hasil pembelajaran, sehingga struktur lebih mudah dipahami.

#### Acceptance Criteria

1. THE `halaman3.blade.php` SHALL menampilkan tabel capaian pembelajaran dengan header 2 level.
2. THE Header baris pertama SHALL memiliki: "Mata Pelajaran" `rowspan="2"`, "Pemaparan Materi" `rowspan="2"`, "Hasil Pembelajaran" `colspan="4"`.
3. THE Header baris kedua SHALL menampilkan: PH (Rata-rata), ATS, SAS, Keterangan.
4. THE Data SHALL diambil dari `$learningAchievements` dan `$gradesBySubject`.
5. IF tidak ada data capaian pembelajaran, THEN THE Blade_Template SHALL menampilkan baris "Belum ada data capaian pembelajaran" yang di-colspan seluruh kolom.

---

### Requirement 16: Tabel Keterangan Predikat Terformat (Halaman 3)

**User Story:** Sebagai pengguna, saya ingin keterangan predikat ditampilkan sebagai tabel yang rapi, bukan teks inline, sehingga lebih mudah dibaca.

#### Acceptance Criteria

1. THE `halaman3.blade.php` SHALL menampilkan keterangan predikat dalam tabel dengan kolom: Nilai (rentang angka), Huruf (A/B/C/D), dan Keterangan.
2. THE Tabel SHALL memiliki 4 baris data: A (86–100, Sangat Baik), B (73–85, Baik), C (60–72, Cukup), D (<60, Kurang).
3. THE Tabel keterangan SHALL ditempatkan setelah tabel capaian pembelajaran.

---

### Requirement 17: Page Break yang Benar

**User Story:** Sebagai pengguna, saya ingin setiap halaman rapor dicetak pada halaman PDF yang terpisah, sehingga tidak ada konten yang tumpang tindih antar halaman.

#### Acceptance Criteria

1. THE Setiap elemen `.page` SHALL memiliki `page-break-after: always` kecuali elemen `.page` terakhir.
2. THE Elemen `.page` terakhir SHALL memiliki `page-break-after: auto` untuk mencegah halaman kosong di akhir PDF.
3. THE PDF_Renderer SHALL menghasilkan PDF dengan tepat 3 halaman untuk rapor yang memiliki data lengkap.

---

### Requirement 18: Word-Wrap pada Semua Tabel

**User Story:** Sebagai pengguna, saya ingin teks panjang di kolom deskripsi tidak terpotong, sehingga semua informasi dapat terbaca dengan lengkap.

#### Acceptance Criteria

1. THE Semua tabel SHALL menggunakan `table-layout: fixed` agar lebar kolom dapat dikontrol.
2. THE Semua `td` dan `th` SHALL memiliki `word-wrap: break-word; overflow: hidden` untuk mencegah teks meluap keluar sel.
3. THE Kolom deskripsi pada tabel KnowledgeSkillScore SHALL memiliki lebar yang lebih besar dibanding kolom nilai dan predikat.

---

### Requirement 19: Integrasi dengan RaporService

**User Story:** Sebagai developer, saya ingin RaporService menggunakan template v2 secara otomatis, sehingga semua PDF yang digenerate menggunakan tampilan baru.

#### Acceptance Criteria

1. WHEN `RaporService::generatePdf()` dipanggil, THE RaporService SHALL memanggil `Pdf::loadView('rapor.pdf_v2', ...)`.
2. THE RaporService SHALL meneruskan semua variabel yang sama seperti sebelumnya ke template v2: `rapor`, `student`, `academicYear`, `schoolClass`, `grades`, `gradesBySubject`, `attitudeScores`, `knowledgeSkillScores`, `learningAchievements`, `personalityScore`, `attendanceBySubject`, `overallAttendance`, `semesterMonths`, `monthNames`, `waliKelasName`.
3. THE RaporService SHALL TIDAK mengubah logika query, kalkulasi nilai, atau alur workflow (finalize, approve, reject).
4. IF template v2 tidak ditemukan, THEN THE PDF_Renderer SHALL melempar exception yang ditangkap oleh blok `catch (\Throwable $e)` yang sudah ada di `generatePdf()`.

---

### Requirement 20: Titimangsa dari Setting

**User Story:** Sebagai administrator, saya ingin tanggal titimangsa pada footer rapor dibaca dari pengaturan sistem, sehingga tidak perlu mengubah kode setiap kali tanggal berubah.

#### Acceptance Criteria

1. THE Blade_Template SHALL membaca titimangsa menggunakan `Setting::where('key', 'titimangsa')->value('value')`.
2. WHEN nilai titimangsa tersedia, THE Blade_Template SHALL memformatnya menggunakan Carbon dengan locale `id` (contoh output: "Jakarta, 23 Desember 2025").
3. IF nilai titimangsa tidak tersedia di database, THEN THE Blade_Template SHALL menampilkan string kosong atau tanda "—" sebagai fallback.
4. THE Titimangsa SHALL ditampilkan di `_footer_resmi.blade.php` dan `_footer_ortu.blade.php` dengan posisi `text-align: center`.
