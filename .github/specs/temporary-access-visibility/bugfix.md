# Bugfix Requirements Document

## Introduction

Fitur Temporary Access memungkinkan Super Admin memberikan akses sementara (abilities granular) kepada user manapun untuk resource tertentu — misalnya memberikan akses KBM, LessonPlan, atau Announcement kepada user `siswa_ortu`. Namun saat ini, fitur yang di-assign via temporary access **tidak muncul di panel user tersebut**. User dengan role `siswa_ortu` yang diberi temporary access ke resource guru tetap terjebak di panel `student` yang tidak memiliki resource tersebut, dan bahkan jika berhasil masuk ke panel yang benar, query data mengembalikan 0 hasil karena filter berbasis role yang tidak mempertimbangkan temporary access.

Bug ini berdampak pada semua user non-guru yang diberi temporary access ke KBM/LessonPlan, dan semua user yang diberi temporary access ke Announcement di luar role normalnya.

---

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN user dengan role `siswa_ortu` memiliki active temporary ability grant untuk policy KBM atau LessonPlan THEN sistem menolak akses ke panel `guru` karena `canAccessPanel()` hanya mengecek `$this->role` tanpa mempertimbangkan temporary access

1.2 WHEN user dengan role `siswa_ortu` memiliki active temporary ability grant untuk policy KBM atau LessonPlan THEN sistem tidak menampilkan resource KBM/LessonPlan di panel manapun yang dapat diakses user tersebut

1.3 WHEN user dengan temporary access ke KBM berhasil masuk ke panel `guru` THEN `KbmResource::getEloquentQuery()` mengembalikan 0 hasil karena memfilter berdasarkan `$user->teacher->id` padahal user non-guru tidak memiliki relasi `teacher`

1.4 WHEN user dengan temporary access ke LessonPlan berhasil masuk ke panel `guru` THEN `LessonPlanResource::getEloquentQuery()` mengembalikan 0 hasil karena memfilter berdasarkan `$user->teacher->id` padahal user non-guru tidak memiliki relasi `teacher`

1.5 WHEN user dengan role `siswa_ortu` memiliki active temporary ability grant untuk policy Announcement THEN `AnnouncementResource::getEloquentQuery()` di panel `student` hanya menampilkan announcement yang memiliki `target_role = 'siswa_ortu'`, bukan semua announcement yang relevan dengan temporary access

1.6 WHEN user dengan role `guru` memiliki active temporary ability grant untuk policy Announcement THEN `AnnouncementResource::getEloquentQuery()` di panel `guru` hanya menampilkan announcement yang memiliki `target_role = 'guru'`, bukan semua announcement yang relevan dengan temporary access

### Expected Behavior (Correct)

2.1 WHEN user memiliki active temporary ability grant untuk policy yang resource-nya berada di panel `guru` (KBM, LessonPlan) THEN sistem SHALL mengizinkan user tersebut masuk ke panel `guru` melalui `canAccessPanel()`

2.2 WHEN user memiliki active temporary ability grant untuk policy yang resource-nya berada di panel `kepsek` THEN sistem SHALL mengizinkan user tersebut masuk ke panel `kepsek` melalui `canAccessPanel()`

2.3 WHEN user non-guru dengan active temporary ability grant `viewAny` untuk policy KBM masuk ke panel `guru` THEN `KbmResource::getEloquentQuery()` SHALL mengembalikan semua KBM (tidak difilter berdasarkan `teacher_id`) karena user tersebut bukan guru

2.4 WHEN user non-guru dengan active temporary ability grant `viewAny` untuk policy LessonPlan masuk ke panel `guru` THEN `LessonPlanResource::getEloquentQuery()` SHALL mengembalikan semua LessonPlan (tidak difilter berdasarkan `teacher_id`) karena user tersebut bukan guru

2.5 WHEN user memiliki active temporary ability grant untuk policy Announcement THEN `AnnouncementResource::getEloquentQuery()` SHALL mengembalikan semua announcement tanpa filter `target_role`, karena temporary access sudah mengotorisasi akses tersebut

2.6 WHEN temporary ability grant user sudah expired THEN sistem SHALL menolak akses ke panel yang hanya bisa diakses via temporary access tersebut

### Unchanged Behavior (Regression Prevention)

3.1 WHEN user dengan role `guru` (tanpa temporary access) mengakses panel `guru` THEN sistem SHALL CONTINUE TO mengizinkan akses dan menampilkan hanya KBM milik guru tersebut berdasarkan `teacher_id`

3.2 WHEN user dengan role `guru` (tanpa temporary access) mengakses panel `guru` THEN sistem SHALL CONTINUE TO mengizinkan akses dan menampilkan hanya LessonPlan milik guru tersebut berdasarkan `teacher_id`

3.3 WHEN user dengan role `siswa_ortu` (tanpa temporary access) mengakses panel `student` THEN sistem SHALL CONTINUE TO menampilkan hanya announcement dengan `target_role` yang mengandung `siswa_ortu`

3.4 WHEN user dengan role `guru` (tanpa temporary access) mengakses panel `guru` THEN sistem SHALL CONTINUE TO menampilkan hanya announcement dengan `target_role` yang mengandung `guru`

3.5 WHEN user dengan role `siswa_ortu` (tanpa temporary access) mencoba mengakses panel `guru` THEN sistem SHALL CONTINUE TO menolak akses

3.6 WHEN user dengan role `super_admin` mengakses panel `admin` THEN sistem SHALL CONTINUE TO mengizinkan akses tanpa perubahan perilaku

3.7 WHEN user dengan role `kepala_sekolah` mengakses panel `kepsek` THEN sistem SHALL CONTINUE TO mengizinkan akses tanpa perubahan perilaku

3.8 WHEN user tidak aktif (`is_active = false`) mencoba mengakses panel manapun THEN sistem SHALL CONTINUE TO menolak akses

---

## Bug Condition Derivation

### Bug Condition Function

```pascal
FUNCTION isBugCondition(X)
  INPUT: X of type struct { user: User, panel: Panel, resource: string }
  OUTPUT: boolean

  // Bug terpicu ketika user punya active temporary grant
  // untuk resource di panel yang tidak sesuai role permanennya
  hasActiveGrant ← UserPolicyAbility.exists(
    user_id = X.user.id,
    is_inherited = false,
    expires_at > now()
  )

  panelMatchesRole ← canAccessPanelByRole(X.user.role, X.panel.id)

  RETURN hasActiveGrant AND NOT panelMatchesRole
END FUNCTION
```

### Property: Fix Checking

```pascal
// Property: User dengan temporary access HARUS bisa masuk ke panel yang relevan
FOR ALL X WHERE isBugCondition(X) DO
  result ← canAccessPanel'(X.user, X.panel)
  ASSERT result = true
END FOR

// Property: Query data TIDAK BOLEH mengembalikan 0 hasil karena filter role
FOR ALL X WHERE isBugCondition(X) AND X.resource IN ['KBM', 'LessonPlan'] DO
  result ← getEloquentQuery'(X.user)
  ASSERT result.count() > 0 OR result IS NOT filtered_by_teacher_id
END FOR
```

### Property: Preservation Checking

```pascal
// Property: User tanpa temporary access HARUS tetap berperilaku seperti semula
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT canAccessPanel'(X.user, X.panel) = canAccessPanel(X.user, X.panel)
  ASSERT getEloquentQuery'(X.user) = getEloquentQuery(X.user)
END FOR
```
