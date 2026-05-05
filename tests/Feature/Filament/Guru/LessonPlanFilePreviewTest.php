<?php

use App\Filament\Guru\Resources\LessonPlans\Pages\CreateLessonPlan;
use App\Filament\Guru\Resources\LessonPlans\Pages\ListLessonPlans;
use App\Models\LessonPlan;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Support\PublicStorageFilePreview;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->guruUser = User::factory()->asGuru()->create();
    $this->teacher = Teacher::factory()->create(['user_id' => $this->guruUser->id]);

    $this->actingAs($this->guruUser);
});

test('modal detail RPP guru bisa dibuka setelah dokumen diunggah', function () {
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => 'DRAFT',
        'file_path' => 'lesson-plans/rpp-guru-preview.pdf',
    ]);

    Livewire::test(ListLessonPlans::class)
        ->mountTableAction('detail', $lessonPlan->getKey())
        ->assertHasNoTableActionErrors();
});

test('renderer preview file public storage menampilkan tautan pratinjau PDF di tab baru tanpa iframe', function () {
    $preview = PublicStorageFilePreview::render('lesson-plans/rpp-detail-preview.pdf');

    expect($preview)
        ->toBeInstanceOf(HtmlString::class)
        ->and($preview->toHtml())->toContain('/storage/lesson-plans/rpp-detail-preview.pdf')
        ->and($preview->toHtml())->toContain('Buka file: rpp-detail-preview.pdf')
        ->and($preview->toHtml())->toContain('Pratinjau PDF di tab baru')
        ->and($preview->toHtml())->toContain('target="_blank"')
        ->and($preview->toHtml())->not->toContain('<iframe');
});

test('renderer preview file public storage non-PDF hanya menampilkan link', function () {
    $preview = PublicStorageFilePreview::render('lesson-plans/rpp-detail-preview.docx');

    expect($preview)
        ->toBeInstanceOf(HtmlString::class)
        ->and($preview->toHtml())->toContain('/storage/lesson-plans/rpp-detail-preview.docx')
        ->and($preview->toHtml())->toContain('Buka file: rpp-detail-preview.docx')
        ->and($preview->toHtml())->not->toContain('<iframe');
});

test('create RPP guru menyimpan nama file asli user', function () {
    Storage::fake('public');

    $subject = Subject::factory()->create();
    $schoolClass = SchoolClass::factory()->create();
    $uploadedFile = UploadedFile::fake()->create('RPP-Matematika-Kelas-9.pdf', 256, 'application/pdf');

    Livewire::test(CreateLessonPlan::class)
        ->fillForm([
            'subject_id' => $subject->id,
            'class_id' => $schoolClass->id,
            'topic' => 'Topik Bab Pecahan',
            'implementation_date' => now()->addDay()->format('Y-m-d'),
            'file_path' => $uploadedFile,
        ])
        ->call('create')
        ->assertHasNoErrors();

    $lessonPlan = LessonPlan::query()->latest('id')->first();

    expect($lessonPlan)->not->toBeNull()
        ->and($lessonPlan?->file_path)->toEndWith('RPP-Matematika-Kelas-9.pdf');

    Storage::disk('public')->assertExists($lessonPlan->file_path);
});
