# Temporary Access Visibility — Bugfix Design

## Overview

User dengan temporary ability grant tidak dapat melihat resource yang di-grant karena dua lapisan pemblokiran:

1. **Panel access gate** — `User::canAccessPanel()` hanya mengecek `$this->role` permanen, sehingga user non-guru/non-kepsek tidak bisa masuk ke panel `guru` atau `kepsek` meskipun punya active grant.
2. **Eloquent query filter** — `KbmResource::getEloquentQuery()` dan `LessonPlanResource::getEloquentQuery()` memfilter berdasarkan `teacher_id`, dan `AnnouncementResource::getEloquentQuery()` memfilter berdasarkan `target_role`. Keduanya tidak mempertimbangkan temporary access, sehingga user yang berhasil masuk panel pun mendapat 0 hasil.

Fix mencakup lima perubahan: (1) tambah method `hasTemporaryAccessToPanel()` di `TemporaryAccessManager`, (2) update `canAccessPanel()` di `User`, (3) bypass teacher filter di `KbmResource` (Guru panel), (4) bypass teacher filter di `LessonPlanResource` (Guru panel), (5) bypass role filter di `AnnouncementResource` (shared, dipakai di panel Guru, Kepsek, dan Student). Selain itu, `KbmResource` dan `LessonPlanResource` di panel Guru perlu ditambahkan `canAccess()` agar konsisten dengan resource lain seperti `AcademicYearResource`.

---

## Glossary

- **Bug_Condition (C)**: Kondisi yang memicu bug — user memiliki active direct `UserPolicyAbility` (not expired, `is_inherited = false`) untuk policy yang relevan dengan panel tertentu, namun panel tersebut tidak dapat diakses karena `canAccessPanel()` hanya mengecek role permanen.
- **Property (P)**: Perilaku yang diharapkan — user dengan active temporary grant SHALL dapat masuk ke panel yang relevan dan melihat data tanpa diblokir filter role/teacher.
- **Preservation**: Perilaku yang tidak boleh berubah — user dengan role permanen (guru, kepsek, siswa_ortu, super_admin) tetap berperilaku persis seperti sebelumnya.
- **`canAccessPanel()`**: Method di `App\Models\User` yang mengontrol apakah user boleh masuk ke panel Filament tertentu.
- **`hasTemporaryAccessToPanel()`**: Method baru di `App\Support\TemporaryAccessManager` yang mengecek apakah user punya active direct ability untuk policy yang `permanent_roles`-nya mengandung role target panel.
- **`getEloquentQuery()`**: Method static di Filament Resource yang menentukan base query untuk tabel/list. Bug ada di versi Guru panel untuk KBM, LessonPlan, dan shared AnnouncementResource.
- **`UserPolicyAbility`**: Model yang menyimpan abilities langsung (`is_inherited = false`) dengan `expires_at`.
- **`AccessPolicy`**: Model dengan kolom `permanent_roles` (JSON array) yang mendefinisikan role mana yang inherit ability ini secara permanen.

---

## Bug Details

### Bug Condition

Bug terpicu ketika user memiliki active direct `UserPolicyAbility` untuk policy yang resource-nya berada di panel `guru` atau `kepsek`, namun role permanen user tersebut tidak termasuk dalam role yang diizinkan oleh `canAccessPanel()`.

**Formal Specification:**

```
FUNCTION isBugCondition(input)
  INPUT: input of type struct { user: User, panelId: string }
  OUTPUT: boolean

  hasActiveDirectAbility ← UserPolicyAbility.exists(
    user_id     = input.user.id,
    is_inherited = false,
    expires_at  > now()
  )

  panelAllowedByRole ← canAccessPanelByRole(input.user.role, input.panelId)
  // canAccessPanelByRole: 'guru' → role='guru', 'kepsek' → role IN ['kepala_sekolah','super_admin']

  RETURN hasActiveDirectAbility AND NOT panelAllowedByRole
END FUNCTION
```

### Examples

- **Contoh 1 — siswa_ortu diberi grant KBM**: User dengan `role = 'siswa_ortu'` mendapat `UserPolicyAbility` untuk policy KBM (`permanent_roles = ['guru']`). `canAccessPanel('guru')` mengembalikan `false` karena `effectiveRole() = 'siswa_ortu'`. User tidak bisa masuk panel guru sama sekali. *(Expected: bisa masuk)*
- **Contoh 2 — siswa_ortu masuk panel guru, query KBM kosong**: Andaikan user berhasil masuk, `KbmResource::getEloquentQuery()` mengecek `$user->teacher` → `null` → mengembalikan `whereRaw('1 = 0')`. Hasilnya 0 record. *(Expected: semua KBM tampil)*
- **Contoh 3 — guru diberi grant Announcement**: User dengan `role = 'guru'` punya grant untuk policy Announcement. `AnnouncementResource::getEloquentQuery()` memfilter `whereJsonContains('target_role', 'guru')`. Announcement yang tidak menarget `guru` tidak muncul. *(Expected: semua announcement tampil)*
- **Contoh 4 — grant expired**: User punya `UserPolicyAbility` dengan `expires_at` di masa lalu. `isBugCondition` = false karena `hasActiveDirectAbility = false`. Tidak ada perubahan perilaku. *(Expected: tetap ditolak — bukan bug)*

---

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**

- User dengan `role = 'guru'` (tanpa temporary access) tetap bisa masuk panel `guru` dan hanya melihat KBM/LessonPlan miliknya sendiri berdasarkan `teacher_id`.
- User dengan `role = 'kepala_sekolah'` tetap bisa masuk panel `kepsek` tanpa perubahan.
- User dengan `role = 'siswa_ortu'` (tanpa temporary access) tetap hanya melihat announcement dengan `target_role` yang mengandung `siswa_ortu`.
- User dengan `role = 'guru'` (tanpa temporary access) tetap hanya melihat announcement dengan `target_role` yang mengandung `guru`.
- User dengan `role = 'super_admin'` tetap bisa masuk panel `admin` dan melihat semua data.
- User tidak aktif (`is_active = false`) tetap ditolak di semua panel.
- User dengan `role = 'siswa_ortu'` (tanpa temporary access) tetap tidak bisa masuk panel `guru`.

**Scope:**

Semua input yang TIDAK memenuhi `isBugCondition` (tidak punya active direct ability, atau role permanen sudah cukup) harus menghasilkan perilaku identik dengan kode sebelum fix.

---

## Hypothesized Root Cause

Berdasarkan analisis kode:

1. **`canAccessPanel()` tidak mengecek temporary access**: Method ini hanya memanggil `$this->effectiveRole()` yang selalu mengembalikan `$this->role` permanen. Tidak ada hook ke `TemporaryAccessManager`. Ini adalah root cause utama untuk bug 1.1 dan 1.2.

2. **`KbmResource::getEloquentQuery()` (Guru panel) mengasumsikan semua user adalah guru**: Guard `if (! $user->teacher) { return $query->whereRaw('1 = 0'); }` tidak mempertimbangkan bahwa user non-guru bisa punya legitimate access via temporary grant. Root cause untuk bug 1.3.

3. **`LessonPlanResource::getEloquentQuery()` (Guru panel) identik dengan KBM**: Sama persis — guard `if (! $user->teacher)` memblokir user non-guru. Root cause untuk bug 1.4.

4. **`AnnouncementResource::getEloquentQuery()` (shared) hanya filter berdasarkan `effectiveRole()`**: `whereJsonContains('target_role', $role)` tidak mempertimbangkan temporary access. Root cause untuk bug 1.5 dan 1.6.

5. **`KbmResource` dan `LessonPlanResource` di Guru panel tidak punya `canAccess()`**: Resource lain seperti `AcademicYearResource` sudah punya `canAccess()` yang mengecek temporary access. Inkonsistensi ini menyebabkan resource tidak muncul di sidebar untuk user dengan temporary access.

---

## Correctness Properties

Property 1: Bug Condition — Panel Access via Temporary Grant

_For any_ user yang memiliki active direct `UserPolicyAbility` (not expired, `is_inherited = false`) untuk policy yang `permanent_roles`-nya mengandung role target panel (`guru` untuk panel `guru`, `kepala_sekolah` untuk panel `kepsek`), method `canAccessPanel()` yang sudah di-fix SHALL mengembalikan `true`, mengizinkan user masuk ke panel tersebut.

**Validates: Requirements 2.1, 2.2**

Property 2: Bug Condition — Query Non-Empty untuk KBM/LessonPlan

_For any_ user non-guru yang memiliki active temporary ability grant `viewAny` untuk policy KBM atau LessonPlan, method `getEloquentQuery()` yang sudah di-fix SHALL mengembalikan query tanpa filter `teacher_id`, sehingga semua record dapat dilihat.

**Validates: Requirements 2.3, 2.4**

Property 3: Bug Condition — Query Non-Empty untuk Announcement

_For any_ user yang memiliki active temporary ability grant `viewAny` untuk policy Announcement, method `getEloquentQuery()` yang sudah di-fix SHALL mengembalikan query tanpa filter `target_role`, sehingga semua announcement dapat dilihat.

**Validates: Requirements 2.5**

Property 4: Preservation — User Tanpa Temporary Access

_For any_ user yang TIDAK memiliki active direct `UserPolicyAbility` (atau role permanennya sudah cukup), semua method yang di-fix SHALL menghasilkan output yang identik dengan kode sebelum fix — tidak ada perubahan perilaku untuk `canAccessPanel()`, `getEloquentQuery()` KBM, LessonPlan, maupun Announcement.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8**

Property 5: Preservation — Expired Grant Ditolak

_For any_ user yang memiliki `UserPolicyAbility` dengan `expires_at <= now()`, sistem SHALL menolak akses ke panel yang hanya bisa diakses via temporary access tersebut, identik dengan user yang tidak punya grant sama sekali.

**Validates: Requirements 2.6**

---

## Fix Implementation

### Changes Required

#### Fix 1: `app/Support/TemporaryAccessManager.php`

**Tambah method `hasTemporaryAccessToPanel()`:**

```php
/**
 * Check if user has any active direct ability for policies relevant to a panel.
 * Panel relevance is determined by matching permanent_roles of the policy.
 *
 * @param  string  $panelId  e.g. 'guru', 'kepsek'
 */
public function hasTemporaryAccessToPanel(User $user, string $panelId): bool
{
    $targetRoles = match ($panelId) {
        'guru'   => ['guru'],
        'kepsek' => ['kepala_sekolah'],
        default  => [],
    };

    if (empty($targetRoles)) {
        return false;
    }

    foreach ($targetRoles as $role) {
        $hasGrant = UserPolicyAbility::query()
            ->forUser($user->id)
            ->direct()
            ->notExpired()
            ->whereHas('accessPolicy', function ($q) use ($role): void {
                $q->where('is_active', true)
                  ->whereJsonContains('permanent_roles', $role);
            })
            ->exists();

        if ($hasGrant) {
            return true;
        }
    }

    return false;
}
```

#### Fix 2: `app/Models/User.php`

**Update `canAccessPanel()` untuk mengecek temporary access:**

```php
public function canAccessPanel(Panel $panel): bool
{
    if (! $this->is_active) {
        return false;
    }

    $effectiveRole = $this->effectiveRole();

    $allowedByRole = match ($panel->getId()) {
        'auth'    => true,
        'admin'   => $effectiveRole === 'super_admin',
        'kepsek'  => in_array($effectiveRole, ['super_admin', 'kepala_sekolah'], true),
        'guru'    => $effectiveRole === 'guru',
        'student' => $effectiveRole === 'siswa_ortu',
        default   => false,
    };

    if ($allowedByRole) {
        return true;
    }

    // Cek temporary access untuk panel yang mendukungnya
    if (in_array($panel->getId(), ['guru', 'kepsek'], true)) {
        return app(TemporaryAccessManager::class)
            ->hasTemporaryAccessToPanel($this, $panel->getId());
    }

    return false;
}
```

#### Fix 3: `app/Filament/Guru/Resources/Kbms/KbmResource.php`

**Tambah `canAccess()` dan update `getEloquentQuery()`:**

```php
public static function canAccess(): bool
{
    /** @var User|null $user */
    $user = auth()->user();

    if (! $user) {
        return false;
    }

    if ($user->role === 'guru') {
        return true;
    }

    return app(TemporaryAccessManager::class)
        ->hasTemporaryPolicyGrant($user, 'viewAny', Kbm::class);
}

public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    /** @var User $user */
    $user = auth()->user();

    // Bypass teacher filter untuk user dengan temporary access
    if (app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, 'viewAny', Kbm::class)) {
        return $query;
    }

    if (! $user->teacher) {
        return $query->whereRaw('1 = 0');
    }

    return $query->whereHas('schedule', function (Builder $builder) use ($user): void {
        $builder->where('teacher_id', $user->teacher->id);
    });
}
```

#### Fix 4: `app/Filament/Guru/Resources/LessonPlans/LessonPlanResource.php`

**Tambah `canAccess()` dan update `getEloquentQuery()` (identik dengan Fix 3):**

```php
public static function canAccess(): bool
{
    /** @var User|null $user */
    $user = auth()->user();

    if (! $user) {
        return false;
    }

    if ($user->role === 'guru') {
        return true;
    }

    return app(TemporaryAccessManager::class)
        ->hasTemporaryPolicyGrant($user, 'viewAny', LessonPlan::class);
}

public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    /** @var User $user */
    $user = auth()->user();

    // Bypass teacher filter untuk user dengan temporary access
    if (app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, 'viewAny', LessonPlan::class)) {
        return $query;
    }

    if (! $user->teacher) {
        return $query->whereRaw('1 = 0');
    }

    return $query->where('teacher_id', $user->teacher->id);
}
```

#### Fix 5: `app/Filament/Resources/Announcements/AnnouncementResource.php`

**Update `getEloquentQuery()` untuk bypass role filter saat ada temporary access:**

```php
public static function getEloquentQuery(): Builder
{
    $user = auth()->user();
    $query = parent::getEloquentQuery();
    $role = $user->effectiveRole();

    if ($role === 'super_admin') {
        return $query;
    }

    // Bypass role filter untuk user dengan temporary access ke Announcement
    if (app(TemporaryAccessManager::class)->hasTemporaryPolicyGrant($user, 'viewAny', Announcement::class)) {
        return $query;
    }

    return $query->whereJsonContains('target_role', $role);
}
```

**Catatan:** Fix ini juga berlaku untuk `app/Filament/Student/Resources/Announcements/AnnouncementResource.php` yang memiliki `getEloquentQuery()` identik.

---

## Testing Strategy

### Validation Approach

Strategi testing mengikuti dua fase: pertama, jalankan exploratory tests pada kode **unfixed** untuk mengkonfirmasi root cause; kedua, jalankan fix checking dan preservation checking setelah fix diterapkan.

### Exploratory Bug Condition Checking

**Goal**: Konfirmasi root cause dengan mendemonstrasikan bug pada kode unfixed.

**Test Plan**: Buat user dengan role `siswa_ortu`, assign active `UserPolicyAbility` untuk policy KBM, lalu assert bahwa `canAccessPanel('guru')` mengembalikan `false` dan `getEloquentQuery()` mengembalikan 0 hasil.

**Test Cases:**

1. **Panel Access Blocked** — User `siswa_ortu` dengan active KBM grant: assert `canAccessPanel('guru') === false` *(akan fail setelah fix)*
2. **KBM Query Empty** — User `siswa_ortu` tanpa relasi teacher: assert `KbmResource::getEloquentQuery()` mengembalikan `whereRaw('1 = 0')` *(akan fail setelah fix)*
3. **LessonPlan Query Empty** — User `siswa_ortu` tanpa relasi teacher: assert `LessonPlanResource::getEloquentQuery()` mengembalikan `whereRaw('1 = 0')` *(akan fail setelah fix)*
4. **Announcement Query Filtered** — User `guru` dengan Announcement grant: assert query mengandung `whereJsonContains('target_role', 'guru')` *(akan fail setelah fix)*

**Expected Counterexamples:**

- `canAccessPanel()` mengembalikan `false` untuk user dengan active grant karena hanya mengecek `$this->role`.
- `getEloquentQuery()` mengembalikan empty set karena `$user->teacher === null`.

### Fix Checking

**Goal**: Verifikasi bahwa untuk semua input di mana `isBugCondition` = true, fungsi yang sudah di-fix menghasilkan perilaku yang benar.

**Pseudocode:**

```
FOR ALL input WHERE isBugCondition(input) DO
  result_panel  := canAccessPanel_fixed(input.user, input.panel)
  result_query  := getEloquentQuery_fixed(input.user)

  ASSERT result_panel = true
  ASSERT result_query IS NOT filtered_by_teacher_id  // untuk KBM/LessonPlan
  ASSERT result_query IS NOT filtered_by_target_role // untuk Announcement
END FOR
```

### Preservation Checking

**Goal**: Verifikasi bahwa untuk semua input di mana `isBugCondition` = false, fungsi yang sudah di-fix menghasilkan output identik dengan fungsi original.

**Pseudocode:**

```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT canAccessPanel_original(input.user, input.panel)
       = canAccessPanel_fixed(input.user, input.panel)

  ASSERT getEloquentQuery_original(input.user).toSql()
       = getEloquentQuery_fixed(input.user).toSql()
END FOR
```

**Testing Approach**: Property-based testing direkomendasikan untuk preservation checking karena:
- Menghasilkan banyak kombinasi user/role/panel secara otomatis.
- Menangkap edge case yang mungkin terlewat oleh unit test manual.
- Memberikan jaminan kuat bahwa tidak ada regresi untuk semua non-buggy inputs.

**Test Cases:**

1. **Guru Normal** — User `role = 'guru'` tanpa temporary access: `canAccessPanel('guru')` tetap `true`, query KBM tetap filter by `teacher_id`.
2. **Kepsek Normal** — User `role = 'kepala_sekolah'`: `canAccessPanel('kepsek')` tetap `true`.
3. **Siswa Tanpa Grant** — User `role = 'siswa_ortu'` tanpa grant: `canAccessPanel('guru')` tetap `false`.
4. **Super Admin** — User `role = 'super_admin'`: semua panel tetap accessible, query Announcement tetap tanpa filter.
5. **Expired Grant** — User dengan `UserPolicyAbility` expired: `canAccessPanel('guru')` tetap `false`.
6. **Inactive User** — User `is_active = false`: semua panel tetap ditolak.

### Unit Tests

- Test `hasTemporaryAccessToPanel()` dengan berbagai kombinasi panel ID dan policy `permanent_roles`.
- Test `canAccessPanel()` untuk user dengan active grant vs expired grant vs tanpa grant.
- Test `KbmResource::getEloquentQuery()` — user dengan grant mengembalikan query tanpa teacher filter.
- Test `LessonPlanResource::getEloquentQuery()` — identik dengan KBM.
- Test `AnnouncementResource::getEloquentQuery()` — user dengan grant mengembalikan query tanpa `target_role` filter.
- Test `KbmResource::canAccess()` dan `LessonPlanResource::canAccess()` untuk user guru, user dengan grant, dan user tanpa grant.

### Property-Based Tests

- Generate random user roles dan panel IDs: verifikasi bahwa user tanpa active direct ability menghasilkan `canAccessPanel()` identik dengan kode original.
- Generate random `UserPolicyAbility` records (expired/active, inherited/direct): verifikasi bahwa hanya active direct abilities yang mempengaruhi panel access.
- Generate random user states (role, teacher relation, grant status): verifikasi preservation of `getEloquentQuery()` SQL untuk semua non-buggy inputs.

### Integration Tests

- Test full flow: assign grant → login sebagai user → akses panel guru → lihat KBM list (tidak kosong).
- Test full flow: assign grant → expire grant → akses panel guru → ditolak.
- Test bahwa guru normal masih hanya melihat KBM miliknya setelah fix diterapkan.
- Test bahwa siswa_ortu tanpa grant masih tidak bisa masuk panel guru setelah fix.
