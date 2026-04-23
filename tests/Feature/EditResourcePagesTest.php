<?php

use App\Filament\Clusters\Academic\Resources\AcademicYears\Pages\EditAcademicYear;
use App\Filament\Clusters\Academic\Resources\Schedules\Pages\EditSchedule;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Clusters\Academic\Resources\Subjects\Pages\EditSubject;
use App\Filament\Clusters\DataPersonalia\Resources\Students\Pages\EditStudent;
use App\Filament\Clusters\DataPersonalia\Resources\Teachers\Pages\EditTeacher;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->asAdmin()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('can render edit student page', function () {
    $student = Student::factory()->create();

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->assertSuccessful();
});

test('can render edit teacher page', function () {
    $teacher = Teacher::factory()->create();

    Livewire::test(EditTeacher::class, ['record' => $teacher->getRouteKey()])
        ->assertSuccessful();
});

test('can render edit academic year page', function () {
    $academicYear = AcademicYear::factory()->create();

    Livewire::test(EditAcademicYear::class, ['record' => $academicYear->getRouteKey()])
        ->assertSuccessful();
});

test('can render edit school class page', function () {
    $schoolClass = SchoolClass::factory()->create();

    Livewire::test(EditSchoolClass::class, ['record' => $schoolClass->getRouteKey()])
        ->assertSuccessful();
});

test('can render edit subject page', function () {
    $subject = Subject::factory()->create();

    Livewire::test(EditSubject::class, ['record' => $subject->getRouteKey()])
        ->assertSuccessful();
});

test('can render edit schedule page', function () {
    $schedule = Schedule::factory()->create();

    Livewire::test(EditSchedule::class, ['record' => $schedule->getRouteKey()])
        ->assertSuccessful();
});
