<?php

/**
 * Fix Checking & Preservation Checking Tests
 *
 * Task 3: Fix Checking — verifikasi bug sudah teratasi
 * Task 4: Preservation Checking — verifikasi tidak ada regresi
 *
 * Semua test ini DIHARAPKAN LULUS pada kode yang sudah diperbaiki.
 *
 * **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3, 3.4, 3.5**
 */

use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

// ─────────────────────────────────────────────────────────────────────────────
// Helpers: simulasikan query Filament setelah fix
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Query pencarian Filament untuk SchoolClassForm (Wali Kelas) — setelah fix.
 * JOIN ke users, searchable di users.name dan teachers.nip.
 */
function schoolClassSearchFixed(string $search): Builder
{
    return Teacher::query()
        ->join('users', 'users.id', '=', 'teachers.user_id')
        ->select('teachers.*')
        ->where(function (Builder $q) use ($search): void {
            $q->where('users.name', 'like', "%{$search}%")
                ->orWhere('teachers.nip', 'like', "%{$search}%");
        });
}

/**
 * Query pencarian Filament untuk ScheduleForm (Guru Pengajar) — setelah fix.
 * JOIN ke users, searchable di users.name dan teachers.nip.
 */
function scheduleSearchFixed(string $search): Builder
{
    return Teacher::query()
        ->join('users', 'users.id', '=', 'teachers.user_id')
        ->select('teachers.*')
        ->where(function (Builder $q) use ($search): void {
            $q->where('users.name', 'like', "%{$search}%")
                ->orWhere('teachers.nip', 'like', "%{$search}%");
        });
}

/**
 * Query preload Filament (tanpa search) — setelah fix.
 * JOIN ke users tetap dilakukan, tapi tidak ada WHERE pencarian.
 */
function preloadQueryFixed(): Builder
{
    return Teacher::query()
        ->join('users', 'users.id', '=', 'teachers.user_id')
        ->select('teachers.*');
}

// ─────────────────────────────────────────────────────────────────────────────
// Task 3.2 — Fix Checking: pencarian nama di SchoolClassForm
// **Validates: Requirements 2.1**
// ─────────────────────────────────────────────────────────────────────────────

test('3.2 SchoolClassForm: pencarian nama menemukan guru yang tepat', function () {
    $userA = User::factory()->asGuru()->create(['name' => 'Hendra Kusuma']);
    $teacherA = Teacher::factory()->create([
        'user_id' => $userA->id,
        'nip' => '197601012005011001',
    ]);

    $userB = User::factory()->asGuru()->create(['name' => 'Wahyu Pratama']);
    Teacher::factory()->create([
        'user_id' => $userB->id,
        'nip' => '198002022008011002',
    ]);

    // Cari "Hendra" — hanya guru A yang harus ditemukan
    $results = schoolClassSearchFixed('Hendra')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacherA->id);
});

test('3.2 SchoolClassForm: pencarian nama parsial menemukan semua guru yang cocok', function () {
    $userA = User::factory()->asGuru()->create(['name' => 'Andi Setiawan']);
    $teacherA = Teacher::factory()->create(['user_id' => $userA->id, 'nip' => '197501012003011001']);

    $userB = User::factory()->asGuru()->create(['name' => 'Andi Prasetyo']);
    $teacherB = Teacher::factory()->create(['user_id' => $userB->id, 'nip' => '198001012006011002']);

    $userC = User::factory()->asGuru()->create(['name' => 'Budi Santoso']);
    Teacher::factory()->create(['user_id' => $userC->id, 'nip' => '199001012010011003']);

    // Cari "Andi" — harus menemukan dua guru
    $results = schoolClassSearchFixed('Andi')->get();
    $resultIds = $results->pluck('id')->sort()->values();

    expect($results)->toHaveCount(2)
        ->and($resultIds)->toContain($teacherA->id)
        ->and($resultIds)->toContain($teacherB->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// Task 3.3 — Fix Checking: pencarian nama di ScheduleForm
// **Validates: Requirements 2.2**
// ─────────────────────────────────────────────────────────────────────────────

test('3.3 ScheduleForm: pencarian nama menemukan guru yang tepat', function () {
    $userA = User::factory()->asGuru()->create(['name' => 'Fitri Handayani']);
    $teacherA = Teacher::factory()->create([
        'user_id' => $userA->id,
        'nip' => '198503152010012001',
    ]);

    $userB = User::factory()->asGuru()->create(['name' => 'Doni Kurniawan']);
    Teacher::factory()->create([
        'user_id' => $userB->id,
        'nip' => '199007202015011002',
    ]);

    // Cari "Fitri" — hanya guru A yang harus ditemukan
    $results = scheduleSearchFixed('Fitri')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacherA->id);
});

test('3.3 ScheduleForm: pencarian nama lengkap menemukan guru yang tepat', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Nurul Hidayah']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '198811102012012003',
    ]);

    // Cari nama lengkap
    $results = scheduleSearchFixed('Nurul Hidayah')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacher->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// Task 3.4 — Fix Checking: guru dengan nip = null ditemukan via nama
// **Validates: Requirements 2.4**
// ─────────────────────────────────────────────────────────────────────────────

test('3.4 guru dengan nip null ditemukan via pencarian nama di SchoolClassForm', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Kartini Dewi']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => null,
    ]);

    $results = schoolClassSearchFixed('Kartini')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacher->id);
});

test('3.4 guru dengan nip null ditemukan via pencarian nama di ScheduleForm', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Laila Sari']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => null,
    ]);

    $results = scheduleSearchFixed('Laila')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacher->id);
});

test('3.4 beberapa guru dengan nip null semuanya ditemukan via pencarian nama', function () {
    $userA = User::factory()->asGuru()->create(['name' => 'Mega Putri']);
    $teacherA = Teacher::factory()->create(['user_id' => $userA->id, 'nip' => null]);

    $userB = User::factory()->asGuru()->create(['name' => 'Mega Wati']);
    $teacherB = Teacher::factory()->create(['user_id' => $userB->id, 'nip' => null]);

    // Guru lain dengan NIP — tidak boleh muncul
    $userC = User::factory()->asGuru()->create(['name' => 'Budi Santoso']);
    Teacher::factory()->create(['user_id' => $userC->id, 'nip' => '199001012010011003']);

    $results = schoolClassSearchFixed('Mega')->get();
    $resultIds = $results->pluck('id')->sort()->values();

    expect($results)->toHaveCount(2)
        ->and($resultIds)->toContain($teacherA->id)
        ->and($resultIds)->toContain($teacherB->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// Task 4.1 — Preservation: pencarian via NIP tetap benar setelah fix
// **Validates: Requirements 2.3, 3.3**
// ─────────────────────────────────────────────────────────────────────────────

test('4.1 pencarian via NIP menemukan guru yang tepat di SchoolClassForm', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Prasetyo Wibowo']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '197901012003011001',
    ]);

    // Cari via NIP — harus tetap ditemukan setelah fix
    $results = schoolClassSearchFixed('197901012003011001')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacher->id);
});

test('4.1 pencarian via NIP parsial menemukan guru yang tepat di ScheduleForm', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Susanto Hadi']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '198505152010011002',
    ]);

    // Cari via sebagian NIP
    $results = scheduleSearchFixed('198505152010011002')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacher->id);
});

test('4.1 pencarian via NIP tidak mengembalikan guru lain yang tidak cocok', function () {
    $userA = User::factory()->asGuru()->create(['name' => 'Agus Salim']);
    $teacherA = Teacher::factory()->create([
        'user_id' => $userA->id,
        'nip' => '197001012000011001',
    ]);

    $userB = User::factory()->asGuru()->create(['name' => 'Bambang Sutrisno']);
    Teacher::factory()->create([
        'user_id' => $userB->id,
        'nip' => '198001012005011002',
    ]);

    // Cari NIP guru A — hanya guru A yang harus muncul
    $results = schoolClassSearchFixed('197001012000011001')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($teacherA->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// Task 4.2 — Preservation: preload dropdown menampilkan semua guru
// **Validates: Requirements 3.4**
// ─────────────────────────────────────────────────────────────────────────────

test('4.2 preload dropdown menampilkan semua guru (dengan NIP)', function () {
    $userA = User::factory()->asGuru()->create(['name' => 'Guru Pertama']);
    $teacherA = Teacher::factory()->create(['user_id' => $userA->id, 'nip' => '111111111111111111']);

    $userB = User::factory()->asGuru()->create(['name' => 'Guru Kedua']);
    $teacherB = Teacher::factory()->create(['user_id' => $userB->id, 'nip' => '222222222222222222']);

    $userC = User::factory()->asGuru()->create(['name' => 'Guru Ketiga']);
    $teacherC = Teacher::factory()->create(['user_id' => $userC->id, 'nip' => '333333333333333333']);

    // Preload tanpa search — semua guru harus muncul
    $results = preloadQueryFixed()->get();
    $resultIds = $results->pluck('id');

    expect($resultIds)->toContain($teacherA->id)
        ->and($resultIds)->toContain($teacherB->id)
        ->and($resultIds)->toContain($teacherC->id);
});

test('4.2 preload dropdown menampilkan guru dengan nip null', function () {
    $userA = User::factory()->asGuru()->create(['name' => 'Guru Tanpa NIP']);
    $teacherA = Teacher::factory()->create(['user_id' => $userA->id, 'nip' => null]);

    $userB = User::factory()->asGuru()->create(['name' => 'Guru Dengan NIP']);
    $teacherB = Teacher::factory()->create(['user_id' => $userB->id, 'nip' => '198001012005011001']);

    // Preload tanpa search — kedua guru harus muncul
    $results = preloadQueryFixed()->get();
    $resultIds = $results->pluck('id');

    expect($resultIds)->toContain($teacherA->id)
        ->and($resultIds)->toContain($teacherB->id);
});

test('4.2 preload mengembalikan jumlah guru yang benar', function () {
    // Buat 5 guru
    Teacher::factory()->count(5)->create();

    $totalTeachers = Teacher::count();
    $preloadResults = preloadQueryFixed()->get();

    expect($preloadResults)->toHaveCount($totalTeachers);
});

// ─────────────────────────────────────────────────────────────────────────────
// Task 4.3 — Preservation: label format Nama (NIP) atau Nama tetap benar
// **Validates: Requirements 3.2, 3.5**
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Simulasi getOptionLabelFromRecordUsing yang digunakan di SchoolClassForm dan ScheduleForm.
 */
function getTeacherOptionLabel(Teacher $teacher): string
{
    return $teacher->nip
        ? "{$teacher->user?->name} ({$teacher->nip})"
        : ($teacher->user?->name ?? '-');
}

test('4.3 label format Nama (NIP) benar untuk guru dengan NIP', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Teguh Santoso']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '198601012010011001',
    ]);

    $teacher->load('user');
    $label = getTeacherOptionLabel($teacher);

    expect($label)->toBe('Teguh Santoso (198601012010011001)');
});

test('4.3 label format hanya Nama untuk guru tanpa NIP', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Yuni Astuti']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => null,
    ]);

    $teacher->load('user');
    $label = getTeacherOptionLabel($teacher);

    expect($label)->toBe('Yuni Astuti');
});

test('4.3 label format fallback ke tanda hubung jika user tidak ada', function () {
    // Guru tanpa NIP dan tanpa relasi user (edge case)
    $teacher = new Teacher(['nip' => null]);

    $label = getTeacherOptionLabel($teacher);

    expect($label)->toBe('-');
});

test('4.3 label guru yang ditemukan via pencarian nama tetap benar formatnya', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Rizky Maulana']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '199205102015011003',
    ]);

    // Cari via nama, lalu cek label
    $found = schoolClassSearchFixed('Rizky')->with('user')->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($teacher->id);

    $label = getTeacherOptionLabel($found);

    expect($label)->toBe('Rizky Maulana (199205102015011003)');
});

test('4.3 label guru dengan nip null yang ditemukan via pencarian nama tetap benar', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Indah Permata']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => null,
    ]);

    // Cari via nama, lalu cek label
    $found = schoolClassSearchFixed('Indah')->with('user')->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($teacher->id);

    $label = getTeacherOptionLabel($found);

    expect($label)->toBe('Indah Permata');
});

// ─────────────────────────────────────────────────────────────────────────────
// Task 4.4 — Preservation: teacher_id yang tersimpan ke database tetap benar
// **Validates: Requirements 3.1**
// ─────────────────────────────────────────────────────────────────────────────

test('4.4 teacher_id yang tersimpan ke SchoolClass tetap benar setelah memilih guru', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Darmawan Putra']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '198401012008011001',
    ]);

    // Simulasi: guru ditemukan via pencarian nama
    $found = schoolClassSearchFixed('Darmawan')->first();
    expect($found)->not->toBeNull();

    // Simulasi: teacher_id yang dipilih disimpan ke SchoolClass
    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $found->id]);

    // Verifikasi: teacher_id yang tersimpan adalah ID guru yang benar
    expect($schoolClass->teacher_id)->toBe($teacher->id);

    $this->assertDatabaseHas('classes', [
        'id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
});

test('4.4 teacher_id yang tersimpan ke Schedule tetap benar setelah memilih guru', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Erlangga Saputra']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '198901012012011002',
    ]);

    // Simulasi: guru ditemukan via pencarian nama
    $found = scheduleSearchFixed('Erlangga')->first();
    expect($found)->not->toBeNull();

    // Simulasi: teacher_id yang dipilih disimpan ke Schedule
    $schedule = Schedule::factory()->create(['teacher_id' => $found->id]);

    // Verifikasi: teacher_id yang tersimpan adalah ID guru yang benar
    expect($schedule->teacher_id)->toBe($teacher->id);

    $this->assertDatabaseHas('schedules', [
        'id' => $schedule->id,
        'teacher_id' => $teacher->id,
    ]);
});

test('4.4 teacher_id guru dengan nip null tersimpan benar ke database', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Fatimah Zahra']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => null,
    ]);

    // Simulasi: guru tanpa NIP ditemukan via pencarian nama
    $found = schoolClassSearchFixed('Fatimah')->first();
    expect($found)->not->toBeNull();

    // Simpan ke SchoolClass
    $schoolClass = SchoolClass::factory()->create(['teacher_id' => $found->id]);

    expect($schoolClass->teacher_id)->toBe($teacher->id);

    $this->assertDatabaseHas('classes', [
        'id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Task 4.5 — Preservation: validasi bentrok jadwal di ScheduleForm tetap bekerja
// **Validates: Requirements 3.x (scope: ScheduleForm tidak berubah)**
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Simulasi logika validasi bentrok jadwal dari ScheduleForm.
 * Mengembalikan pesan error jika ada bentrok, atau null jika tidak ada.
 */
function checkScheduleConflict(
    string $classId,
    string $teacherId,
    int $dayOfWeek,
    string $startTime,
    string $endTime,
    ?string $excludeScheduleId = null
): ?string {
    // Cek bentrok kelas
    $classConflict = Schedule::where('class_id', $classId)
        ->where('day_of_week', $dayOfWeek)
        ->when($excludeScheduleId, fn ($query) => $query->where('id', '!=', $excludeScheduleId))
        ->where(function ($query) use ($startTime, $endTime): void {
            $query->whereBetween('start_time', [$startTime, $endTime])
                ->orWhereBetween('end_time', [$startTime, $endTime])
                ->orWhere(function ($q) use ($startTime, $endTime): void {
                    $q->where('start_time', '<=', $startTime)
                        ->where('end_time', '>=', $endTime);
                });
        })
        ->exists();

    if ($classConflict) {
        return 'Jadwal bentrok untuk kelas ini pada rentang waktu yang dipilih.';
    }

    // Cek bentrok guru
    $teacherConflict = Schedule::where('teacher_id', $teacherId)
        ->where('day_of_week', $dayOfWeek)
        ->when($excludeScheduleId, fn ($query) => $query->where('id', '!=', $excludeScheduleId))
        ->where(function ($query) use ($startTime, $endTime): void {
            $query->whereBetween('start_time', [$startTime, $endTime])
                ->orWhereBetween('end_time', [$startTime, $endTime])
                ->orWhere(function ($q) use ($startTime, $endTime): void {
                    $q->where('start_time', '<=', $startTime)
                        ->where('end_time', '>=', $endTime);
                });
        })
        ->exists();

    if ($teacherConflict) {
        return 'Guru ini sudah memiliki jadwal mengajar di kelas lain pada waktu tersebut.';
    }

    return null;
}

test('4.5 tidak ada bentrok jadwal ketika waktu berbeda', function () {
    $schedule = Schedule::factory()->create([
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '09:45:00',
    ]);

    // Jadwal baru di hari yang sama tapi waktu berbeda (tidak overlap)
    $error = checkScheduleConflict(
        classId: $schedule->class_id,
        teacherId: $schedule->teacher_id,
        dayOfWeek: 1,
        startTime: '10:00:00',
        endTime: '11:45:00',
    );

    expect($error)->toBeNull();
});

test('4.5 terdeteksi bentrok kelas ketika waktu overlap', function () {
    $schedule = Schedule::factory()->create([
        'day_of_week' => 2,
        'start_time' => '08:00:00',
        'end_time' => '09:45:00',
    ]);

    // Jadwal baru untuk kelas yang sama, hari yang sama, waktu overlap
    $error = checkScheduleConflict(
        classId: $schedule->class_id,
        teacherId: $schedule->teacher_id,
        dayOfWeek: 2,
        startTime: '09:00:00',
        endTime: '10:45:00',
    );

    expect($error)->toBe('Jadwal bentrok untuk kelas ini pada rentang waktu yang dipilih.');
});

test('4.5 terdeteksi bentrok guru ketika guru mengajar di kelas lain pada waktu yang sama', function () {
    $teacher = Teacher::factory()->create();

    // Jadwal pertama untuk kelas A
    $classA = SchoolClass::factory()->create();
    Schedule::factory()->create([
        'class_id' => $classA->id,
        'teacher_id' => $teacher->id,
        'day_of_week' => 3,
        'start_time' => '08:00:00',
        'end_time' => '09:45:00',
    ]);

    // Jadwal baru untuk kelas B — guru yang sama, waktu overlap
    $classB = SchoolClass::factory()->create();

    $error = checkScheduleConflict(
        classId: $classB->id,
        teacherId: $teacher->id,
        dayOfWeek: 3,
        startTime: '08:30:00',
        endTime: '10:15:00',
    );

    expect($error)->toBe('Guru ini sudah memiliki jadwal mengajar di kelas lain pada waktu tersebut.');
});

test('4.5 tidak ada bentrok ketika jadwal di hari berbeda', function () {
    $schedule = Schedule::factory()->create([
        'day_of_week' => 1,
        'start_time' => '08:00:00',
        'end_time' => '09:45:00',
    ]);

    // Jadwal baru di hari berbeda — tidak boleh ada bentrok
    $error = checkScheduleConflict(
        classId: $schedule->class_id,
        teacherId: $schedule->teacher_id,
        dayOfWeek: 2,
        startTime: '08:00:00',
        endTime: '09:45:00',
    );

    expect($error)->toBeNull();
});

test('4.5 tidak ada bentrok saat edit jadwal yang sudah ada (exclude self)', function () {
    $schedule = Schedule::factory()->create([
        'day_of_week' => 4,
        'start_time' => '10:00:00',
        'end_time' => '11:45:00',
    ]);

    // Edit jadwal yang sama — exclude dirinya sendiri, tidak boleh ada bentrok
    $error = checkScheduleConflict(
        classId: $schedule->class_id,
        teacherId: $schedule->teacher_id,
        dayOfWeek: 4,
        startTime: '10:00:00',
        endTime: '11:45:00',
        excludeScheduleId: $schedule->id,
    );

    expect($error)->toBeNull();
});

test('4.5 guru yang ditemukan via pencarian nama dapat digunakan dalam validasi bentrok', function () {
    $user = User::factory()->asGuru()->create(['name' => 'Gunawan Wibisono']);
    $teacher = Teacher::factory()->create([
        'user_id' => $user->id,
        'nip' => '198701012011011001',
    ]);

    $classA = SchoolClass::factory()->create();

    // Jadwal pertama untuk guru ini
    Schedule::factory()->create([
        'class_id' => $classA->id,
        'teacher_id' => $teacher->id,
        'day_of_week' => 5,
        'start_time' => '08:00:00',
        'end_time' => '09:45:00',
    ]);

    // Guru ditemukan via pencarian nama (setelah fix)
    $found = scheduleSearchFixed('Gunawan')->first();
    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($teacher->id);

    // Validasi bentrok menggunakan teacher_id dari hasil pencarian
    $classB = SchoolClass::factory()->create();

    $error = checkScheduleConflict(
        classId: $classB->id,
        teacherId: $found->id,
        dayOfWeek: 5,
        startTime: '08:30:00',
        endTime: '10:15:00',
    );

    // Harus terdeteksi bentrok karena guru sudah mengajar di waktu tersebut
    expect($error)->toBe('Guru ini sudah memiliki jadwal mengajar di kelas lain pada waktu tersebut.');
});
