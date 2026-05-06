<?php

// Feature: student-grades-report-ui, Property 2: Nilai yang tidak tersedia selalu ditampilkan sebagai placeholder
// Feature: student-grades-report-ui, Property 3: Semua 11 grade type selalu ditampilkan untuk setiap mata pelajaran
// Feature: student-grades-report-ui, Property 4: Nilai numerik selalu diformat dengan dua angka desimal

use App\Filament\Student\Pages\MyGradesPage;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Property 2: Nilai yang tidak tersedia selalu ditampilkan sebagai placeholder "—"
 * Property 3: Semua 11 grade type selalu ditampilkan untuk setiap mata pelajaran
 * Property 4: Nilai numerik selalu diformat dengan dua angka desimal
 *
 * Validates: Requirements 2.3, 2.4, 2.5
 */
test('grade display properties hold for random grade data', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('student'));

    for ($i = 0; $i < 30; $i++) {
        // Wrap each iteration in a transaction so data is isolated between iterations
        DB::beginTransaction();

        try {
            // Arrange: create a student (factory auto-creates a siswa_ortu user)
            $student = Student::factory()->create();
            $user = User::find($student->user_id);

            // Ensure only one active academic year exists for this iteration
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
            $academicYear = AcademicYear::factory()->active()->create();

            // Create 1–3 subjects
            $subjectCount = fake()->numberBetween(1, 3);
            $subjects = Subject::factory()->count($subjectCount)->create();

            // Track which grade_types exist per subject and which are missing
            $existingGrades = [];   // [subject_id => [grade_type => score]]
            $hasMissingGrades = false;

            foreach ($subjects as $subject) {
                // Pick a random subset of grade types (at least 1, at most 11)
                $allTypes = Grade::GRADE_TYPES;
                $subsetSize = fake()->numberBetween(1, count($allTypes));
                $selectedTypes = fake()->randomElements($allTypes, $subsetSize);
                $missingForSubject = array_values(array_diff($allTypes, $selectedTypes));

                if (count($missingForSubject) > 0) {
                    $hasMissingGrades = true;
                }

                $existingGrades[$subject->id] = [];

                foreach ($selectedTypes as $gradeType) {
                    $score = fake()->randomFloat(2, 0, 100);
                    Grade::factory()->create([
                        'student_id' => $student->id,
                        'subject_id' => $subject->id,
                        'academic_year_id' => $academicYear->id,
                        'grade_type' => $gradeType,
                        'score' => $score,
                    ]);
                    $existingGrades[$subject->id][$gradeType] = $score;
                }
            }

            // Act: render the page as the student
            $this->actingAs($user);
            $component = Livewire::test(MyGradesPage::class)->assertSuccessful();

            // Assert Property 3: all 11 grade type labels appear in the response
            // Feature: student-grades-report-ui, Property 3: Semua 11 grade type selalu ditampilkan untuk setiap mata pelajaran
            foreach (Grade::GRADE_TYPES as $gradeType) {
                $component->assertSee(
                    $gradeType,
                    sprintf(
                        'Iteration %d: grade type label "%s" not found in response',
                        $i + 1,
                        $gradeType,
                    ),
                );
            }

            // Assert Property 4: every score in the database appears formatted with 2 decimal places
            // Feature: student-grades-report-ui, Property 4: Nilai numerik selalu diformat dengan dua angka desimal
            foreach ($existingGrades as $subjectId => $gradeMap) {
                foreach ($gradeMap as $gradeType => $score) {
                    $formatted = number_format((float) $score, 2);
                    $component->assertSee(
                        $formatted,
                        sprintf(
                            'Iteration %d: score "%s" (grade_type=%s, subject=%s) not found formatted as "%s"',
                            $i + 1,
                            $score,
                            $gradeType,
                            $subjectId,
                            $formatted,
                        ),
                    );
                }
            }

            // Assert Property 2: for missing grade_types, "—" appears in the response
            // Feature: student-grades-report-ui, Property 2: Nilai yang tidak tersedia selalu ditampilkan sebagai placeholder
            if ($hasMissingGrades) {
                $component->assertSee(
                    '—',
                    sprintf(
                        'Iteration %d: placeholder "—" not found in response despite missing grade types',
                        $i + 1,
                    ),
                );
            }
        } finally {
            DB::rollBack();
        }
    }
});
