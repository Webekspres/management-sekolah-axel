<?php

use App\Filament\Pages\ActivityLogPage;
use App\Models\User;
use Filament\Facades\Filament;

// ---------------------------------------------------------------------------
// 1. HTTP Access Control
// ---------------------------------------------------------------------------

test('super_admin dapat mengakses halaman activity log', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.activity-log'))
        ->assertSuccessful();
});

test('guru mendapat 403 saat mengakses halaman activity log', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $guru = User::factory()->asGuru()->create();

    $this->actingAs($guru)
        ->get(route('filament.admin.pages.activity-log'))
        ->assertForbidden();
});

test('kepala_sekolah mendapat 403 saat mengakses halaman activity log', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $kepsek = User::factory()->asKepalaSekolah()->create();

    $this->actingAs($kepsek)
        ->get(route('filament.admin.pages.activity-log'))
        ->assertForbidden();
});

test('siswa_ortu mendapat 403 saat mengakses halaman activity log', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $siswa = User::factory()->asSiswa()->create();

    $this->actingAs($siswa)
        ->get(route('filament.admin.pages.activity-log'))
        ->assertForbidden();
});

test('unauthenticated user diredirect ke login', function () {
    $this->get(route('filament.admin.pages.activity-log'))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// 2. canAccess()
// ---------------------------------------------------------------------------

test('canAccess returns true untuk super_admin', function () {
    $admin = User::factory()->asAdmin()->create();
    $this->actingAs($admin);

    expect(ActivityLogPage::canAccess())->toBeTrue();
});

dataset('non_admin_roles', [
    'guru' => ['guru'],
    'kepala_sekolah' => ['kepala_sekolah'],
    'siswa_ortu' => ['siswa_ortu'],
]);

test('canAccess returns false untuk role non-super_admin', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $this->actingAs($user);

    expect(ActivityLogPage::canAccess())->toBeFalse();
})->with('non_admin_roles');
