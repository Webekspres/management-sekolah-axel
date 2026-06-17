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
