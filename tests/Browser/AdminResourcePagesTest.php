<?php

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

test('admin can open key edit resource pages without javascript errors', function (): void {
    $admin = User::factory()->asAdmin()->create();

    $student = Student::factory()->create();
    $teacher = Teacher::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $schoolClass = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id]);
    $subject = Subject::factory()->create();
    $schedule = Schedule::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);

    smokeAuthenticatedPages($admin, [
        "/admin/students/{$student->getRouteKey()}/edit",
        "/admin/teachers/{$teacher->getRouteKey()}/edit",
        "/admin/academic-years/{$academicYear->getRouteKey()}/edit",
        "/admin/school-classes/{$schoolClass->getRouteKey()}/edit",
        "/admin/subjects/{$subject->getRouteKey()}/edit",
        "/admin/schedules/{$schedule->getRouteKey()}/edit",
    ]);
});

test('admin can open key resource index pages without javascript errors', function (): void {
    $admin = User::factory()->asAdmin()->create();

    smokeAuthenticatedPages($admin, [
        '/admin/students',
        '/admin/teachers',
        '/admin/lesson-plans',
        '/admin/kbms',
        '/admin/akses-sementara',
        '/admin/log-akses',
        '/admin/activity-log',
    ]);
});
