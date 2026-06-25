<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Kbm;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;

beforeEach(function (): void {
    $this->guruUser = User::factory()->asGuru()->create();
    $this->teacher = Teacher::factory()->create(['user_id' => $this->guruUser->id]);
    $this->academicYear = AcademicYear::factory()->active()->create();
    $this->schoolClass = SchoolClass::factory()->create([
        'academic_year_id' => $this->academicYear->id,
    ]);
    $this->schedule = Schedule::factory()->create([
        'teacher_id' => $this->teacher->id,
        'class_id' => $this->schoolClass->id,
    ]);
    $this->students = Student::factory()->count(2)->create([
        'class_id' => $this->schoolClass->id,
    ]);
});

test('guru can open grade input page for own schedule', function (): void {
    $studentName = $this->students->first()->user->name;

    visitAuthenticated($this->guruUser, "/guru/input-nilai/{$this->schedule->id}")
        ->assertSee('Input Nilai')
        ->assertSee($studentName)
        ->assertNoSmoke();
});

test('guru cannot open grade input page for another teachers schedule', function (): void {
    $otherSchedule = Schedule::factory()->create([
        'teacher_id' => Teacher::factory()->create()->id,
    ]);

    visitAuthenticated($this->guruUser, "/guru/input-nilai/{$otherSchedule->id}")
        ->assertSee('403');
});

test('guru can save a PH grade from the grade input page', function (): void {
    $page = visitAuthenticated($this->guruUser, "/guru/input-nilai/{$this->schedule->id}");
    $page->assertSee('Input Nilai')
        ->script(<<<'JS'
            const input = document.querySelector('tbody tr:first-child .fi-ta-cell-grade-p-h1 input[type="number"]');
            input.focus();
            input.value = '85';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.blur();
        JS);

    $page->wait(3)->assertNoJavaScriptErrors();

    expect(Grade::query()
        ->where('subject_id', $this->schedule->subject_id)
        ->where('academic_year_id', $this->academicYear->id)
        ->where('grade_type', 'PH1')
        ->whereIn('student_id', $this->students->pluck('id'))
        ->where('score', 85)
        ->exists())->toBeTrue();
});

test('guru can input attendance for students in KBM class', function (): void {
    $studentName = $this->students->first()->user->name;
    $kbm = Kbm::factory()->create([
        'schedule_id' => $this->schedule->id,
        'date' => today()->toDateString(),
    ]);

    visitAuthenticated($this->guruUser, "/guru/kbms/{$kbm->getRouteKey()}/attendance")
        ->assertSee('Input Absensi')
        ->assertSee($studentName)
        ->assertNoSmoke();
});

test('guru can open isi absensi from dashboard checklist for today schedule', function (): void {
    $this->schedule->update(['day_of_week' => now()->dayOfWeekIso]);

    $studentName = $this->students->first()->user->name;
    $kbm = Kbm::factory()->create([
        'schedule_id' => $this->schedule->id,
        'date' => today()->toDateString(),
    ]);

    visitAuthenticated($this->guruUser, '/guru')
        ->assertSee('Checklist mengajar hari ini')
        ->click('Isi Absensi')
        ->assertSee('Input Absensi')
        ->assertSee($studentName)
        ->assertNoSmoke();

    expect($kbm->id)->not->toBeEmpty();
});

test('guru academic resource pages load without javascript errors', function (): void {
    smokeAuthenticatedPages($this->guruUser, [
        '/guru/kbms',
        '/guru/lesson-plans',
        '/guru/attitude-scores',
        '/guru/knowledge-skill-scores',
        '/guru/personality-scores',
        '/guru/learning-achievements',
        '/guru/attendances',
    ]);
});
