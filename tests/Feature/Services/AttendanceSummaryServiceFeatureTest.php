<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Kbm;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\AttendanceSummaryService;

test('getMonthlyBreakdownBySubject mengelompokkan absensi per bulan per mapel', function (): void {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create(['semester' => '1']);
    $subject = Subject::factory()->create();
    $schoolClass = SchoolClass::factory()->create();
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'subject_id' => $subject->id,
    ]);

    // KBM in July (semester 1)
    $kbmJuly = Kbm::factory()->create(['schedule_id' => $schedule->id, 'date' => '2024-07-15']);
    Attendance::factory()->create(['kbm_id' => $kbmJuly->id, 'student_id' => $student->id, 'status' => 'HADIR']);

    // KBM in August (semester 1)
    $kbmAugust = Kbm::factory()->create(['schedule_id' => $schedule->id, 'date' => '2024-08-20']);
    Attendance::factory()->create(['kbm_id' => $kbmAugust->id, 'student_id' => $student->id, 'status' => 'SAKIT']);

    $breakdown = app(AttendanceSummaryService::class)->getMonthlyBreakdownBySubject($student, $academicYear);

    expect($breakdown)->not->toBeEmpty();
    expect($breakdown->has($subject->id))->toBeTrue();

    $subjectBreakdown = $breakdown->get($subject->id);
    expect($subjectBreakdown['subject_name'])->toBe($subject->name);
    expect($subjectBreakdown['months'])->toHaveKey(7);
    expect($subjectBreakdown['months'])->toHaveKey(8);
    expect($subjectBreakdown['months'][7]['hadir'])->toBe(1);
    expect($subjectBreakdown['months'][8]['sakit'])->toBe(1);
});

test('getOverallSummary menghitung total SAKIT IZIN ALPA', function (): void {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create(['semester' => '1']);
    $schedule = Schedule::factory()->create();

    foreach (['SAKIT', 'IZIN', 'ALPA', 'ALPA', 'HADIR'] as $status) {
        $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id, 'date' => '2024-07-15']);
        Attendance::factory()->create(['kbm_id' => $kbm->id, 'student_id' => $student->id, 'status' => $status]);
    }

    $summary = app(AttendanceSummaryService::class)->getOverallSummary($student, $academicYear);

    expect($summary['sakit'])->toBe(1);
    expect($summary['izin'])->toBe(1);
    expect($summary['alpa'])->toBe(2);
    expect($summary['total'])->toBe(5);
});

test('getMonthlyBreakdownBySubject semester 2 menggunakan bulan Januari-Juni', function (): void {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create(['semester' => '2']);
    $subject = Subject::factory()->create();
    $schedule = Schedule::factory()->create(['subject_id' => $subject->id]);

    // KBM in March (semester 2)
    $kbm = Kbm::factory()->create(['schedule_id' => $schedule->id, 'date' => '2025-03-10']);
    Attendance::factory()->create(['kbm_id' => $kbm->id, 'student_id' => $student->id, 'status' => 'HADIR']);

    $breakdown = app(AttendanceSummaryService::class)->getMonthlyBreakdownBySubject($student, $academicYear);

    expect($breakdown->has($subject->id))->toBeTrue();
    $subjectBreakdown = $breakdown->get($subject->id);
    expect($subjectBreakdown['months'])->toHaveKey(3); // March
    expect($subjectBreakdown['months'][3]['hadir'])->toBe(1);
});
