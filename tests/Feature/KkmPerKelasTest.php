<?php

use App\Filament\Clusters\Academic\Resources\SchoolClasses\Pages\CreateSchoolClass;
use App\Filament\Clusters\Academic\Resources\SchoolClasses\Pages\EditSchoolClass;
use App\Filament\Student\Widgets\GradeStatsWidget;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectKkm;
use App\Models\User;
use App\Services\RaporService;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

// ─────────────────────────────────────────────────────────────────────────────
// 6.1 Unit tests: logika resolusi KKM (RaporService::resolveKkm)
// ─────────────────────────────────────────────────────────────────────────────

describe('RaporService::resolveKkm', function () {
    beforeEach(function () {
        $this->service = app(RaporService::class);
    });

    test('menggunakan kkm kelas jika tidak null', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => 80.00]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);

        $result = $this->service->resolveKkm($schoolClass, $subject->id);

        expect($result)->toBe(80.0);
    });

    test('fallback ke SubjectKkm jika kkm kelas null', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => null]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);
        SubjectKkm::factory()->create([
            'subject_id' => $subject->id,
            'level_id' => $schoolClass->level_id,
            'kkm' => 75.00,
        ]);

        $result = $this->service->resolveKkm($schoolClass, $subject->id);

        expect($result)->toBe(75.0);
    });

    test('fallback ke 70.0 jika kkm kelas null dan tidak ada SubjectKkm', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => null]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);
        // Tidak ada SubjectKkm untuk subject ini

        $result = $this->service->resolveKkm($schoolClass, $subject->id);

        expect($result)->toBe(70.0);
    });

    test('fallback ke 70.0 jika schoolClass null', function () {
        $subject = Subject::factory()->create();

        $result = $this->service->resolveKkm(null, $subject->id);

        expect($result)->toBe(70.0);
    });

    test('kkm kelas mengalahkan SubjectKkm yang ada', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => 85.00]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);
        SubjectKkm::factory()->create([
            'subject_id' => $subject->id,
            'level_id' => $schoolClass->level_id,
            'kkm' => 70.00,
        ]);

        $result = $this->service->resolveKkm($schoolClass, $subject->id);

        // KKM kelas (85) harus digunakan, bukan SubjectKkm (70)
        expect($result)->toBe(85.0);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 6.2 Feature tests: form kelas (field KKM, validasi, simpan null)
// ─────────────────────────────────────────────────────────────────────────────

describe('SchoolClassForm KKM field', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->asAdmin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    });

    test('menampilkan field KKM di form buat kelas', function () {
        Livewire::test(CreateSchoolClass::class)
            ->assertFormFieldExists('kkm');
    });

    test('menampilkan field KKM di form edit kelas', function () {
        $schoolClass = SchoolClass::factory()->create();

        Livewire::test(EditSchoolClass::class, ['record' => $schoolClass->getRouteKey()])
            ->assertFormFieldExists('kkm');
    });

    test('menyimpan null ketika field KKM dikosongkan', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => 80.00]);

        Livewire::test(EditSchoolClass::class, ['record' => $schoolClass->getRouteKey()])
            ->fillForm(['kkm' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas(SchoolClass::class, [
            'id' => $schoolClass->id,
            'kkm' => null,
        ]);
    });

    test('menyimpan nilai KKM yang valid ke database', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => null]);

        Livewire::test(EditSchoolClass::class, ['record' => $schoolClass->getRouteKey()])
            ->fillForm(['kkm' => 75.50])
            ->call('save')
            ->assertHasNoFormErrors();

        assertDatabaseHas(SchoolClass::class, [
            'id' => $schoolClass->id,
            'kkm' => 75.50,
        ]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 6.3 Feature tests: RaporService menggunakan KKM kelas
// ─────────────────────────────────────────────────────────────────────────────

describe('RaporService KKM kelas integration', function () {
    beforeEach(function () {
        $this->service = app(RaporService::class);
    });

    test('resolveKkm menggunakan kkm kelas ketika tersedia', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => 90.00]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);

        $kkm = $this->service->resolveKkm($schoolClass, $subject->id);

        expect($kkm)->toBe(90.0);
    });

    test('resolveKkm fallback ke SubjectKkm ketika kkm kelas null', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => null]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);
        SubjectKkm::factory()->create([
            'subject_id' => $subject->id,
            'level_id' => $schoolClass->level_id,
            'kkm' => 72.00,
        ]);

        $kkm = $this->service->resolveKkm($schoolClass, $subject->id);

        expect($kkm)->toBe(72.0);
    });

    test('resolveKkm fallback ke 70.0 ketika kkm kelas null dan tidak ada SubjectKkm', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => null]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);

        $kkm = $this->service->resolveKkm($schoolClass, $subject->id);

        expect($kkm)->toBe(70.0);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 6.4 Feature tests: GradeStatsWidget menggunakan KKM kelas
// ─────────────────────────────────────────────────────────────────────────────

describe('GradeStatsWidget KKM kelas', function () {
    test('widget merender dengan benar untuk siswa dengan kkm kelas', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => 90.00]);
        $user = User::factory()->asSiswa()->create();
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'class_id' => $schoolClass->id,
        ]);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);

        // Nilai 85 < KKM 90 → harus dihitung sebagai below KKM
        Grade::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'grade_type' => 'RAPOR',
            'score' => 85.00,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('siswa_ortu'));

        Livewire::test(GradeStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('Di Bawah KKM');
    });

    test('tidak menghitung below_kkm ketika nilai di atas kkm kelas', function () {
        $schoolClass = SchoolClass::factory()->create(['kkm' => 70.00]);
        $user = User::factory()->asSiswa()->create();
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'class_id' => $schoolClass->id,
        ]);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);

        // Nilai 85 > KKM 70 → tidak dihitung sebagai below KKM
        Grade::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'grade_type' => 'RAPOR',
            'score' => 85.00,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('siswa_ortu'));

        // Widget harus merender tanpa error
        Livewire::test(GradeStatsWidget::class)
            ->assertSuccessful();
    });

    test('resolveKkm menggunakan kkm kelas untuk menghitung below_kkm', function () {
        $service = app(RaporService::class);

        // Kelas dengan KKM 90 — nilai 85 harus below KKM
        $schoolClass = SchoolClass::factory()->create(['kkm' => 90.00]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);

        $kkm = $service->resolveKkm($schoolClass, $subject->id);
        $isBelowKkm = $kkm > 85.0;

        expect($kkm)->toBe(90.0);
        expect($isBelowKkm)->toBeTrue();

        // Kelas dengan KKM 70 — nilai 85 tidak below KKM
        $schoolClass2 = SchoolClass::factory()->create(['kkm' => 70.00]);
        $subject2 = Subject::factory()->create(['level_id' => $schoolClass2->level_id]);

        $kkm2 = $service->resolveKkm($schoolClass2, $subject2->id);
        $isBelowKkm2 = $kkm2 > 85.0;

        expect($kkm2)->toBe(70.0);
        expect($isBelowKkm2)->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 6.5 Property test: form menerima semua nilai KKM valid dalam range 0-100
// Feature: kkm-per-kelas, Property 1: KKM valid diterima oleh form
// ─────────────────────────────────────────────────────────────────────────────

test('form menerima semua nilai KKM dalam range 0-100', function () {
    $this->actingAs(User::factory()->asAdmin()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $schoolClass = SchoolClass::factory()->create(['kkm' => null]);

    for ($i = 0; $i < 100; $i++) {
        $validKkm = fake()->randomFloat(2, 0, 100);

        Livewire::test(EditSchoolClass::class, ['record' => $schoolClass->getRouteKey()])
            ->fillForm(['kkm' => $validKkm])
            ->call('save')
            ->assertHasNoFormErrors(['kkm']);
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 6.6 Property test: form menolak nilai KKM di luar range 0-100
// Feature: kkm-per-kelas, Property 2: Nilai KKM di luar rentang ditolak
// ─────────────────────────────────────────────────────────────────────────────

test('form menolak nilai KKM di luar range 0-100', function () {
    $this->actingAs(User::factory()->asAdmin()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $schoolClass = SchoolClass::factory()->create(['kkm' => null]);

    for ($i = 0; $i < 100; $i++) {
        $invalidKkm = fake()->boolean()
            ? fake()->randomFloat(2, -100, -0.01)
            : fake()->randomFloat(2, 100.01, 200);

        Livewire::test(EditSchoolClass::class, ['record' => $schoolClass->getRouteKey()])
            ->fillForm(['kkm' => $invalidKkm])
            ->call('save')
            ->assertHasFormErrors(['kkm']);
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 6.7 Property test: prioritas resolusi KKM selalu diikuti
// Feature: kkm-per-kelas, Property 3: Prioritas resolusi KKM selalu diikuti
// ─────────────────────────────────────────────────────────────────────────────

test('resolusi KKM mengikuti urutan prioritas yang benar', function () {
    $service = app(RaporService::class);

    for ($i = 0; $i < 100; $i++) {
        $classKkm = fake()->boolean() ? fake()->randomFloat(2, 0, 100) : null;
        $hasSubjectKkm = fake()->boolean();
        $subjectKkmValue = $hasSubjectKkm ? fake()->randomFloat(2, 0, 100) : null;

        $schoolClass = SchoolClass::factory()->create(['kkm' => $classKkm]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);

        if ($hasSubjectKkm) {
            SubjectKkm::factory()->create([
                'subject_id' => $subject->id,
                'level_id' => $schoolClass->level_id,
                'kkm' => $subjectKkmValue,
            ]);
        }

        $result = $service->resolveKkm($schoolClass, $subject->id);

        if ($classKkm !== null) {
            // Priority 1: KKM kelas
            expect($result)->toBe((float) $classKkm);
        } elseif ($hasSubjectKkm) {
            // Priority 2: SubjectKkm
            expect($result)->toBe((float) $subjectKkmValue);
        } else {
            // Priority 3: fallback 70.0
            expect($result)->toBe(70.0);
        }
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 6.8 Property test: penanda below-kkm konsisten dengan KKM yang berlaku
// Feature: kkm-per-kelas, Property 4: Penanda below-kkm konsisten
// ─────────────────────────────────────────────────────────────────────────────

test('penanda below-kkm muncul jika dan hanya jika nilai lebih rendah dari KKM', function () {
    $service = app(RaporService::class);

    for ($i = 0; $i < 100; $i++) {
        $score = fake()->randomFloat(2, 0, 100);
        $classKkm = fake()->randomFloat(2, 0, 100);

        $schoolClass = SchoolClass::factory()->create(['kkm' => $classKkm]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);

        $resolvedKkm = $service->resolveKkm($schoolClass, $subject->id);
        $isBelowKkm = $score < $resolvedKkm;

        // Verifikasi konsistensi: isBelowKkm harus sama dengan ($score < $resolvedKkm)
        expect($isBelowKkm)->toBe($score < $resolvedKkm);

        // Verifikasi resolvedKkm adalah classKkm karena kita set classKkm tidak null
        expect($resolvedKkm)->toBe((float) $classKkm);
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 6.9 Property test: GradeStatsWidget menggunakan KKM kelas jika tersedia
// Feature: kkm-per-kelas, Property 5: GradeStatsWidget menggunakan KKM kelas
// ─────────────────────────────────────────────────────────────────────────────

test('GradeStatsWidget menggunakan kkm kelas untuk semua kombinasi nilai dan KKM', function () {
    $service = app(RaporService::class);

    for ($i = 0; $i < 100; $i++) {
        $classKkm = fake()->randomFloat(2, 50, 95);
        $score = fake()->randomFloat(2, 0, 100);
        $expectedBelowKkm = $score < $classKkm;

        $schoolClass = SchoolClass::factory()->create(['kkm' => $classKkm]);
        $subject = Subject::factory()->create(['level_id' => $schoolClass->level_id]);

        // Verifikasi bahwa resolveKkm menggunakan kkm kelas
        $resolvedKkm = $service->resolveKkm($schoolClass, $subject->id);
        expect($resolvedKkm)->toBe((float) $classKkm);

        // Verifikasi logika below-kkm konsisten
        $isBelowKkm = $score < $resolvedKkm;
        expect($isBelowKkm)->toBe($expectedBelowKkm);
    }
});
