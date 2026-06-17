# Bugfix Requirements Document

## Introduction

Dropdown Select field untuk memilih **Wali Kelas** (di form Kelas) dan **Guru Pengajar** (di form Jadwal) tidak dapat menemukan guru berdasarkan nama saat dicari. Ketika admin mengetik nama guru (misalnya "Sultan"), dropdown menampilkan "Tidak ada hasil yang sesuai dengan pencarian Anda." meskipun data guru tersebut ada di database.

Penyebab root: kedua Select field menggunakan `titleAttribute: 'nip'` pada `relationship()`. Filament menggunakan `titleAttribute` sebagai kolom pencarian default, sehingga query `WHERE nip LIKE '%Sultan%'` dijalankan terhadap tabel `teachers` — bukan `WHERE users.name LIKE '%Sultan%'`. Karena kolom `nip` berisi nomor induk pegawai (bukan nama), pencarian berdasarkan nama selalu menghasilkan nol hasil.

File yang terdampak:
- `app/Filament/Clusters/Academic/Resources/SchoolClasses/Schemas/SchoolClassForm.php` — field `teacher_id` (Wali Kelas)
- `app/Filament/Clusters/Academic/Resources/Schedules/Schemas/ScheduleForm.php` — field `teacher_id` (Guru Pengajar)

---

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN admin mengetik nama guru (misalnya "Sultan") di dropdown "Wali Kelas" pada form Kelas THEN sistem menampilkan "Tidak ada hasil yang sesuai" karena query mencari di kolom `teachers.nip`, bukan `users.name`

1.2 WHEN admin mengetik nama guru (misalnya "Sultan") di dropdown "Guru Pengajar" pada form Jadwal THEN sistem menampilkan "Tidak ada hasil yang sesuai" karena query mencari di kolom `teachers.nip`, bukan `users.name`

1.3 WHEN admin mengetik NIP guru yang valid di dropdown "Wali Kelas" atau "Guru Pengajar" THEN sistem menampilkan hasil yang sesuai (karena NIP memang ada di kolom `teachers.nip`)

1.4 WHEN guru memiliki nilai `nip` yang `null` di database THEN sistem tidak pernah bisa menemukan guru tersebut melalui pencarian apapun di kedua dropdown tersebut

### Expected Behavior (Correct)

2.1 WHEN admin mengetik nama guru (misalnya "Sultan") di dropdown "Wali Kelas" pada form Kelas THEN sistem SHALL menampilkan semua guru yang namanya mengandung kata tersebut (pencarian di `users.name`)

2.2 WHEN admin mengetik nama guru (misalnya "Sultan") di dropdown "Guru Pengajar" pada form Jadwal THEN sistem SHALL menampilkan semua guru yang namanya mengandung kata tersebut (pencarian di `users.name`)

2.3 WHEN admin mengetik NIP guru yang valid di dropdown "Wali Kelas" atau "Guru Pengajar" THEN sistem SHALL menampilkan guru yang NIP-nya mengandung kata tersebut (pencarian di `teachers.nip`)

2.4 WHEN guru memiliki nilai `nip` yang `null` THEN sistem SHALL tetap dapat menemukan guru tersebut melalui pencarian nama

### Unchanged Behavior (Regression Prevention)

3.1 WHEN guru ditemukan melalui pencarian dan dipilih THEN sistem SHALL CONTINUE TO menyimpan `teacher_id` yang benar ke database

3.2 WHEN form Kelas atau Jadwal dibuka untuk edit record yang sudah ada THEN sistem SHALL CONTINUE TO menampilkan nama guru yang sudah terpilih dengan format yang benar (nama + NIP jika ada)

3.3 WHEN admin mencari guru di dropdown "Wali Kelas" atau "Guru Pengajar" menggunakan NIP THEN sistem SHALL CONTINUE TO menampilkan hasil yang sesuai

3.4 WHEN dropdown "Wali Kelas" atau "Guru Pengajar" dimuat pertama kali (preload) THEN sistem SHALL CONTINUE TO menampilkan daftar semua guru yang tersedia

3.5 WHEN admin memilih guru dari dropdown THEN sistem SHALL CONTINUE TO menampilkan label guru dalam format `Nama (NIP)` jika NIP ada, atau hanya `Nama` jika NIP kosong

---

## Bug Condition (Pseudocode)

**Bug Condition Function** — mengidentifikasi input yang memicu bug:

```pascal
FUNCTION isBugCondition(X)
  INPUT: X of type SearchQuery
  OUTPUT: boolean

  // Bug terpicu ketika pencarian dilakukan dengan string yang
  // tidak cocok dengan nilai kolom nip di tabel teachers
  RETURN X.searchString NOT LIKE ANY teachers.nip
         AND X.searchString LIKE ANY users.name
END FUNCTION
```

**Property: Fix Checking** — perilaku yang benar untuk input buggy:

```pascal
// Property: Fix Checking - Teacher Name Search
FOR ALL X WHERE isBugCondition(X) DO
  results ← searchTeacherDropdown'(X.searchString)
  ASSERT COUNT(results) >= 1
  ASSERT ALL result IN results: result.user.name LIKE '%' + X.searchString + '%'
END FOR
```

**Property: Preservation Checking** — input non-buggy harus tetap sama:

```pascal
// Property: Preservation Checking
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT searchTeacherDropdown(X) = searchTeacherDropdown'(X)
END FOR
```

**Keterangan:**
- `F` = fungsi pencarian sebelum fix (hanya mencari di `teachers.nip`)
- `F'` = fungsi pencarian setelah fix (mencari di `users.name` dan `teachers.nip`)
