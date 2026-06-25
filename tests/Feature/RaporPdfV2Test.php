<?php

use App\Models\AcademicYear;
use App\Models\AttitudeScore;
use App\Models\Grade;
use App\Models\KnowledgeSkillScore;
use App\Models\LearningAchievement;
use App\Models\PersonalityScore;
use App\Models\Rapor;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Services\RaporService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Sub-task 11.1: Feature test (mandatory)
// ─────────────────────────────────────────────────────────────────────────────

test('generates PDF using v2 template without errors', function (): void {
    Storage::fake('local');

    // Create base models
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create(['semester' => 'Ganjil', 'name' => '2025/2026']);
    $rapor = Rapor::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'status' => 'DRAFT',
        'program' => 'Reguler',
        'sumber_pembelajaran' => 'Buku teks',
    ]);

    // Create subjects and grades
    $subject1 = Subject::factory()->create(['name' => 'Matematika']);
    $subject2 = Subject::factory()->create(['name' => 'Bahasa Indonesia']);

    // Create schedules for teacher names
    Schedule::factory()->create(['subject_id' => $subject1->id]);
    Schedule::factory()->create(['subject_id' => $subject2->id]);

    // Create grades for subject 1
    foreach (['PH1', 'PH2', 'PH3', 'PH4'] as $type) {
        Grade::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject1->id,
            'academic_year_id' => $academicYear->id,
            'grade_type' => $type,
            'score' => fake()->randomFloat(2, 70, 95),
        ]);
    }

    foreach (['TUGAS1', 'TUGAS2', 'TUGAS3', 'TUGAS4'] as $type) {
        Grade::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject1->id,
            'academic_year_id' => $academicYear->id,
            'grade_type' => $type,
            'score' => fake()->randomFloat(2, 70, 95),
        ]);
    }

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject1->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'ATS',
        'score' => 85.0,
    ]);

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject1->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'SAS',
        'score' => 90.0,
    ]);

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject1->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'RAPOR',
        'score' => 87.5,
    ]);

    // Create grades for subject 2
    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject2->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'PH1',
        'score' => 80.0,
    ]);

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject2->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'ATS',
        'score' => 82.0,
    ]);

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject2->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'SAS',
        'score' => 88.0,
    ]);

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject2->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'RAPOR',
        'score' => 83.5,
    ]);

    // Create attitude scores
    AttitudeScore::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'aspect' => 'Spiritual',
        'score' => 85.0,
        'description' => 'Baik dalam beribadah',
    ]);

    AttitudeScore::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'aspect' => 'Sosial',
        'score' => 88.0,
        'description' => 'Baik dalam bergaul',
    ]);

    // Create knowledge & skill scores
    KnowledgeSkillScore::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject1->id,
        'academic_year_id' => $academicYear->id,
        'knowledge_score' => 87.0,
        'knowledge_predicate' => 'A',
        'knowledge_description' => 'Sangat baik dalam memahami konsep',
        'skill_score' => 85.0,
        'skill_predicate' => 'B',
        'skill_description' => 'Baik dalam praktik',
    ]);

    KnowledgeSkillScore::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject2->id,
        'academic_year_id' => $academicYear->id,
        'knowledge_score' => 83.0,
        'knowledge_predicate' => 'B',
        'knowledge_description' => 'Baik dalam memahami materi',
        'skill_score' => 86.0,
        'skill_predicate' => 'A',
        'skill_description' => 'Sangat baik dalam menulis',
    ]);

    // Create learning achievements
    LearningAchievement::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject1->id,
        'academic_year_id' => $academicYear->id,
        'topic_coverage' => 'Aljabar, Geometri, Statistika',
        'notes' => 'Menguasai materi dengan baik',
    ]);

    LearningAchievement::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject2->id,
        'academic_year_id' => $academicYear->id,
        'topic_coverage' => 'Teks narasi, deskripsi, argumentasi',
        'notes' => 'Perlu meningkatkan kemampuan menulis',
    ]);

    // Create personality score
    PersonalityScore::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'kedisiplinan' => 'A',
        'kerapihan' => 'B',
        'kerajinan' => 'A',
        'kesopanan' => 'A',
    ]);

    // Create titimangsa setting (value must be a parseable date string)
    Setting::factory()->create([
        'key' => 'titimangsa',
        'value' => '2025-06-20',
    ]);

    // Generate PDF
    $service = app(RaporService::class);
    $filePath = $service->generatePdf($rapor);

    // Assertions
    expect($filePath)->toBeString();
    expect(Storage::exists($filePath))->toBeTrue();
    expect(Storage::size($filePath))->toBeGreaterThan(0);

    // Verify rapor record was updated
    $rapor->refresh();
    expect($rapor->file_path)->toBe($filePath);
    expect($rapor->generated_at)->not->toBeNull();

    $pdfPath = Storage::path($filePath);
    $textFile = sys_get_temp_dir().'/rapor-pdf-v2-'.uniqid().'.txt';
    exec('pdftotext '.escapeshellarg($pdfPath).' '.escapeshellarg($textFile).' 2>&1', $pdftotextOutput, $pdftotextCode);

    if ($pdftotextCode === 0 && file_exists($textFile)) {
        $pdfText = file_get_contents($textFile);
        @unlink($textFile);

        expect($pdfText)->toContain('2025/2026');
        expect($pdfText)->toContain('Reguler');
        expect($pdfText)->not->toContain('TPeahmubnelaja');

        $pdfBinary = Storage::get($filePath);
        expect($pdfBinary)->not->toContain('DejaVuSans-BoldOblique');
        expect($pdfBinary)->toMatch('/Times-BoldItalic|times_new_roman/i');
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// Sub-task 11.2: Integration test untuk view rendering (opsional)
// ─────────────────────────────────────────────────────────────────────────────

test('renders pdf_v2 view without throwing exceptions', function (): void {
    // Create minimal valid data
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create(['semester' => 'Ganjil', 'name' => '2025/2026']);
    $schoolClass = $student->schoolClass;
    $rapor = Rapor::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'program' => 'Reguler',
        'sumber_pembelajaran' => 'Buku teks dan LKS',
    ]);

    $viewData = [
        'rapor' => $rapor,
        'student' => $student,
        'academicYear' => $academicYear,
        'schoolClass' => $schoolClass,
        'grades' => collect([]),
        'gradesBySubject' => [],
        'attitudeScores' => collect([]),
        'knowledgeSkillScores' => collect([]),
        'learningAchievements' => collect([]),
        'personalityScore' => null,
        'attendanceBySubject' => collect([]),
        'overallAttendance' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0, 'total' => 0],
        'semesterMonths' => [7, 8, 9, 10, 11, 12],
        'monthNames' => [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ],
        'waliKelasName' => $schoolClass?->homeroomTeacher?->user?->name,
        'titimangsaFormatted' => 'Jakarta, 20 Juni 2025',
    ];

    $html = view('rapor.pdf_v2', $viewData)->render();

    expect($html)->toContain('LAPORAN HASIL BELAJAR');
    expect($html)->toContain('page-break-after');
    expect($html)->toContain('Reguler');
    expect($html)->toContain('Buku teks dan LKS');
    expect($html)->toContain('Sumber Pembelajaran');
    expect($html)->toContain($academicYear->name);
    expect($html)->not->toContain('@font-face');
    expect($html)->toContain("'times new roman', serif");
    expect($html)->not->toContain('courgette');
});

// ─────────────────────────────────────────────────────────────────────────────
// Sub-task 11.3: Regression test untuk backup v1 (opsional)
// ─────────────────────────────────────────────────────────────────────────────

test('v1 backup template still exists', function (): void {
    expect(file_exists(resource_path('views/rapor/pdf_v1_backup.blade.php')))->toBeTrue();
});
