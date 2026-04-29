<?php

// Feature: attendance-module

use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Helper: build upsert data array from students + kbm (mirrors KbmsTable action logic).
 *
 * @param  Collection<int, Student>  $students
 * @return array<int, array{id: string, kbm_id: string, student_id: string, status: string}>
 */
function buildUpsertData(Collection $students, string $kbmId, string $status = 'HADIR'): array
{
    return $students->map(fn (Student $student): array => [
        'id' => (string) Str::ulid(),
        'kbm_id' => $kbmId,
        'student_id' => $student->id,
        'status' => $status,
    ])->all();
}

/**
 * Helper: perform the upsert inside a transaction (mirrors KbmsTable action logic).
 *
 * @param  array<int, array{id: string, kbm_id: string, student_id: string, status: string}>  $upsertData
 */
function performUpsert(array $upsertData): void
{
    DB::transaction(function () use ($upsertData): void {
        Attendance::upsert(
            $upsertData,
            uniqueBy: ['kbm_id', 'student_id'],
            update: ['status'],
        );
    });
}

/**
 * Helper: create a KBM with a class that has $studentCount students.
 *
 * @return array{kbm: Kbm, students: Collection<int, Student>}
 */
function createKbmWithStudents(int $studentCount): array
{
    $teacher = Teacher::factory()->create();
    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $teacher->id]);
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
    $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id]);

    $students = Student::factory($studentCount)->create(['class_id' => $schoolClass->id]);

    return ['kbm' => $kbm, 'students' => $students];
}

/**
 * Property 1: Bulk input menghasilkan tepat satu record per siswa
 *
 * Validates: Requirements 1.2, 1.3, 2.3
 */
it('creates exactly one attendance record per student for any class size', function (int $studentCount): void {
    ['kbm' => $kbm, 'students' => $students] = createKbmWithStudents($studentCount);

    $upsertData = buildUpsertData($students, $kbm->id);
    performUpsert($upsertData);

    expect(Attendance::where('kbm_id', $kbm->id)->count())->toBe($studentCount);

    // Each (kbm_id, student_id) pair must be unique
    $uniquePairs = Attendance::where('kbm_id', $kbm->id)
        ->select('kbm_id', 'student_id')
        ->get()
        ->map(fn ($row) => $row->kbm_id.'|'.$row->student_id)
        ->unique()
        ->count();

    expect($uniquePairs)->toBe($studentCount);
})->with(function (): array {
    $cases = [];
    for ($i = 0; $i < 50; $i++) {
        $cases[] = [random_int(1, 5)];
    }

    return $cases;
});

/**
 * Property 2: Upsert idempoten — tidak ada duplikasi
 *
 * Validates: Requirements 1.3, 1.4
 */
it('upsert is idempotent and never creates duplicate records', function (int $studentCount, int $submitCount): void {
    ['kbm' => $kbm, 'students' => $students] = createKbmWithStudents($studentCount);

    // Submit the same data multiple times
    for ($i = 0; $i < $submitCount; $i++) {
        $upsertData = buildUpsertData($students, $kbm->id, 'HADIR');
        performUpsert($upsertData);
    }

    // Count must always equal studentCount regardless of how many times submitted
    expect(Attendance::where('kbm_id', $kbm->id)->count())->toBe($studentCount);
})->with(function (): array {
    $cases = [];
    for ($i = 0; $i < 50; $i++) {
        $cases[] = [random_int(1, 5), random_int(2, 3)];
    }

    return $cases;
});

/**
 * Property 8: Pre-populate form mencerminkan data tersimpan
 *
 * Validates: Requirements 2.2
 */
it('fillForm logic returns statuses that match existing attendance records', function (int $studentCount): void {
    ['kbm' => $kbm, 'students' => $students] = createKbmWithStudents($studentCount);

    $statuses = ['HADIR', 'SAKIT', 'IZIN', 'ALPA'];

    // Create attendance records with random statuses
    $expectedStatuses = [];
    foreach ($students as $student) {
        $status = $statuses[array_rand($statuses)];
        Attendance::factory()->create([
            'kbm_id' => $kbm->id,
            'student_id' => $student->id,
            'status' => $status,
        ]);
        $expectedStatuses[$student->id] = $status;
    }

    // Replicate the fillForm logic from KbmsTable
    $existingAttendances = Attendance::where('kbm_id', $kbm->id)
        ->pluck('status', 'student_id');

    $formData = $students->map(fn (Student $student): array => [
        'student_id' => $student->id,
        'status' => $existingAttendances->get($student->id, 'HADIR'),
    ])->values()->all();

    // Each student's pre-populated status must match what's in the DB
    foreach ($formData as $row) {
        expect($row['status'])->toBe($expectedStatuses[$row['student_id']]);
    }
})->with(function (): array {
    $cases = [];
    for ($i = 0; $i < 50; $i++) {
        $cases[] = [random_int(1, 5)];
    }

    return $cases;
});
