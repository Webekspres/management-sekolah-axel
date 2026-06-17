<?php

use App\Models\AccessPolicy;
use App\Models\User;
use App\Support\TemporaryAccessManager;

test('admin can open temporary access management and grant access', function (): void {
    $admin = User::factory()->asAdmin()->create();
    $target = User::factory()->asSiswa()->create();
    $policy = AccessPolicy::query()->where('code', 'lesson_plan_management')->firstOrFail();

    $page = visitAuthenticated($admin, '/admin/akses-sementara');
    $page->assertSee('Akses Sementara')
        ->assertNoJavaScriptErrors();

    visitAuthenticated($admin, '/admin/akses-aktif')
        ->assertSee('Akses Aktif')
        ->assertNoSmoke();

    app(TemporaryAccessManager::class)->assignAbility(
        $target,
        $policy,
        'viewAny',
        $admin,
        now()->addWeek(),
    );

    visitAuthenticated($target, '/guru/lesson-plans')
        ->assertNoSmoke();
});

test('kepsek key pages load without javascript errors', function (): void {
    $user = User::factory()->asKepalaSekolah()->create();

    smokeAuthenticatedPages($user, [
        '/kepsek',
        '/kepsek/lesson-plans',
        '/kepsek/kbms',
        '/kepsek/rapors',
        '/kepsek/attendances',
    ]);
});
