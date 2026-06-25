<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

pest()->browser()->timeout(15000);

require_once __DIR__.'/Browser/helpers.php';

/**
 * Livewire render tests migrated to Playwright E2E in tests/Browser/.
 * Keeps fast unit/policy/service tests in Feature; skips redundant UI bootstrapping.
 */
$browserMigratedFeatureTests = [
    'Feature/EditResourcePagesTest.php',
    'Feature/MyGradesPageTest.php',
    'Feature/MyRaporPageTest.php',
    'Feature/Widgets/AdminOverviewStatsTest.php',
    'Feature/Widgets/GuruOverviewStatsTest.php',
    'Feature/Widgets/KepsekOverviewStatsTest.php',
    'Feature/Widgets/AttendanceSummaryWidgetTest.php',
    'Feature/Widgets/JadwalKalenderWidgetTest.php',
    'Feature/Student/Widgets/SiswaOrtuOverviewStatsTest.php',
    'Feature/Student/Widgets/StudentAttendanceSummaryWidgetTest.php',
    'Feature/Filament/Student/MyGradesPageTest.php',
    'Feature/Filament/Guru/GradeInputPageTest.php',
    'Feature/Filament/Guru/AttitudeScoreResourceTest.php',
    'Feature/Filament/Guru/KnowledgeSkillScoreResourceTest.php',
    'Feature/Filament/Guru/PersonalityScoreResourceTest.php',
    'Feature/Filament/Guru/LearningAchievementResourceTest.php',
    'Feature/Filament/Admin/GradeResourceTest.php',
    'Feature/Filament/Admin/SubjectKkmResourceTest.php',
    'Feature/Filament/Admin/RaporResourceTest.php',
    'Feature/Filament/Kepsek/RaporResourceTest.php',
    'Feature/Filament/Student/AcademicLevelSwitcherTest.php',
    'Feature/Filament/Student/AnnouncementArticleViewTest.php',
    'Feature/Filament/Student/AnnouncementBugConditionTest.php',
    'Feature/Filament/Student/AnnouncementPreservationTest.php',
    'Feature/Filament/AnnouncementPreviewTest.php',
    'Feature/Filament/Guru/LessonPlanFilePreviewTest.php',
    'Feature/Filament/Guru/LessonPlanBugConditionTest.php',
    'Feature/Filament/Guru/LessonPlanPreservationTest.php',
    'Feature/Filament/Pages/TemporaryAccessManagementTest.php',
    'Feature/Filament/Pages/TemporaryAccessLogListTest.php',
    'Feature/Filament/Pages/ActiveTemporaryAccessListTest.php',
    'Feature/ActivityLog/ActivityLogPageTest.php',
    'Feature/SystemLogViewerTest.php',
    'Feature/StaffResourceTest.php',
    'Feature/GradeDisplayPropertyTest.php',
    'Feature/Student/Resources/Attendances/StudentAttendanceTest.php',
    'Feature/Kepsek/Resources/Attendances/KepsekAttendanceTest.php',
    'Feature/Kepsek/Resources/AcademicApprovalEditPageTest.php',
    'Feature/Pembayaran/StudentInvoicePaymentTest.php',
    'Feature/Pembayaran/ManageDefaultSppTest.php',
    'Feature/AdminAssignTemporaryAccessE2ETest.php',
];

foreach ($browserMigratedFeatureTests as $migratedTestFile) {
    pest()->beforeEach(function (): void {
        $this->markTestSkipped('Migrated to Playwright E2E in tests/Browser/. Run: php artisan test --testsuite=Browser');
    })->in($migratedTestFile);
}

pest()->group('serial')->in('Feature/Personalia');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

use App\Models\AcademicYear;
use App\Models\AttitudeScore;
use App\Models\Grade;
use App\Models\KnowledgeSkillScore;
use App\Models\PersonalityScore;
use App\Models\Student;
use App\Models\Subject;

/**
 * Randomized property-test iteration count. Lower in CI to save minutes.
 */
function propertyIterationCount(int $default = 100): int
{
    $configured = getenv('PEST_PROPERTY_ITERATIONS');

    if ($configured !== false && $configured !== '') {
        return max(1, (int) $configured);
    }

    if (getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true') {
        return min($default, 20);
    }

    return $default;
}

/**
 * Dataset size for property tests that generate Pest `->with()` cases.
 */
function propertyDatasetCount(int $default = 50): int
{
    $configured = getenv('PEST_PROPERTY_ITERATIONS');

    if ($configured !== false && $configured !== '') {
        return max(1, (int) $configured);
    }

    if (getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true') {
        return min($default, 10);
    }

    return $default;
}

/**
 * Create a complete set of grade data for a student so validateCompleteness passes.
 */
function createCompleteGrades(Student $student, Subject $subject, AcademicYear $academicYear): void
{
    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'PH1',
        'score' => 80.00,
    ]);

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'ATS',
        'score' => 75.00,
    ]);

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'SAS',
        'score' => 78.00,
    ]);

    KnowledgeSkillScore::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'knowledge_score' => 80.00,
        'skill_score' => 78.00,
    ]);

    AttitudeScore::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'aspect' => 'Spiritual',
        'score' => 85.00,
    ]);

    PersonalityScore::factory()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'kedisiplinan' => 'A',
        'kerapihan' => 'B',
        'kerajinan' => 'A',
        'kesopanan' => 'B',
    ]);
}
