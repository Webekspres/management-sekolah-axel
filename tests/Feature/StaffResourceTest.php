<?php

use App\Filament\Clusters\DataPersonalia\Resources\Staff\Pages\CreateStaff;
use App\Filament\Clusters\DataPersonalia\Resources\Staff\Pages\EditStaff;
use App\Filament\Clusters\DataPersonalia\Resources\Staff\Pages\ListStaff;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

test('admin can access staff list page', function () {
    $user = User::factory()->asAdmin()->create();

    $this->actingAs($user)
        ->get('/admin/data-personalia/staff')
        ->assertOk();
});

test('kepala sekolah can access staff list page', function () {
    $user = User::factory()->asKepalaSekolah()->create();

    $this->actingAs($user)
        ->get('/admin/data-personalia/staff')
        ->assertOk();
});

test('guru cannot access staff list page', function () {
    $user = User::factory()->asGuru()->create();

    $this->actingAs($user)
        ->get('/admin/data-personalia/staff')
        ->assertForbidden();
});

test('staff table only shows admin and kepsek users', function () {
    $admin = User::factory()->asAdmin()->create();
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $guru = User::factory()->asGuru()->create();

    $this->actingAs($admin);

    livewire(ListStaff::class)
        ->assertCanSeeTableRecords([$admin, $kepsek])
        ->assertCanNotSeeTableRecords([$guru]);
});

test('admin can create a new kepala sekolah user', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    livewire(CreateStaff::class)
        ->fillForm([
            'name' => 'Kepsek Baru',
            'email' => 'kepsek-baru@hstkb.sch.id',
            'password' => 'password123',
            'role' => 'kepala_sekolah',
            'gender' => 'L',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'name' => 'Kepsek Baru',
        'email' => 'kepsek-baru@hstkb.sch.id',
        'role' => 'kepala_sekolah',
    ]);
});

test('admin can create a new super admin user', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    livewire(CreateStaff::class)
        ->fillForm([
            'name' => 'Admin Baru',
            'email' => 'admin-baru@hstkb.sch.id',
            'password' => 'password123',
            'role' => 'super_admin',
            'gender' => 'P',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'name' => 'Admin Baru',
        'email' => 'admin-baru@hstkb.sch.id',
        'role' => 'super_admin',
    ]);
});

test('admin can edit a staff user', function () {
    $admin = User::factory()->asAdmin()->create();
    $kepsek = User::factory()->asKepalaSekolah()->create();

    $this->actingAs($admin);

    livewire(EditStaff::class, ['record' => $kepsek->id])
        ->fillForm(['name' => 'Kepsek Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'id' => $kepsek->id,
        'name' => 'Kepsek Updated',
    ]);
});

test('create staff validates required fields', function () {
    $admin = User::factory()->asAdmin()->create();

    $this->actingAs($admin);

    livewire(CreateStaff::class)
        ->fillForm([
            'name' => null,
            'email' => null,
            'password' => null,
            'role' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'role' => 'required',
        ]);
});
