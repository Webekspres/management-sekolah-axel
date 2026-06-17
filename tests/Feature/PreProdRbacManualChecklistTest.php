<?php

use App\Enums\UserRole;
use App\Filament\Actions\ImportPersonaliaAction;
use App\Filament\Imports\StudentImporter;
use App\Models\AcademicYear;
use App\Models\AccessPolicy;
use App\Models\Rapor;
use App\Models\Student;
use App\Models\User;
use App\Support\TemporaryAccessManager;
use Database\Seeders\AccessPolicySeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(AccessPolicySeeder::class);
    seedPreProdStagingUsers();
});

function seedPreProdStagingUsers(): void
{
    $accounts = [
        ['name' => 'Super Admin', 'email' => 'admin@hstkb.sch.id', 'role' => 'super_admin', 'password' => 'adminportal2026'],
        ['name' => 'Kepala Sekolah', 'email' => 'kepsek@hstkb.sch.id', 'role' => 'kepala_sekolah', 'password' => 'kepsekportal2026'],
        ['name' => 'Guru Demo', 'email' => 'guru@hstkb.sch.id', 'role' => 'guru', 'password' => 'guruportal2026'],
        ['name' => 'Siswa Demo', 'email' => 'siswa@hstkb.sch.id', 'role' => 'siswa_ortu', 'password' => 'siswaportal2026'],
    ];

    foreach ($accounts as $account) {
        User::query()->updateOrCreate(
            ['email' => $account['email']],
            [
                'name' => $account['name'],
                'role' => $account['role'],
                'password' => Hash::make($account['password']),
                'gender' => 'L',
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );
    }
}

/**
 * Pre-prod RBAC manual checklist — mirrors staging @hstkb.sch.id accounts.
 */
function preProdUser(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

dataset('pre prod accounts', [
    'admin' => ['admin@hstkb.sch.id', 'adminportal2026', '/admin', UserRole::SuperAdmin],
    'kepsek' => ['kepsek@hstkb.sch.id', 'kepsekportal2026', '/kepsek', UserRole::KepalaSekolah],
    'guru' => ['guru@hstkb.sch.id', 'guruportal2026', '/guru', UserRole::Guru],
    'siswa' => ['siswa@hstkb.sch.id', 'siswaportal2026', '/student', UserRole::SiswaOrtu],
]);

test('staging credentials authenticate successfully', function (string $email, string $password): void {
    expect(Auth::attempt(['email' => $email, 'password' => $password]))->toBeTrue();

    Auth::logout();
})->with('pre prod accounts');

test('root redirect sends each role to the correct panel', function (
    string $email,
    string $password,
    string $expectedPath,
    UserRole $expectedRole,
): void {
    $user = preProdUser($email);

    expect($user->userRole())->toBe($expectedRole);

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect($expectedPath);
})->with('pre prod accounts');

test('siswa cannot access admin staff after login', function (): void {
    $siswa = preProdUser('siswa@hstkb.sch.id');

    $this->actingAs($siswa)
        ->get('/admin/data-personalia/staff')
        ->assertForbidden();
});

test('guru cannot access admin temporary access page', function (): void {
    $guru = preProdUser('guru@hstkb.sch.id');

    $this->actingAs($guru)
        ->get('/admin/akses-sementara')
        ->assertForbidden();
});

test('siswa with temporary grant can access guru panel', function (): void {
    $siswa = preProdUser('siswa@hstkb.sch.id');
    $admin = preProdUser('admin@hstkb.sch.id');
    $policy = AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();

    app(TemporaryAccessManager::class)->assignAbility(
        $siswa,
        $policy,
        'viewAny',
        $admin,
        now()->addDay(),
    );

    expect($siswa->fresh()->canAccessPanel(Filament::getPanel('guru')))->toBeTrue();

    $this->actingAs($siswa)
        ->get('/guru/lesson-plans')
        ->assertOk();

    app(TemporaryAccessManager::class)->revokeAbility($siswa, $policy, 'viewAny');
});

test('siswa can download approved rapor but not draft rapor', function (): void {
    Storage::fake('local');

    $siswa = preProdUser('siswa@hstkb.sch.id');
    $student = Student::factory()->create(['user_id' => $siswa->id]);
    $academicYear = AcademicYear::factory()->active()->create();

    $approved = Rapor::factory()->approved()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'file_path' => 'rapors/preprod-approved.pdf',
    ]);
    Storage::put($approved->file_path, 'pdf');

    $draft = Rapor::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'status' => 'DRAFT',
        'file_path' => 'rapors/preprod-draft.pdf',
    ]);
    Storage::put($draft->file_path, 'pdf');

    $this->actingAs($siswa)
        ->get(route('rapor.download', $approved))
        ->assertOk();

    $this->actingAs($siswa)
        ->get(route('rapor.download', $draft))
        ->assertForbidden();
});

test('admin can import personalia but guru cannot', function (): void {
    $admin = preProdUser('admin@hstkb.sch.id');
    $guru = preProdUser('guru@hstkb.sch.id');

    $this->actingAs($admin);
    expect(
        ImportPersonaliaAction::make('importStudents')->importer(StudentImporter::class)->isAuthorized()
    )->toBeTrue();

    $this->actingAs($guru);
    expect(
        ImportPersonaliaAction::make('importStudents')->importer(StudentImporter::class)->isAuthorized()
    )->toBeFalse();
});
