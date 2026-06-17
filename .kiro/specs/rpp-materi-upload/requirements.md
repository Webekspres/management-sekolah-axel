# Requirements Document

## Introduction

Fitur ini memperluas modul RPP (Rencana Pelaksanaan Pembelajaran) yang sudah ada dengan kemampuan upload materi pembelajaran. Saat ini, guru hanya dapat melampirkan satu file RPP (format .pdf/.doc/.docx). Dengan fitur ini, guru dapat mengunggah lebih dari satu file materi (format .pdf, .pptx, .xlsx, .docx) yang terlampir pada RPP. Kepala Sekolah dapat melihat materi tersebut saat memeriksa dan menyetujui RPP. Siswa dapat mengunduh materi yang sudah terlampir pada RPP yang telah disetujui.

Fitur ini merupakan refactor dan perluasan dari fitur RPP yang sudah ada, bukan penggantian total. Alur approval RPP (DRAFT → PENDING → REVISED/APPROVED) tetap dipertahankan.

## Glossary

- **RPP**: Rencana Pelaksanaan Pembelajaran — dokumen perencanaan pembelajaran yang dibuat oleh guru. Dokumen RPP bersifat internal dan hanya boleh diakses oleh guru pemilik dan Kepsek.
- **LessonPlan**: Model Eloquent yang merepresentasikan satu RPP dalam sistem.
- **LessonPlanMaterial**: Model Eloquent baru yang merepresentasikan satu file materi pembelajaran yang dilampirkan pada RPP. Materi berbeda dari dokumen RPP itu sendiri — materi dapat diakses oleh siswa setelah RPP disetujui.
- **Guru**: Pengguna dengan role `guru` yang membuat dan mengelola RPP miliknya sendiri.
- **Kepsek**: Kepala Sekolah — pengguna dengan role `kepsek` yang memeriksa dan menyetujui RPP. Kepsek dapat melihat semua RPP dari semua guru.
- **Siswa**: Pengguna dengan role `siswa` yang hanya dapat mengunduh LessonPlanMaterial dari RPP yang sudah berstatus APPROVED. Siswa tidak dapat melihat dokumen RPP itu sendiri.
- **Ortu**: Pengguna dengan role `ortu` (orang tua siswa) yang tidak memiliki akses ke RPP maupun LessonPlanMaterial sama sekali.
- **Upload_System**: Subsistem pengelolaan file yang menangani penyimpanan dan pengambilan file materi.
- **Approval_Flow**: Alur status RPP: DRAFT → PENDING → REVISED atau APPROVED.

---

## Requirements

### Requirement 1: Upload Materi pada RPP

**User Story:** Sebagai guru, saya ingin dapat mengunggah lebih dari satu file materi pada RPP, sehingga materi pembelajaran dapat terdokumentasi bersama RPP.

#### Acceptance Criteria

1. WHEN guru membuat atau mengedit RPP berstatus DRAFT atau REVISED, THE Upload_System SHALL menerima unggahan file materi dengan format .pdf, .pptx, .xlsx, dan .docx.
2. THE Upload_System SHALL mengizinkan pengunggahan lebih dari satu file materi dalam satu RPP.
3. WHEN guru mengunggah file materi, THE Upload_System SHALL menyimpan file ke direktori `lesson-plan-materials` pada disk `public`.
4. WHEN guru mengunggah file materi, THE Upload_System SHALL mempertahankan nama file asli (preserve filenames).
5. IF file yang diunggah bukan berformat .pdf, .pptx, .xlsx, atau .docx, THEN THE Upload_System SHALL menolak file tersebut dan menampilkan pesan kesalahan yang menjelaskan format yang diizinkan.
6. WHILE RPP berstatus PENDING atau APPROVED, THE Upload_System SHALL menonaktifkan kemampuan menambah atau menghapus file materi.
7. THE LessonPlanMaterial SHALL menyimpan atribut: `lesson_plan_id`, `file_path`, dan `original_filename`.

---

### Requirement 2: Tampilan Materi pada Panel Guru

**User Story:** Sebagai guru, saya ingin dapat melihat, mengunduh, dan menghapus file materi yang sudah saya unggah pada RPP, sehingga saya dapat mengelola materi dengan mudah.

#### Acceptance Criteria

1. WHEN guru membuka halaman edit RPP, THE LessonPlanResource SHALL menampilkan daftar file materi yang sudah diunggah beserta nama file dan tombol unduh.
2. WHEN guru mengklik tombol unduh pada file materi, THE Upload_System SHALL menghasilkan URL unduhan yang valid untuk file tersebut.
3. WHILE RPP berstatus DRAFT atau REVISED, THE LessonPlanResource SHALL menampilkan tombol hapus pada setiap file materi yang sudah diunggah.
4. WHILE RPP berstatus PENDING atau APPROVED, THE LessonPlanResource SHALL menyembunyikan tombol hapus pada file materi.

---

### Requirement 3: Tampilan Materi pada Panel Kepsek

**User Story:** Sebagai Kepala Sekolah, saya ingin dapat melihat dan mengunduh file materi yang dilampirkan pada RPP, sehingga saya dapat memeriksa kelengkapan materi sebelum memberikan persetujuan.

#### Acceptance Criteria

1. WHEN Kepsek membuka halaman detail/edit RPP, THE LessonPlanResource SHALL menampilkan daftar file materi yang dilampirkan pada RPP tersebut.
2. WHEN Kepsek mengklik nama file materi, THE Upload_System SHALL menghasilkan URL yang dapat dibuka di tab baru untuk melihat atau mengunduh file.
3. IF RPP tidak memiliki file materi, THEN THE LessonPlanResource SHALL menampilkan teks "Tidak ada materi yang dilampirkan."
4. THE LessonPlanResource SHALL menampilkan daftar materi dalam tampilan read-only — Kepsek tidak dapat menambah atau menghapus file materi.

---

### Requirement 4: Akses Unduh Materi oleh Siswa

**User Story:** Sebagai siswa, saya ingin dapat melihat dan mengunduh materi pembelajaran yang sudah disetujui oleh Kepala Sekolah, sehingga saya dapat mempelajari materi sebelum atau sesudah pelajaran.

#### Acceptance Criteria

1. THE LessonPlanResource SHALL menampilkan daftar LessonPlanMaterial dari RPP yang berstatus APPROVED kepada siswa di panel siswa — siswa tidak dapat melihat dokumen RPP itu sendiri.
2. WHEN siswa membuka halaman materi, THE LessonPlanResource SHALL menampilkan daftar file LessonPlanMaterial yang dilampirkan pada RPP tersebut tanpa menampilkan konten atau metadata dokumen RPP.
3. WHEN siswa mengklik tombol unduh pada file materi, THE Upload_System SHALL menghasilkan URL unduhan yang valid untuk LessonPlanMaterial tersebut.
4. IF RPP berstatus selain APPROVED, THEN THE LessonPlanResource SHALL tidak menampilkan materi RPP tersebut kepada siswa.
5. THE LessonPlanResource SHALL menampilkan LessonPlanMaterial kepada siswa dalam tampilan read-only — siswa tidak dapat mengubah data RPP atau materi.
6. WHEN siswa mengakses panel siswa, THE LessonPlanResource SHALL hanya menampilkan materi dari RPP yang relevan dengan kelas siswa tersebut.

---

### Requirement 5: Integritas Data dan Penghapusan File

**User Story:** Sebagai sistem, saya ingin memastikan file materi yang dihapus dari database juga dihapus dari storage, sehingga tidak ada file orphan yang memenuhi disk.

#### Acceptance Criteria

1. WHEN sebuah LessonPlanMaterial dihapus dari database, THE Upload_System SHALL menghapus file fisik yang bersesuaian dari storage.
2. WHEN sebuah LessonPlan dihapus, THE Upload_System SHALL menghapus semua LessonPlanMaterial beserta file fisiknya yang terkait.
3. IF file fisik tidak ditemukan saat penghapusan, THEN THE Upload_System SHALL tetap menghapus record database tanpa melempar exception yang menghentikan proses.

---

### Requirement 6: Keamanan Akses File

**User Story:** Sebagai sistem, saya ingin memastikan hanya pengguna yang berwenang yang dapat mengakses file materi, sehingga keamanan data terjaga.

#### Acceptance Criteria

1. THE Upload_System SHALL menyimpan file materi dengan visibility `public` pada disk `public`, konsisten dengan pola penyimpanan file RPP yang sudah ada.
2. WHEN URL file materi dihasilkan, THE Upload_System SHALL menggunakan `PublicStorageUrl::fromPublicDiskPath()` konsisten dengan pola yang sudah ada di aplikasi.
3. THE LessonPlanResource SHALL membatasi akses halaman daftar materi siswa hanya untuk pengguna dengan role `siswa` yang memiliki profil siswa aktif.

---

### Requirement 7: Pembatasan Akses Dokumen RPP

**User Story:** Sebagai sistem, saya ingin memastikan dokumen RPP tidak bocor ke pihak yang tidak berwenang, sehingga privasi dan integritas dokumen perencanaan pembelajaran terjaga.

#### Acceptance Criteria

1. THE LessonPlanResource SHALL membatasi akses halaman daftar dan detail RPP hanya untuk pengguna dengan role `guru` (pemilik RPP) atau `kepsek`.
2. WHEN pengguna dengan role `siswa` mencoba mengakses halaman detail atau daftar RPP, THE LessonPlanResource SHALL menolak akses dan mengembalikan respons HTTP 403.
3. WHEN pengguna dengan role `ortu` mencoba mengakses halaman RPP maupun halaman materi RPP, THE LessonPlanResource SHALL menolak akses dan mengembalikan respons HTTP 403.
4. WHILE guru mengakses panel guru, THE LessonPlanResource SHALL hanya menampilkan RPP yang dibuat oleh guru tersebut — guru tidak dapat melihat RPP milik guru lain.
5. THE LessonPlanResource SHALL memastikan endpoint unduhan file RPP (dokumen RPP itu sendiri) hanya dapat diakses oleh guru pemilik RPP atau Kepsek.
6. IF pengguna yang tidak memiliki role `guru` atau `kepsek` mencoba mengakses URL unduhan dokumen RPP secara langsung, THEN THE Upload_System SHALL menolak permintaan tersebut dan mengembalikan respons HTTP 403.
7. THE LessonPlanResource SHALL memisahkan secara eksplisit antara endpoint akses dokumen RPP (hanya guru pemilik dan kepsek) dan endpoint akses LessonPlanMaterial (guru pemilik, kepsek, dan siswa dari kelas yang relevan dengan RPP APPROVED).
