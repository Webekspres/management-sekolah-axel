# Design Document

## Feature: temporary-access-enhancements

---

## Overview

Dokumen ini mendeskripsikan desain teknis untuk tiga peningkatan pada sistem manajemen akses sementara:

1. Mengganti `window.confirm` dengan Filament confirmation modal pada halaman `TemporaryAccessManagement`.
2. Menambahkan bulk action "Cabut Akses Terpilih" pada tabel `ActiveTemporaryAccessList`.
3. Membuat model `TemporaryAccessLog`, migration, dan halaman Filament "Log Akses" yang view-only.

---

## Architecture

### Komponen yang Dimodifikasi

| File | Perubahan |
|---|---|
| `resources/views/filament/pages/temporary-access-management.blade.php` | Hapus `x-data`, `x-on:click`, dan `window.confirm`; tombol Simpan menjadi trigger Filament Action |
| `app/Filament/Pages/TemporaryAccessManagement.php` | Tambah header Action `save` dengan `requiresConfirmation()` |
| `app/Filament/Pages/ActiveTemporaryAccessList.php` | Tambah `BulkAction` untuk cabut akses massal |
| `app/Support/TemporaryAccessManager.php` | Tambah method `logGrant()` dan `logRevoke()` |

### Komponen Baru

| File | Deskripsi |
|---|---|
| `database/migrations/xxxx_create_temporary_access_logs_table.php` | Migration untuk tabel `temporary_access_logs` |
| `app/Models/TemporaryAccessLog.php` | Model Eloquent untuk log akses sementara |
| `app/Filament/Pages/TemporaryAccessLogList.php` | Halaman Filament view-only untuk log akses |
| `resources/views/filament/pages/temporary-access-log-list.blade.php` | Blade view untuk halaman log |

---

## Detailed Design

### 1. Ganti Browser Alert dengan Filament Modal

**Masalah saat ini:** Blade view menggunakan Alpine.js `x-on:click="if (confirm(...)) { $wire.submit() }"` yang memunculkan dialog bawaan browser.

**Solusi:** Pindahkan tombol Simpan menjadi Filament `Action` di `getHeaderActions()` dengan `requiresConfirmation()`. Blade view hanya perlu merender form tanpa tombol submit manual.

**Perubahan pada `TemporaryAccessManagement.php`:**

```php
protected function getHeaderActions(): array
{
    return [
        Action::make('save')
            ->label('Simpan')
            ->icon(Heroicon::OutlinedCheck)
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Pemberian Akses')
            ->modalDescription('Simpan pemberian akses sementara ini? Akses akan aktif sesuai durasi yang dipilih.')
            ->modalSubmitActionLabel('Ya, Simpan')
            ->disabled(fn (): bool => $this->isSaveDisabled())
            ->action(fn () => $this->submit()),
    ];
}
```

**Perubahan pada blade view:**

```blade
<x-filament-panels::page>
    <form class="space-y-6" wire:submit.prevent>
        {{ $this->form }}
    </form>
</x-filament-panels::page>
```

Form tidak lagi memiliki `wire:submit.prevent="submit"` karena submit dipicu dari Action. Tombol Simpan dihapus dari blade.

---

### 2. Bulk Action Cabut Akses

**Perubahan pada `ActiveTemporaryAccessList.php`:**

Tambahkan `toolbarActions()` dengan `BulkAction`:

```php
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

->toolbarActions([
    BulkAction::make('revokeSelected')
        ->label('Cabut Akses Terpilih')
        ->icon(Heroicon::OutlinedXCircle)
        ->color('danger')
        ->requiresConfirmation()
        ->modalHeading('Cabut Akses Terpilih')
        ->modalDescription(fn (Collection $records): string => "Cabut {$records->count()} akses yang dipilih? Aksi ini tidak dapat dibatalkan.")
        ->modalSubmitActionLabel('Ya, Cabut Semua')
        ->action(fn (Collection $records) => $this->revokeSelectedAbilities($records))
        ->deselectRecordsAfterCompletion(),
])
```

**Method `revokeSelectedAbilities()`:**

```php
private function revokeSelectedAbilities(Collection $abilities): void
{
    $revokedCount = 0;
    $failedCount = 0;

    foreach ($abilities as $ability) {
        try {
            $this->revokeAbility($ability);
            $revokedCount++;
        } catch (\Throwable $e) {
            $failedCount++;
        }
    }

    Notification::make()
        ->title("{$revokedCount} akses berhasil dicabut")
        ->success()
        ->send();

    if ($failedCount > 0) {
        Notification::make()
            ->title("{$failedCount} akses gagal dicabut")
            ->warning()
            ->send();
    }
}
```

Method `revokeAbility()` yang sudah ada tetap digunakan untuk logika per-record (termasuk cleanup `TemporaryPolicyGrant`).

---

### 3. Model dan Migration TemporaryAccessLog

#### Schema Tabel `temporary_access_logs`

```php
Schema::create('temporary_access_logs', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUlid('access_policy_id')->constrained('access_policies')->cascadeOnDelete();
    $table->string('ability');
    $table->foreignUlid('level_id')->nullable()->constrained('levels')->nullOnDelete();
    $table->foreignUlid('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('granted_at')->useCurrent();
    $table->timestamp('expires_at');
    $table->timestamp('revoked_at')->nullable();
    $table->foreignUlid('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
});
```

Tidak ada `updated_at` — log bersifat append-only kecuali untuk kolom `revoked_at` dan `revoked_by_user_id`.

#### Model `TemporaryAccessLog`

```php
class TemporaryAccessLog extends Model
{
    use HasUlid;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'access_policy_id', 'ability', 'level_id',
        'granted_by_user_id', 'granted_at', 'expires_at',
        'revoked_at', 'revoked_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { ... }
    public function accessPolicy(): BelongsTo { ... }
    public function level(): BelongsTo { ... }
    public function grantedBy(): BelongsTo { ... }
    public function revokedBy(): BelongsTo { ... }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    public function scopeRevoked(Builder $query): Builder
    {
        return $query->whereNotNull('revoked_at');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where('expires_at', '<=', now());
    }
}
```

#### Integrasi dengan TemporaryAccessManager

Method `assignAbility()` dimodifikasi untuk membuat log entry setelah `updateOrCreate` berhasil:

```php
// Setelah UserPolicyAbility::updateOrCreate(...)
TemporaryAccessLog::create([
    'user_id' => $user->id,
    'access_policy_id' => $policy->id,
    'ability' => $ability,
    'level_id' => $levelId,
    'granted_by_user_id' => $grantedBy->id,
    'granted_at' => now(),
    'expires_at' => $expiresAt,
]);
```

Method `revokeAbility()` di `ActiveTemporaryAccessList` dimodifikasi untuk mengupdate log:

```php
// Setelah $ability->delete()
TemporaryAccessLog::query()
    ->where('user_id', $ability->user_id)
    ->where('access_policy_id', $ability->access_policy_id)
    ->where('ability', $ability->ability)
    ->whereNull('revoked_at')
    ->update([
        'revoked_at' => now(),
        'revoked_by_user_id' => auth()->id(),
    ]);
```

---

### 4. Halaman Log Akses (`TemporaryAccessLogList`)

**Navigasi:** Group "Manajemen Akses", sort 3, slug `log-akses`.

**Tabel:**

```php
->query(
    TemporaryAccessLog::query()
        ->with(['user', 'accessPolicy', 'level', 'grantedBy', 'revokedBy'])
        ->latest('granted_at')
)
->columns([
    TextColumn::make('user.name')->label('User')->searchable()->description(...),
    TextColumn::make('accessPolicy.name')->label('Policy')->searchable()->sortable(),
    TextColumn::make('ability')->label('Ability')->badge()->color(...),
    TextColumn::make('level.name')->label('Jenjang')->placeholder('Semua jenjang')->badge(),
    TextColumn::make('granted_at')->label('Diberikan Pada')->dateTime()->sortable(),
    TextColumn::make('grantedBy.name')->label('Diberikan Oleh')->searchable(),
    TextColumn::make('expires_at')->label('Berakhir')->dateTime()->sortable(),
    TextColumn::make('revoked_at')->label('Dicabut Pada')->dateTime()->sortable()->placeholder('-'),
    TextColumn::make('revokedBy.name')->label('Dicabut Oleh')->placeholder('-'),
])
->filters([
    SelectFilter::make('user_id')->label('User')->relationship('user', 'name')->searchable()->preload(),
    SelectFilter::make('access_policy_id')->label('Policy')->relationship('accessPolicy', 'name')->searchable()->preload(),
    SelectFilter::make('ability')->label('Ability')->options([...]),
    SelectFilter::make('status')->label('Status')->options([
        'active' => 'Aktif',
        'revoked' => 'Dicabut',
        'expired' => 'Kedaluwarsa',
    ])->query(fn (Builder $query, array $data) => match ($data['value']) {
        'active' => $query->active(),
        'revoked' => $query->revoked(),
        'expired' => $query->expired(),
        default => $query,
    }),
])
->defaultSort('granted_at', 'desc')
```

Tidak ada `recordActions()` — halaman ini view-only.

---

## Correctness Properties

### 1.3 Klik konfirmasi mengeksekusi submit()
- **Type:** Example
- **Test:** Panggil `callAction('save')` pada Livewire component `TemporaryAccessManagement`, verifikasi `assertNotified()` dan `assertDatabaseHas(UserPolicyAbility::class, [...])`.

### 1.4 Klik batal tidak mengubah data
- **Type:** Example
- **Test:** Panggil `callAction('save', shouldConfirm: false)` atau `assertActionHalted`, verifikasi `assertDatabaseMissing`.

### 2.3 Bulk action mencabut semua UserPolicyAbility yang dipilih
- **Type:** Example
- **Test:** Buat 3 `UserPolicyAbility`, pilih semua, panggil bulk action, verifikasi semua terhapus dari database.

### 2.4 TemporaryPolicyGrant dihapus jika tidak ada ability aktif tersisa
- **Type:** Example (Invariant)
- **Test:** Setelah bulk revoke semua abilities untuk satu user+policy, verifikasi `assertDatabaseMissing(TemporaryPolicyGrant::class, ['user_id' => ..., 'access_policy_id' => ...])`.

### 3.2 Log dibuat saat akses diberikan
- **Type:** Example
- **Test:** Submit form `TemporaryAccessManagement` dengan 2 user × 2 abilities, verifikasi 4 entri `TemporaryAccessLog` dibuat di database.

### 3.3 Log diperbarui saat akses dicabut
- **Type:** Example (Invariant)
- **Test:** Setelah revoke, verifikasi `revoked_at != null` dan `revoked_by_user_id == auth()->id()` pada log yang sesuai.

### 3.4 Halaman Log Akses hanya untuk super_admin
- **Type:** Example
- **Test:** Akses halaman sebagai user non-admin, verifikasi `assertForbidden()`.

### 3.6 Searchable berdasarkan nama user, policy, pemberi akses
- **Type:** Example
- **Test:** Gunakan `searchTable()` dan verifikasi `assertCanSeeTableRecords` / `assertCanNotSeeTableRecords`.

### 3.8 Filter berdasarkan status
- **Type:** Example
- **Test:** Buat log dengan status berbeda (aktif, dicabut, kedaluwarsa), filter per status, verifikasi hanya record yang sesuai tampil.
