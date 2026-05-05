<?php

use App\Filament\Resources\Announcements\AnnouncementResource as PanelAnnouncementResource;
use App\Filament\Student\Resources\Announcements\AnnouncementResource as StudentAnnouncementResource;
use App\Models\Announcement;
use App\Models\User;

test('guru announcement resource only shows announcements for their role', function () {
    $guru = User::factory()->asGuru()->create();

    $visibleAnnouncement = Announcement::factory()->forGuru()->create();
    $hiddenAnnouncement = Announcement::factory()->create([
        'target_role' => ['kepala_sekolah'],
    ]);
    $sharedAnnouncement = Announcement::factory()->create([
        'target_role' => ['guru', 'kepala_sekolah'],
    ]);

    $this->actingAs($guru);

    $announcementIds = PanelAnnouncementResource::getEloquentQuery()
        ->pluck('id')
        ->all();

    expect($announcementIds)
        ->toContain($visibleAnnouncement->id)
        ->toContain($sharedAnnouncement->id)
        ->not->toContain($hiddenAnnouncement->id);
});

test('announcement resource uses title as record title attribute', function () {
    expect(PanelAnnouncementResource::getRecordTitleAttribute())->toBe('title');
});

test('policy allows user to view announcement with matching role', function () {
    $guru = User::factory()->asGuru()->create();
    $announcement = Announcement::factory()->forGuru()->create();

    expect($guru->can('view', $announcement))->toBeTrue();
});

test('super admin can see all announcements from guru resource query', function () {
    $admin = User::factory()->asAdmin()->create();
    $guruAnnouncement = Announcement::factory()->forGuru()->create();
    $kepsekAnnouncement = Announcement::factory()->create([
        'target_role' => ['kepala_sekolah'],
    ]);

    $this->actingAs($admin);

    $announcementIds = PanelAnnouncementResource::getEloquentQuery()
        ->pluck('id')
        ->all();

    expect($announcementIds)
        ->toContain($guruAnnouncement->id)
        ->toContain($kepsekAnnouncement->id);
});

test('student announcement resource only shows announcements for siswa_ortu role', function () {
    $student = User::factory()->asSiswa()->create();

    $visibleAnnouncement = Announcement::factory()->create([
        'target_role' => ['siswa_ortu'],
    ]);
    $sharedAnnouncement = Announcement::factory()->create([
        'target_role' => ['guru', 'siswa_ortu'],
    ]);
    $hiddenAnnouncement = Announcement::factory()->forGuru()->create();

    $this->actingAs($student);

    $announcementIds = StudentAnnouncementResource::getEloquentQuery()
        ->pluck('id')
        ->all();

    expect($announcementIds)
        ->toContain($visibleAnnouncement->id)
        ->toContain($sharedAnnouncement->id)
        ->not->toContain($hiddenAnnouncement->id);
});

test('policy allows siswa_ortu to view announcement index', function () {
    $student = User::factory()->asSiswa()->create();

    expect($student->can('viewAny', Announcement::class))->toBeTrue();
});
