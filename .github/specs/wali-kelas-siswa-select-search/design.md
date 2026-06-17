# Wali Kelas & Guru Pengajar Select Search — Bugfix Design

## Overview

Dua dropdown Select di form Filament tidak dapat menemukan guru berdasarkan nama karena `titleAttribute: 'nip'` menyebabkan Filament membangun query pencarian ke kolom `teachers.nip`, bukan `users.name`. Fix yang diperlukan adalah mengganti mekanisme pencarian di kedua form agar query melakukan JOIN ke tabel `users` dan mencari di `users.name` (serta opsional `teachers.nip`), tanpa mengubah tampilan label maupun perilaku lainnya.

File yang terdampak:
- `app/Filament/Clusters/Academic/Resources/SchoolClasses/Schemas/SchoolClassForm.php` — field `teacher_id` (Wali Kelas)
- `app/Filament/Clusters/Academic/Resources/Schedules/Schemas/ScheduleForm.php` — field `teacher_id` (Guru Pengajar)

## Glossary

- **Bug_Condition (C)**: Kondisi yang memicu bug — pencarian dilakukan dengan string yang cocok dengan `users.name` tetapi tidak cocok dengan `teachers.nip`
- **Property (P)**: Perilaku yang benar — dropdown mengembalikan guru yang namanya mengandung string pencarian
- **Preservation**: Perilaku yang tidak boleh berubah — label tampilan, penyimpanan `teacher_id`, preload, dan pencarian via NIP
- **`titleAttribute`**: Parameter pada `relationship()` Filament yang digunakan sebagai kolom pencarian default; saat ini diset ke `'nip'` sehingga query mencari di `teachers.nip`
- **`modifyQueryUsing`**: Callback pada `relationship()` yang menerima `$search` sebagai parameter injeksi di Filament v5, memungkinkan kustomisasi query pencarian
- **`getOptionLabelFromRecordUsing`**: Callback yang mengontrol label yang ditampilkan untuk setiap opsi; sudah benar dan tidak perlu diubah
- **`teachers` table**: Tabel yang menyimpan data guru dengan kolom `id`, `user_id`, `nip`, `employment_status`
- **`users` table**: Tabel yang menyimpan data akun dengan kolom `name` (nama lengkap guru)

## Bug Details

### Bug Condition

Bug terpicu ketika admin mengetik nama guru di dropdown "Wali Kelas" atau "Guru Pengajar". Filament menggunakan `titleAttribute` sebagai kolom pencarian default, sehingga query `WHERE teachers.nip LIKE '%<search>%'` dijalankan. Karena `nip` berisi nomor induk pegawai (bukan nama), pencarian nama selalu menghasilkan nol hasil. Guru dengan `nip = null` tidak dapat ditemukan sama sekali.

**Formal Specification:**
```
FUNCTION isBugCondition(X)
  INPUT: X of type SearchQuery { searchString: string, context: 'wali_kelas' | 'guru_pengajar' }
  OUTPUT: boolean

  RETURN X.searchString NOT LIKE ANY teachers.nip
         AND X.searchString LIKE ANY users.name
         AND X.context IN ['wali_kelas', 'guru_pengajar']
END FUNCTION
```

### Examples

- Admin mengetik "Sultan" di dropdown Wali Kelas → hasil kosong (bug), seharusnya menampilkan guru bernama "Sultan Agung"
- Admin mengetik "Budi" di dropdown Guru Pengajar → hasil kosong (bug), seharusnya menampilkan semua guru bernama "Budi ..."
- Admin mengetik NIP "198501012010" di dropdown Wali Kelas → hasil ditemukan (bukan bug, NIP cocok)
- Guru dengan `nip = null` tidak pernah bisa ditemukan via pencarian apapun (bug terparah)
- Admin membuka form edit kelas yang sudah punya wali kelas → label guru tampil benar (bukan bug, ini preload)

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Label opsi guru tetap ditampilkan dalam format `Nama (NIP)` jika NIP ada, atau hanya `Nama` jika NIP null — dikontrol oleh `getOptionLabelFromRecordUsing` yang tidak diubah
- Saat form edit dibuka, guru yang sudah terpilih tetap ditampilkan dengan label yang benar
- Pencarian menggunakan NIP tetap berfungsi (query mencari di `teachers.nip` juga)
- Preload daftar guru saat dropdown pertama dibuka tetap berfungsi
- Penyimpanan `teacher_id` ke database tetap benar setelah guru dipilih
- Validasi `required` pada field tetap berfungsi

**Scope:**
Semua input yang TIDAK melibatkan pencarian nama guru di kedua dropdown tersebut tidak terpengaruh oleh fix ini. Ini mencakup:
- Semua field lain di `SchoolClassForm` dan `ScheduleForm`
- Logika validasi bentrok jadwal di `ScheduleForm`
- Dropdown lain (`class_id`, `subject_id`, `day_of_week`, dll.)

## Hypothesized Root Cause

Berdasarkan analisis kode, penyebab utama adalah:

1. **`titleAttribute: 'nip'` sebagai kolom pencarian default**: Filament v5 menggunakan `titleAttribute` untuk membangun query `WHERE {titleAttribute} LIKE '%{search}%'`. Karena `nip` ada di tabel `teachers` (bukan `users`), dan nama guru ada di `users.name`, pencarian nama tidak pernah menemukan hasil.

2. **Tidak ada JOIN ke tabel `users` dalam query pencarian**: `modifyQueryUsing` yang ada hanya melakukan `->with('user')` (eager load relasi), bukan `->join('users', ...)`. Eager load tidak mempengaruhi klausa `WHERE` pada query pencarian.

3. **Guru dengan `nip = null`**: Karena query mencari `WHERE nip LIKE '%x%'`, guru dengan `nip = null` tidak pernah cocok dengan kondisi apapun.

4. **Solusi yang tersedia di Filament v5**: `modifyQueryUsing` di Filament v5 mendukung injeksi parameter `$search` (string pencarian saat ini). Ini memungkinkan kita membangun query JOIN + WHERE secara manual tanpa perlu `getSearchResultsUsing()`.

## Correctness Properties

Property 1: Bug Condition — Teacher Name Search Returns Results

_For any_ `SearchQuery` X where `isBugCondition(X)` returns true (yaitu: search string cocok dengan `users.name` tapi tidak cocok dengan `teachers.nip`), fungsi pencarian yang sudah diperbaiki SHALL mengembalikan setidaknya satu hasil yang mengandung guru dengan nama yang cocok dengan string pencarian tersebut.

**Validates: Requirements 2.1, 2.2, 2.4**

Property 2: Preservation — NIP Search and Non-Search Behavior Unchanged

_For any_ input yang TIDAK memenuhi `isBugCondition` (yaitu: pencarian via NIP, preload tanpa search, atau pemilihan opsi yang sudah ada), fungsi pencarian yang sudah diperbaiki SHALL menghasilkan hasil yang sama dengan fungsi asli, mempertahankan semua perilaku yang sudah benar.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

## Fix Implementation

### Changes Required

Asumsi root cause analysis benar, fix dilakukan dengan mengganti `titleAttribute: 'nip'` menjadi `titleAttribute: 'user_id'` (dummy — tidak digunakan untuk pencarian) dan mengubah `modifyQueryUsing` agar menerima `$search` dan melakukan JOIN + WHERE secara manual.

**Pendekatan yang dipilih**: Gunakan `modifyQueryUsing` dengan injeksi `$search` (Filament v5 feature) untuk melakukan `JOIN users` dan `WHERE users.name LIKE` atau `teachers.nip LIKE`. Ini lebih bersih daripada `getSearchResultsUsing()` karena tetap menggunakan mekanisme `relationship()` sehingga `getOptionLabelFromRecordUsing` dan preload tetap bekerja.

---

**File 1**: `app/Filament/Clusters/Academic/Resources/SchoolClasses/Schemas/SchoolClassForm.php`

**Field**: `teacher_id` (Wali Kelas)

**Specific Changes**:
1. **Ganti `titleAttribute`**: Ubah dari `'nip'` menjadi `'user_id'` — ini hanya placeholder karena pencarian akan di-override oleh `modifyQueryUsing`
2. **Tambah JOIN di `modifyQueryUsing`**: Inject `$search` dan tambahkan `->join('users', 'users.id', '=', 'teachers.user_id')` serta `->where(fn ($q) => $q->where('users.name', 'like', "%{$search}%")->orWhere('teachers.nip', 'like', "%{$search}%"))` ketika `$search` tidak kosong
3. **Tambah `->select('teachers.*')`**: Untuk menghindari ambiguitas kolom `id` setelah JOIN

---

**File 2**: `app/Filament/Clusters/Academic/Resources/Schedules/Schemas/ScheduleForm.php`

**Field**: `teacher_id` (Guru Pengajar)

**Specific Changes**: Sama persis dengan perubahan di `SchoolClassForm.php` di atas.

---

**Catatan**: `StudentForm.php` tidak perlu diubah — tidak ada dropdown guru di form tersebut. Field `class_id` di `StudentForm` menggunakan `options()` biasa (bukan `relationship()` dengan `titleAttribute`), sehingga tidak terdampak bug ini.

## Testing Strategy

### Validation Approach

Strategi testing mengikuti dua fase: pertama, jalankan test pada kode yang belum diperbaiki untuk membuktikan bug ada (exploratory), lalu jalankan test yang sama setelah fix untuk membuktikan bug sudah teratasi dan tidak ada regresi.

### Exploratory Bug Condition Checking

**Goal**: Buktikan bug ada SEBELUM fix diimplementasikan. Konfirmasi atau bantah root cause analysis.

**Test Plan**: Tulis test yang mensimulasikan pencarian nama guru di dropdown Filament dan assert bahwa hasil dikembalikan. Jalankan pada kode yang belum diperbaiki — test harus GAGAL.

**Test Cases**:
1. **Pencarian nama di Wali Kelas**: Cari guru berdasarkan `users.name` di `SchoolClassForm` → harus gagal pada kode unfixed (mengembalikan array kosong)
2. **Pencarian nama di Guru Pengajar**: Cari guru berdasarkan `users.name` di `ScheduleForm` → harus gagal pada kode unfixed
3. **Guru dengan NIP null**: Cari guru yang `nip = null` berdasarkan nama → harus gagal pada kode unfixed
4. **Pencarian NIP**: Cari guru berdasarkan NIP → harus LULUS pada kode unfixed (ini bukan bug)

**Expected Counterexamples**:
- Query yang dijalankan adalah `WHERE teachers.nip LIKE '%<nama>%'` — tidak menemukan hasil untuk pencarian nama
- Guru dengan `nip = null` tidak pernah cocok dengan kondisi LIKE apapun

### Fix Checking

**Goal**: Verifikasi bahwa untuk semua input di mana `isBugCondition` true, fungsi yang sudah diperbaiki menghasilkan perilaku yang benar.

**Pseudocode:**
```
FOR ALL X WHERE isBugCondition(X) DO
  results := searchTeacherDropdown_fixed(X.searchString)
  ASSERT COUNT(results) >= 1
  ASSERT ALL result IN results: result.user.name LIKE '%' + X.searchString + '%'
         OR result.nip LIKE '%' + X.searchString + '%'
END FOR
```

### Preservation Checking

**Goal**: Verifikasi bahwa untuk semua input di mana `isBugCondition` false, fungsi yang sudah diperbaiki menghasilkan hasil yang sama dengan fungsi asli.

**Pseudocode:**
```
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT searchTeacherDropdown_original(X) = searchTeacherDropdown_fixed(X)
END FOR
```

**Testing Approach**: Property-based testing direkomendasikan untuk preservation checking karena:
- Menghasilkan banyak test case secara otomatis di seluruh domain input
- Menangkap edge case yang mungkin terlewat oleh unit test manual
- Memberikan jaminan kuat bahwa perilaku tidak berubah untuk semua input non-buggy

**Test Cases**:
1. **Pencarian NIP tetap bekerja**: Cari guru via NIP setelah fix → hasil sama dengan sebelum fix
2. **Preload tetap bekerja**: Buka dropdown tanpa mengetik → semua guru tetap muncul
3. **Label format tetap benar**: Guru yang dipilih tetap ditampilkan sebagai `Nama (NIP)` atau `Nama`
4. **Penyimpanan `teacher_id` tetap benar**: Setelah memilih guru, `teacher_id` yang tersimpan tetap benar

### Unit Tests

- Test bahwa query pencarian nama menghasilkan hasil yang benar untuk `SchoolClassForm`
- Test bahwa query pencarian nama menghasilkan hasil yang benar untuk `ScheduleForm`
- Test bahwa guru dengan `nip = null` dapat ditemukan via pencarian nama
- Test bahwa pencarian NIP tetap berfungsi setelah fix
- Test edge case: search string kosong, search string dengan karakter khusus

### Property-Based Tests

- Generate random nama guru dan verifikasi bahwa pencarian selalu menemukan guru yang namanya mengandung string tersebut
- Generate random NIP dan verifikasi bahwa pencarian NIP tetap menghasilkan hasil yang sama sebelum dan sesudah fix
- Generate random kombinasi nama + NIP dan verifikasi bahwa pencarian di salah satu kolom selalu menemukan guru yang relevan

### Integration Tests

- Test full flow: buka form Kelas, cari guru via nama, pilih guru, simpan → `teacher_id` tersimpan benar
- Test full flow: buka form Jadwal, cari guru via nama, pilih guru, simpan → `teacher_id` tersimpan benar
- Test edit record: buka form edit yang sudah punya guru terpilih → label guru tampil benar
- Test bahwa validasi bentrok jadwal di `ScheduleForm` tetap berfungsi setelah fix
