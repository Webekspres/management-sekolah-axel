<?php

use App\Filament\Guru\Resources\Kbms\KbmResource;
use App\Filament\Guru\Resources\LessonPlans\LessonPlanResource;
use App\Filament\Student\Resources\Announcements\AnnouncementResource as StudentAnnouncementResource;
use App\Models\AccessPolicy;
use App\Models\Teacher;
use App\Models\User;
use App\Support\TemporaryAccessManager;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;

function visibilityKbmPolicy(): AccessPolicy
{
    return AccessPolicy::query()->where('code', 'kbm_management')->firstOrFail();
}

function visibilityLessonPlanPolicy(): AccessPolicy
{
    return AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();
}

function visibilityAnnouncementPolicy(): AccessPolicy
{
    return AccessPolicy::query()->where('code', 'announcement_management')->firstOrFail();
}

function grantTemporaryViewAny(User $user, AccessPolicy $policy): void
{
    $admin = User::factory()->asAdmin()->create();

    app(TemporaryAccessManager::class)->assignAbility(
        $user,
        $policy,
        'viewAny',
        $admin,
        now()->addDay(),
    );
}

describe('expected behavior with temporary access', function () {
    test('siswa_ortu dengan grant KBM dapat masuk panel guru', function () {
        $user = User::factory()->asSiswa()->create();
        grantTemporaryViewAny($user, visibilityKbmPolicy());

        $panel = Filament::getPanel('guru');

        expect($user->canAccessPanel($panel))->toBeTrue();
    });

    test('siswa_ortu dengan grant LessonPlan dapat masuk panel guru', function () {
        $user = User::factory()->asSiswa()->create();
        grantTemporaryViewAny($user, visibilityLessonPlanPolicy());

        $panel = Filament::getPanel('guru');

        expect($user->canAccessPanel($panel))->toBeTrue();
    });

    test('siswa_ortu dengan grant KBM tidak mendapat query kosong di KbmResource', function () {
        $user = User::factory()->asSiswa()->create();
        grantTemporaryViewAny($user, visibilityKbmPolicy());

        Filament::setCurrentPanel(Filament::getPanel('guru'));
        actingAs($user);

        $sql = strtolower(KbmResource::getEloquentQuery()->toSql());

        expect($sql)->not->toContain('1 = 0');
    });

    test('siswa_ortu dengan grant LessonPlan tidak mendapat query kosong di LessonPlanResource', function () {
        $user = User::factory()->asSiswa()->create();
        grantTemporaryViewAny($user, visibilityLessonPlanPolicy());

        Filament::setCurrentPanel(Filament::getPanel('guru'));
        actingAs($user);

        $sql = strtolower(LessonPlanResource::getEloquentQuery()->toSql());

        expect($sql)->not->toContain('1 = 0');
    });

    test('siswa_ortu dengan grant Announcement tidak difilter target_role di panel student', function () {
        $user = User::factory()->asSiswa()->create();
        grantTemporaryViewAny($user, visibilityAnnouncementPolicy());

        Filament::setCurrentPanel(Filament::getPanel('student'));
        actingAs($user);

        $sql = strtolower(StudentAnnouncementResource::getEloquentQuery()->toSql());

        expect($sql)->not->toContain('target_role');
    });
});

describe('preservation', function () {
    test('guru tanpa grant tetap dapat masuk panel guru', function () {
        $user = User::factory()->asGuru()->create();

        expect($user->canAccessPanel(Filament::getPanel('guru')))->toBeTrue();
    });

    test('guru tanpa grant query KBM tetap filter teacher_id', function () {
        $user = User::factory()->asGuru()->create();
        Teacher::factory()->create(['user_id' => $user->id]);

        Filament::setCurrentPanel(Filament::getPanel('guru'));
        actingAs($user);

        expect(strtolower(KbmResource::getEloquentQuery()->toSql()))->toContain('teacher_id');
    });

    test('guru tanpa grant query LessonPlan tetap filter teacher_id', function () {
        $user = User::factory()->asGuru()->create();
        Teacher::factory()->create(['user_id' => $user->id]);

        Filament::setCurrentPanel(Filament::getPanel('guru'));
        actingAs($user);

        expect(strtolower(LessonPlanResource::getEloquentQuery()->toSql()))->toContain('teacher_id');
    });

    test('kepala sekolah tetap dapat masuk panel kepsek', function () {
        $user = User::factory()->asKepalaSekolah()->create();

        expect($user->canAccessPanel(Filament::getPanel('kepsek')))->toBeTrue();
    });

    test('siswa_ortu tanpa grant tetap ditolak di panel guru', function () {
        $user = User::factory()->asSiswa()->create();

        expect($user->canAccessPanel(Filament::getPanel('guru')))->toBeFalse();
    });

    test('super admin tetap dapat masuk panel admin', function () {
        $user = User::factory()->asAdmin()->create();

        expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
    });

    test('user tidak aktif tetap ditolak di panel guru', function () {
        $user = User::factory()->inactive()->asGuru()->create();

        expect($user->canAccessPanel(Filament::getPanel('guru')))->toBeFalse();
    });

    test('grant expired tidak membuka panel guru', function () {
        $user = User::factory()->asSiswa()->create();
        $admin = User::factory()->asAdmin()->create();

        app(TemporaryAccessManager::class)->assignAbility(
            $user,
            visibilityKbmPolicy(),
            'viewAny',
            $admin,
            now()->subMinute(),
        );

        expect($user->canAccessPanel(Filament::getPanel('guru')))->toBeFalse();
    });

    test('siswa tanpa grant query Announcement tetap difilter target_role', function () {
        $user = User::factory()->asSiswa()->create();

        Filament::setCurrentPanel(Filament::getPanel('student'));
        actingAs($user);

        expect(strtolower(StudentAnnouncementResource::getEloquentQuery()->toSql()))->toContain('target_role');
    });
});

describe('TemporaryAccessManager panel access', function () {
    test('hasTemporaryAccessToPanel true untuk siswa dengan grant policy panel guru', function () {
        $user = User::factory()->asSiswa()->create();
        grantTemporaryViewAny($user, visibilityKbmPolicy());

        expect(app(TemporaryAccessManager::class)->hasTemporaryAccessToPanel($user, 'guru'))->toBeTrue();
    });

    test('hasTemporaryAccessToPanel false tanpa grant', function () {
        $user = User::factory()->asSiswa()->create();

        expect(app(TemporaryAccessManager::class)->hasTemporaryAccessToPanel($user, 'guru'))->toBeFalse();
    });

    test('hasTemporaryAccessToPanel false untuk grant expired', function () {
        $user = User::factory()->asSiswa()->create();
        $admin = User::factory()->asAdmin()->create();

        app(TemporaryAccessManager::class)->assignAbility(
            $user,
            visibilityKbmPolicy(),
            'viewAny',
            $admin,
            now()->subMinute(),
        );

        expect(app(TemporaryAccessManager::class)->hasTemporaryAccessToPanel($user, 'guru'))->toBeFalse();
    });
});
