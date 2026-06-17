<?php

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Policies\AcademicYearPolicy;
use App\Policies\AnnouncementPolicy;
use App\Policies\KbmPolicy;
use App\Policies\LessonPlanPolicy;
use App\Policies\SchedulePolicy;
use App\Policies\SchoolClassPolicy;
use App\Policies\StudentPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\TeacherPolicy;
use Illuminate\Support\Facades\Gate;

test('all rbac policies are registered with gate', function (string $modelClass, string $policyClass): void {
    expect(Gate::getPolicyFor($modelClass))->toBeInstanceOf($policyClass);
})->with([
    [AcademicYear::class, AcademicYearPolicy::class],
    [Announcement::class, AnnouncementPolicy::class],
    [Kbm::class, KbmPolicy::class],
    [LessonPlan::class, LessonPlanPolicy::class],
    [Schedule::class, SchedulePolicy::class],
    [SchoolClass::class, SchoolClassPolicy::class],
    [Student::class, StudentPolicy::class],
    [Subject::class, SubjectPolicy::class],
    [Teacher::class, TeacherPolicy::class],
]);
