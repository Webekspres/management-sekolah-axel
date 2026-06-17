# Bugfix Requirements Document

## Introduction

Terdapat dua bug yang saling berkaitan pada fitur RPP (Rencana Pelaksanaan Pembelajaran) di panel Guru:

**Bug A — RPP berstatus `REVISED` tidak bisa diedit atau disimpan:** Guru yang menerima permintaan revisi dari kepsek tidak dapat mengupload ulang file RPP karena form dikunci dan penyimpanan ditolak. Tiga lokasi bug: (1) `isContentLocked()` di `LessonPlanForm` mengunci form untuk `REVISED`, (2) `mutateFormDataBeforeSave()` di `EditLessonPlan` menolak save untuk `REVISED`, (3) `LessonPlanPolicy::update()` menolak update untuk `REVISED` secara diam-diam.

**Bug B — RPP baru (create dari 0) tidak tersimpan ke database:** Guru mengisi form RPP baru, klik simpan, tidak ada error, tapi data tidak muncul di tabel dan tidak ada di database. Root cause masih perlu dikonfirmasi melalui exploratory test — kemungkinan ada di policy, validasi form, atau file upload handling.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN guru mencoba mengedit RPP dengan status `REVISED` THEN sistem mengunci semua field form (termasuk FileUpload) sehingga guru tidak dapat mengupload file RPP yang telah direvisi

1.2 WHEN guru mencoba menyimpan perubahan RPP dengan status `REVISED` THEN sistem menolak penyimpanan karena `mutateFormDataBeforeSave` hanya mengizinkan `DRAFT` dan `PENDING`

1.3 WHEN guru mencoba menyimpan perubahan RPP dengan status `REVISED` THEN `LessonPlanPolicy::update()` menolak operasi secara diam-diam karena hanya mengizinkan `DRAFT` dan `PENDING`

1.4 WHEN guru mengisi form RPP baru dan klik simpan THEN data tidak tersimpan ke database tanpa pesan error apapun

1.5 WHEN guru mencoba menyimpan perubahan RPP dengan status `PENDING` THEN sistem menolak penyimpanan dengan pesan error, padahal seharusnya ditolak (ini perilaku yang benar tapi perlu diverifikasi)

1.6 WHEN guru mengakses halaman edit RPP dengan status `APPROVED` THEN sistem menampilkan form yang terkunci tanpa pesan yang jelas

### Expected Behavior (Correct)

2.1 WHEN guru mencoba mengedit RPP dengan status `REVISED` THEN sistem SHALL menampilkan semua field form dalam keadaan aktif sehingga guru dapat mengupload ulang file RPP

2.2 WHEN guru mencoba menyimpan perubahan RPP dengan status `REVISED` THEN sistem SHALL menyimpan perubahan tersebut (status tetap `REVISED`, siap diajukan ulang)

2.3 WHEN guru mengisi form RPP baru dengan data lengkap dan klik simpan THEN sistem SHALL menyimpan RPP ke database dengan status `DRAFT` dan menampilkan RPP di tabel

2.4 WHEN guru mencoba menyimpan perubahan RPP dengan status `PENDING` THEN sistem SHALL menolak penyimpanan karena RPP sedang menunggu keputusan kepsek

2.5 WHEN guru mengakses halaman edit RPP dengan status `APPROVED` THEN sistem SHALL menampilkan form yang terkunci dengan pesan yang jelas bahwa RPP sudah disetujui

### Unchanged Behavior (Regression Prevention)

3.1 WHEN guru mengedit RPP dengan status `DRAFT` THEN sistem SHALL CONTINUE TO menampilkan semua field form dalam keadaan aktif dan menyimpan perubahan dengan benar

3.2 WHEN guru mengajukan RPP berstatus `DRAFT` atau `REVISED` untuk approval THEN sistem SHALL CONTINUE TO mengubah status menjadi `PENDING` dan mencatat aktivitas

3.3 WHEN kepsek menyetujui RPP berstatus `PENDING` THEN sistem SHALL CONTINUE TO mengubah status menjadi `APPROVED`

3.4 WHEN kepsek meminta revisi RPP berstatus `PENDING` THEN sistem SHALL CONTINUE TO mengubah status menjadi `REVISED` dan menyimpan catatan revisi

3.5 WHEN guru mencoba menghapus RPP berstatus `APPROVED` THEN sistem SHALL CONTINUE TO mencegah penghapusan tersebut

---

## Bug Condition (Pseudocode)

**Bug Condition Function** — Mengidentifikasi input yang memicu bug:

```pascal
FUNCTION isBugCondition(X)
  INPUT: X of type LessonPlan OR CreateLessonPlanRequest
  OUTPUT: boolean

  // Bug A: RPP berstatus REVISED tidak bisa diedit/disimpan
  IF X IS LessonPlan AND X.status = 'REVISED' THEN RETURN true

  // Bug B: RPP baru (create) tidak tersimpan
  IF X IS CreateLessonPlanRequest THEN RETURN true

  RETURN false
END FUNCTION
```

**Property: Fix Checking** — Perilaku yang benar untuk input buggy:

```pascal
// Bug A Fix: REVISED RPP dapat diedit dan disimpan
FOR ALL X WHERE X IS LessonPlan AND X.status = 'REVISED' DO
  form ← renderEditForm'(X)
  ASSERT form.fields.are_enabled = true
  
  result ← saveChanges'(X, newFileData)
  ASSERT result.success = true
  ASSERT result.status = 'REVISED'
END FOR

// Bug B Fix: RPP baru tersimpan ke database
FOR ALL X WHERE X IS CreateLessonPlanRequest DO
  result ← createLessonPlan'(X)
  ASSERT result.success = true
  ASSERT LessonPlan::count() INCREASED BY 1
END FOR
```

**Property: Preservation Checking** — Perilaku non-buggy tidak berubah:

```pascal
// Property: Preservation Checking
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT editForm(X) = editForm'(X)
  ASSERT saveResult(X) = saveResult'(X)
END FOR
```
