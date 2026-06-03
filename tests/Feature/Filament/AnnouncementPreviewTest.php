<?php

use App\Filament\Resources\Announcements\Actions\PreviewAnnouncementAction;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use App\Support\AnnouncementRichContentPreview;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

test('announcement rich content preview renders code blocks with rich editor styling', function () {
    $html = '<pre><code>echo "hello";</code></pre>';

    $preview = AnnouncementRichContentPreview::make($html, 'Judul Uji')->toHtml();

    expect($preview)
        ->toContain('fi-fo-rich-editor')
        ->toContain('fi-fo-rich-editor-content')
        ->toContain('fi-prose')
        ->toContain('tiptap')
        ->toContain('<pre')
        ->toContain('<code')
        ->toContain('echo &#34;hello&#34;;')
        ->toContain('Judul Uji');
});

test('guru can open announcement preview on create page with current form content', function () {
    $guru = User::factory()->asGuru()->create();

    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->actingAs($guru);

    $content = '<p>Teks <strong>tebal</strong></p><pre><code>$x = 1;</code></pre>';

    Livewire::test(CreateAnnouncement::class)
        ->fillForm([
            'title' => 'Pengumuman Uji',
            'content' => $content,
            'target_role' => ['guru'],
        ])
        ->callAction(TestAction::make('previewAnnouncement')->schemaComponent('form-actions', 'content'))
        ->assertOk();
});

test('creating announcement stores created_by as current user', function () {
    $guru = User::factory()->asGuru()->create();

    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->actingAs($guru);

    Livewire::test(CreateAnnouncement::class)
        ->fillForm([
            'title' => 'Pengumuman Baru',
            'content' => '<p>Isi pengumuman</p>',
            'target_role' => ['guru'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Announcement::class, [
        'title' => 'Pengumuman Baru',
        'created_by' => $guru->id,
    ]);
});

test('creator can open announcement preview on edit page', function () {
    $guru = User::factory()->asGuru()->create();
    $announcement = Announcement::factory()->forGuru()->create([
        'created_by' => $guru->id,
        'content' => '<p>Konten <strong>edit</strong></p>',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->actingAs($guru);

    Livewire::test(EditAnnouncement::class, ['record' => $announcement->id])
        ->assertActionVisible('previewAnnouncement')
        ->callAction('previewAnnouncement')
        ->assertOk();
});

test('user can preview legacy announcement without creator on edit page', function () {
    $guru = User::factory()->asGuru()->create();
    $announcement = Announcement::factory()->forGuru()->create([
        'created_by' => null,
        'content' => '<p>Pengumuman lama</p>',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->actingAs($guru);

    Livewire::test(EditAnnouncement::class, ['record' => $announcement->id])
        ->assertActionVisible('previewAnnouncement')
        ->callAction('previewAnnouncement')
        ->assertOk();
});

test('non creator cannot preview announcement on edit page', function () {
    $creator = User::factory()->asGuru()->create();
    $otherGuru = User::factory()->asGuru()->create();
    $announcement = Announcement::factory()->forGuru()->create([
        'created_by' => $creator->id,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->actingAs($otherGuru);

    expect(PreviewAnnouncementAction::canPreview(
        Livewire::test(EditAnnouncement::class, ['record' => $announcement->id])->instance(),
    ))->toBeFalse();
});

test('siswa cannot access announcement create page', function () {
    $siswa = User::factory()->asSiswa()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($siswa)
        ->get('/admin/announcements/create')
        ->assertForbidden();
});
