<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\LearningAchievement;
use App\Models\Rapor;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Services\RaporService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('debug rapor pdf text extraction', function (): void {
    Storage::fake('local');

    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create(['semester' => 'Ganjil', 'name' => '2025/2026']);
    $rapor = Rapor::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'program' => 'Reguler',
        'sumber_pembelajaran' => 'Buku teks',
        'status' => 'DRAFT',
    ]);

    $subject = Subject::factory()->create(['name' => 'Matematika']);
    Schedule::factory()->create(['subject_id' => $subject->id]);

    foreach (['PH1', 'PH2', 'ATS', 'SAS', 'RAPOR'] as $type) {
        Grade::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'grade_type' => $type,
            'score' => 85.0,
        ]);
    }

    LearningAchievement::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'topic_coverage' => 'Aljabar',
        'notes' => 'Berkembang',
    ]);

    Setting::factory()->create(['key' => 'titimangsa', 'value' => '2025-06-20']);

    $filePath = app(RaporService::class)->generatePdf($rapor);
    $pdfPath = Storage::path($filePath);
    file_put_contents(base_path('storage/app/debug-rapor-test.pdf'), Storage::get($filePath));

    expect(file_exists($pdfPath))->toBeTrue();

    $textFile = base_path('storage/app/debug-rapor-test.txt');
    exec('pdftotext '.escapeshellarg($pdfPath).' '.escapeshellarg($textFile).' 2>&1', $output, $code);

    if ($code === 0 && file_exists($textFile)) {
        $text = file_get_contents($textFile);
        expect($text)->toContain('2025/2026');
        expect($text)->not->toMatch('/T\s*P\s*e\s*a\s*h/');
        expect($text)->not->toContain('TPeahmubnelaja');
    }
})->skip(! command_exists('pdftotext'), 'pdftotext not available');

function command_exists(string $command): bool
{
    $where = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';

    exec($where.' '.escapeshellarg($command), $output, $code);

    return $code === 0;
}
