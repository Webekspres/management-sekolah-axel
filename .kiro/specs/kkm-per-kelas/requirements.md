# Requirements Document

## Introduction

Fitur ini menambahkan field **KKM (Kriteria Ketuntasan Minimal)** langsung pada model dan tabel `classes` (kelas), sehingga setiap kelas dapat memiliki nilai KKM yang berbeda-beda. Saat ini KKM dikonfigurasi per mata pelajaran per jenjang (`subject_kkms`), namun tidak ada KKM yang melekat pada kelas itu sendiri.

Dengan adanya KKM per kelas, saat proses generate rapor PDF, nilai KKM yang ditampilkan diambil dari kelas siswa tersebut — bukan lagi hanya dari konfigurasi per jenjang. Ini memungkinkan fleksibilitas lebih besar, misalnya kelas unggulan memiliki KKM lebih tinggi dari kelas reguler meskipun berada di jenjang yang sama.

## Glossary

- **KKM**: Kriteria Ketuntasan Minimal — nilai minimum yang harus dicapai siswa agar dinyatakan tuntas pada suatu mata pelajaran.
- **SchoolClass**: Model kelas (`App\Models\SchoolClass`, tabel `classes`) yang merepresentasikan satu kelas dalam satu tahun akademik.
- **Classes_Table**: Tabel database `classes` yang menyimpan data kelas.
- **Rapor_PDF**: Dokumen PDF rapor yang digenerate oleh `RaporService::generatePdf()` dan ditampilkan di halaman rapor siswa.
- **RaporService**: Service class `App\Services\RaporService` yang menangani logika generate PDF rapor.
- **SubjectKkm**: Model `App\Models\SubjectKkm` yang menyimpan KKM per mata pelajaran per jenjang — tetap dipertahankan sebagai fallback.
- **SchoolClass_Form**: Form Filament untuk membuat dan mengedit data kelas di panel admin.
- **KKM_Kelas**: Nilai KKM yang melekat pada kelas, disimpan di kolom `kkm` pada tabel `classes`.
- **KKM_Fallback**: Nilai KKM yang digunakan ketika `KKM_Kelas` tidak dikonfigurasi — diambil dari `SubjectKkm::getKkm()` dengan fallback akhir 70.0.

---

## Requirements

### Requirement 1: Penambahan Field KKM pada Tabel Kelas

**User Story:** Sebagai administrator, saya ingin setiap kelas memiliki nilai KKM tersendiri, sehingga kelas yang berbeda dapat memiliki standar ketuntasan yang berbeda meskipun berada di jenjang yang sama.

#### Acceptance Criteria

1. THE Classes_Table SHALL memiliki kolom `kkm` bertipe `decimal(5,2)` yang bersifat nullable.
2. THE SchoolClass SHALL mengekspos kolom `kkm` sebagai fillable attribute dengan cast ke `decimal:2`.
3. WHEN kolom `kkm` pada suatu kelas bernilai `null`, THE SchoolClass SHALL dianggap tidak memiliki KKM yang dikonfigurasi dan sistem SHALL menggunakan KKM_Fallback.
4. THE Classes_Table SHALL mempertahankan semua kolom yang sudah ada (`id`, `name`, `level_id`, `teacher_id`, `academic_year_id`) tanpa perubahan.

---

### Requirement 2: Input KKM pada Form Kelas di Panel Admin

**User Story:** Sebagai administrator, saya ingin dapat mengisi nilai KKM saat membuat atau mengedit data kelas, sehingga saya dapat mengatur standar ketuntasan yang sesuai untuk setiap kelas.

#### Acceptance Criteria

1. WHEN administrator membuka form buat atau edit kelas, THE SchoolClass_Form SHALL menampilkan field input KKM dengan label "KKM" di dalam section "Informasi Kelas".
2. WHEN administrator mengisi field KKM, THE SchoolClass_Form SHALL menerima nilai numerik desimal antara 0 hingga 100 (inklusif).
3. WHEN administrator mengosongkan field KKM, THE SchoolClass_Form SHALL menyimpan nilai `null` pada kolom `kkm` — field ini bersifat opsional.
4. WHEN administrator mengisi nilai KKM di luar rentang 0–100, THE SchoolClass_Form SHALL menampilkan pesan validasi error dan menolak penyimpanan.
5. WHEN administrator menyimpan form kelas dengan KKM yang valid, THE SchoolClass SHALL menyimpan nilai KKM tersebut ke database dengan presisi dua angka desimal.

---

### Requirement 3: Penggunaan KKM Kelas pada Generate Rapor PDF

**User Story:** Sebagai guru, saya ingin nilai KKM yang tampil di rapor siswa diambil dari kelas siswa tersebut, sehingga rapor mencerminkan standar ketuntasan yang berlaku di kelas tersebut.

#### Acceptance Criteria

1. WHEN `RaporService::generatePdf()` dipanggil untuk seorang siswa, THE RaporService SHALL mengambil nilai `kkm` dari kelas siswa tersebut (`student->schoolClass->kkm`).
2. WHEN kelas siswa memiliki nilai `kkm` yang tidak null, THE Rapor_PDF SHALL menampilkan nilai KKM tersebut untuk setiap baris mata pelajaran di bagian "Nilai Pengetahuan & Keterampilan".
3. WHEN kelas siswa tidak memiliki nilai `kkm` (null), THE RaporService SHALL menggunakan KKM_Fallback dari `SubjectKkm::getKkm($subjectId, $levelId)` dengan fallback akhir 70.0.
4. WHEN nilai pengetahuan atau keterampilan siswa lebih rendah dari KKM yang berlaku, THE Rapor_PDF SHALL menampilkan nilai tersebut dengan penanda visual "below-kkm" (teks merah tebal).
5. THE RaporService SHALL menentukan KKM yang berlaku untuk setiap baris mata pelajaran dengan urutan prioritas: (1) `KKM_Kelas` jika tidak null, (2) `SubjectKkm::getKkm()` jika ada, (3) 70.0 sebagai fallback akhir.

---

### Requirement 4: Konsistensi Data dan Backward Compatibility

**User Story:** Sebagai pengembang, saya ingin penambahan field KKM tidak merusak data dan fungsionalitas yang sudah ada, sehingga sistem tetap berjalan normal untuk kelas-kelas yang belum dikonfigurasi KKM-nya.

#### Acceptance Criteria

1. WHEN migrasi database dijalankan pada data yang sudah ada, THE Classes_Table SHALL menambahkan kolom `kkm` dengan nilai default `null` untuk semua baris yang sudah ada — tanpa mengubah data lainnya.
2. WHEN sistem mengakses kelas yang belum memiliki nilai KKM (kolom `kkm` null), THE RaporService SHALL tetap berfungsi normal menggunakan KKM_Fallback.
3. THE SubjectKkm model dan tabel `subject_kkms` SHALL tetap dipertahankan dan tidak dimodifikasi — digunakan sebagai sumber KKM_Fallback.
4. WHEN `GradeStatsWidget` pada halaman Nilai Saya siswa menghitung jumlah mata pelajaran di bawah KKM, THE GradeStatsWidget SHALL menggunakan KKM_Kelas jika tersedia, dengan fallback ke `SubjectKkm::getKkm()`.
