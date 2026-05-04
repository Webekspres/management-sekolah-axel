# Bugfix Requirements Document

## Introduction

Panel siswa (`app/Filament/Student/`) menampilkan daftar pengumuman (Announcement) dengan tiga masalah utama:

1. **Aksi hapus tidak seharusnya ada** — `AnnouncementsTable` pada panel siswa menyertakan `DeleteBulkAction` dan `EditAction`, sehingga siswa dapat menghapus atau mengedit pengumuman. Ini adalah pelanggaran otorisasi karena siswa hanya boleh membaca pengumuman.

2. **Status baca tidak diimplementasikan** — Tidak ada mekanisme untuk melacak apakah siswa sudah membaca suatu pengumuman. Model `Announcement` tidak memiliki relasi ke tabel read-status, dan `ViewAnnouncement` page tidak memicu pencatatan status baca saat pengumuman dibuka.

3. **Tidak ada indikator visual status baca** — Baris pengumuman yang sudah dibaca dan yang belum dibaca tampil identik di tabel, sehingga siswa tidak dapat membedakan mana yang sudah dan belum dibaca.

---

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN siswa mengakses halaman daftar pengumuman di panel siswa THEN sistem menampilkan checkbox seleksi baris dan tombol "Hapus yang dipilih" (DeleteBulkAction)

1.2 WHEN siswa mengakses halaman daftar pengumuman di panel siswa THEN sistem menampilkan tombol aksi "Edit" pada setiap baris pengumuman

1.3 WHEN siswa membuka halaman detail pengumuman (ViewAnnouncement) THEN sistem menampilkan tombol "Edit" di header halaman

1.4 WHEN siswa membuka halaman detail pengumuman THEN sistem tidak mencatat bahwa pengumuman tersebut telah dibaca oleh siswa tersebut

1.5 WHEN siswa melihat daftar pengumuman THEN sistem menampilkan semua baris dengan tampilan visual yang identik, tanpa membedakan pengumuman yang sudah dibaca dan yang belum dibaca

### Expected Behavior (Correct)

2.1 WHEN siswa mengakses halaman daftar pengumuman di panel siswa THEN sistem SHALL tidak menampilkan checkbox seleksi baris maupun tombol "Hapus yang dipilih"

2.2 WHEN siswa mengakses halaman daftar pengumuman di panel siswa THEN sistem SHALL tidak menampilkan tombol aksi "Edit" pada setiap baris pengumuman

2.3 WHEN siswa membuka halaman detail pengumuman (ViewAnnouncement) THEN sistem SHALL tidak menampilkan tombol "Edit" di header halaman

2.4 WHEN siswa membuka halaman detail pengumuman THEN sistem SHALL mencatat status baca (read status) untuk pasangan siswa–pengumuman tersebut, sehingga `isRead()` mengembalikan `true` untuk pengumuman itu

2.5 WHEN siswa melihat daftar pengumuman THEN sistem SHALL menampilkan indikator visual yang jelas untuk membedakan pengumuman yang sudah dibaca (isRead = true) dari yang belum dibaca (isRead = false), misalnya warna baris berbeda, badge "Sudah Dibaca"/"Belum Dibaca", atau ikon indikator

### Unchanged Behavior (Regression Prevention)

3.1 WHEN admin atau guru mengakses resource pengumuman di panel mereka THEN sistem SHALL CONTINUE TO menampilkan semua aksi yang tersedia (create, edit, delete) sesuai peran masing-masing

3.2 WHEN siswa mengakses halaman daftar pengumuman THEN sistem SHALL CONTINUE TO menampilkan hanya pengumuman yang ditujukan untuk peran siswa (filter `target_role`)

3.3 WHEN siswa mengklik baris pengumuman THEN sistem SHALL CONTINUE TO membuka halaman detail (ViewAnnouncement) dengan konten pengumuman yang lengkap

3.4 WHEN siswa yang sama membuka pengumuman yang sudah pernah dibaca sebelumnya THEN sistem SHALL CONTINUE TO menampilkan status baca sebagai sudah dibaca (tidak mereset atau menduplikasi catatan)

3.5 WHEN siswa yang berbeda membuka pengumuman yang sama THEN sistem SHALL CONTINUE TO melacak status baca secara terpisah per siswa (status baca bersifat per-user, bukan global)

---

## Bug Condition Derivation

### Bug Condition Function

```pascal
FUNCTION isBugCondition(X)
  INPUT: X of type {action: string, panel: string, role: string}
  OUTPUT: boolean

  // Bug 1 & 2: Aksi hapus/edit muncul di panel siswa
  IF X.panel = 'student' AND X.role = 'siswa_ortu' AND X.action IN ['delete', 'edit']
    RETURN true
  END IF

  // Bug 3: Membuka detail pengumuman tidak mencatat status baca
  IF X.panel = 'student' AND X.role = 'siswa_ortu' AND X.action = 'view_announcement'
    RETURN true
  END IF

  RETURN false
END FUNCTION
```

### Property: Fix Checking

```pascal
// Property: Fix Checking — Aksi hapus/edit tidak boleh ada untuk siswa
FOR ALL X WHERE isBugCondition(X) AND X.action IN ['delete', 'edit'] DO
  result ← renderStudentAnnouncementTable'(X)
  ASSERT NOT contains(result, DeleteBulkAction)
  ASSERT NOT contains(result, EditAction)
END FOR

// Property: Fix Checking — Status baca tercatat saat pengumuman dibuka
FOR ALL X WHERE isBugCondition(X) AND X.action = 'view_announcement' DO
  result ← viewAnnouncement'(X)
  ASSERT isRead(X.student_id, X.announcement_id) = true
END FOR
```

### Property: Preservation Checking

```pascal
// Property: Preservation Checking
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT F(X) = F'(X)
  // Admin/guru tetap bisa edit dan hapus
  // Filter target_role tetap berjalan
  // Status baca per-siswa tetap independen
END FOR
```
