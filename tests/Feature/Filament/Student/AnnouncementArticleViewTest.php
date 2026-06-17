<?php

use App\Filament\Student\Resources\Announcements\AnnouncementResource;
use App\Filament\Student\Resources\Announcements\Pages\ViewAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('student announcement detail renders rich content with editor styling', function () {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create([
        'title' => 'Pengumuman Ujian',
        'content' => '<p>Catatan penting.</p><pre><code>if true: pass</code></pre>',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('student'));

    $this->actingAs($siswa);

    Livewire::test(ViewAnnouncement::class, ['record' => $announcement->id])
        ->assertOk()
        ->assertSee('Pengumuman Ujian')
        ->assertSee('Catatan penting.')
        ->assertSeeHtml('fi-fo-rich-editor')
        ->assertSeeHtml('fi-prose')
        ->assertSeeHtml('<pre')
        ->assertSeeHtml('if true: pass')
        ->assertActionVisible('backToAnnouncements');
});

test('student announcement resource uses title as record title attribute', function () {
    expect(AnnouncementResource::getRecordTitleAttribute())->toBe('title');
});

test('student announcement view page shows indonesian breadcrumb label', function () {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forSiswa()->create([
        'title' => 'Libur Semester',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('student'));

    $this->actingAs($siswa);

    $component = Livewire::test(ViewAnnouncement::class, ['record' => $announcement->id]);

    expect($component->instance()->getBreadcrumb())->toBe('Detail')
        ->and($component->instance()->getTitle())->toBe('Pengumuman');
});
