<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Address;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\Level;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Rapor;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $levels = $this->normalizeLevels();

        // Always seed access policies (idempotent)
        $this->call(AccessPolicySeeder::class);

        if ($this->hasSeededDemoData()) {
            $this->command?->info('Data demo sudah ada, seeding dilewati untuk mencegah duplikasi.');

            return;
        }

        // --- 1. Wilayah Indonesia (province, city, sub_district, village) ---
        $this->call(IndonesianRegionSeeder::class);

        DB::transaction(fn () => $this->seedDemoData($levels));
    }

    /**
     * @param  Collection<int, Level>  $levels
     */
    private function seedDemoData(Collection $levels): void
    {

        // --- 2. Referensi independen ---
        $academicYear = AcademicYear::query()->firstOrCreate(
            ['name' => '2025/2026', 'semester' => 'Genap'],
            ['is_active' => true],
        );

        $subjects = Subject::factory(10)->sequence(
            fn ($sequence) => ['level_id' => $levels->random()->id]
        )->create();
        $subjectsByLevel = $subjects->groupBy('level_id');

        Setting::factory(5)->create();

        // --- 3. Akun tetap (idempotent) ---
        $demoUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@example.com',
                'role' => 'super_admin',
                'password' => Hash::make('admin123'),
                'gender' => 'L',
            ],
            [
                'name' => 'Kepala Sekolah',
                'email' => 'kepsek@example.com',
                'role' => 'kepala_sekolah',
                'password' => Hash::make('kepsek123'),
                'gender' => 'L',
            ],
            [
                'name' => 'Guru Demo',
                'email' => 'guru@example.com',
                'role' => 'guru',
                'password' => Hash::make('guru123'),
                'gender' => 'L',
            ],
            [
                'name' => 'Siswa Demo',
                'email' => 'siswa@example.com',
                'role' => 'siswa_ortu',
                'password' => Hash::make('siswa123'),
                'gender' => 'L',
            ],
        ];

        foreach ($demoUsers as $userData) {
            $user = User::query()->firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'email_verified_at' => now(),
                    'phone_number' => fake()->numerify('08##########'),
                    'place_of_birth' => fake()->city(),
                    'date_of_birth' => fake()->dateTimeBetween('-50 years', '-15 years')->format('Y-m-d'),
                    'is_active' => true,
                ])
            );

            if (! $user->address_id) {
                $address = Address::factory()->create();
                $user->update([
                    'address_id' => $address->id,
                    'city_id' => $address->city_id,
                ]);
            }
        }

        // --- 4. Guru + Teacher record ---
        $guruUsers = User::factory(10)->asGuru()->withAddress()->create();

        $teachers = $guruUsers->map(
            fn (User $user) => Teacher::factory()->create(['user_id' => $user->id])
        );

        // --- 5. Kelas ---
        $classes = $teachers->map(
            fn (Teacher $teacher) => SchoolClass::factory()->create([
                'teacher_id' => $teacher->id,
                'level_id' => $levels->random()->id,
                'academic_year_id' => $academicYear->id,
            ])
        );

        // --- 6. Jadwal per kelas ---
        $schedules = $classes->flatMap(
            function (SchoolClass $class) use ($subjects, $subjectsByLevel) {
                $levelSubjects = $subjectsByLevel->get($class->level_id, collect());
                $subjectForClass = $levelSubjects->isNotEmpty() ? $levelSubjects->random() : $subjects->random();

                return Schedule::factory(5)->create([
                    'class_id' => $class->id,
                    'teacher_id' => $class->teacher_id,
                    'subject_id' => $subjectForClass->id,
                ]);
            }
        );

        // --- 7. Lesson plan per guru ---
        $classesByTeacherId = $classes->keyBy('teacher_id');
        $lessonPlans = $teachers->flatMap(
            function (Teacher $teacher) use ($classesByTeacherId, $subjects, $subjectsByLevel) {
                $class = $classesByTeacherId->get($teacher->id) ?? $classesByTeacherId->first();
                $levelSubjects = $class ? $subjectsByLevel->get($class->level_id, collect()) : collect();
                $subjectForClass = $levelSubjects->isNotEmpty() ? $levelSubjects->random() : $subjects->random();

                return LessonPlan::factory(3)->approved()->create([
                    'teacher_id' => $teacher->id,
                    'class_id' => $class?->id,
                    'subject_id' => $subjectForClass->id,
                ]);
            }
        );

        // --- 8. KBM per jadwal ---
        $kbms = $schedules->flatMap(
            fn (Schedule $schedule) => Kbm::factory(3)->approved()->create([
                'schedule_id' => $schedule->id,
                'lesson_plan_id' => $lessonPlans->random()->id,
            ])
        );

        // --- 9. Siswa per kelas ---
        $allStudents = $classes->flatMap(function (SchoolClass $class) {
            $siswaUsers = User::factory(15)->asSiswa()->withAddress()->create();

            return $siswaUsers->map(
                fn (User $user) => Student::factory()->create([
                    'user_id' => $user->id,
                    'class_id' => $class->id,
                ])
            );
        });

        // --- 10. Absensi per KBM ---
        $kbms->each(function (Kbm $kbm) use ($allStudents) {
            $sample = $allStudents->random(min(10, $allStudents->count()));
            $sample->each(
                fn (Student $student) => Attendance::factory()->create([
                    'kbm_id' => $kbm->id,
                    'student_id' => $student->id,
                ])
            );
        });

        // --- 11. Nilai per siswa ---
        $allStudents->each(function (Student $student) use ($subjects, $academicYear) {
            Grade::factory(5)->create([
                'student_id' => $student->id,
                'subject_id' => $subjects->random()->id,
                'academic_year_id' => $academicYear->id,
            ]);
        });

        // --- 12. Invoice + Payment per siswa ---
        $allStudents->each(function (Student $student) use ($academicYear) {
            $invoice = Invoice::factory()->create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
            ]);

            Payment::factory()->paid()->create(['invoice_id' => $invoice->id]);

            // Sebagian siswa punya invoice menunggak
            if (fake()->boolean(30)) {
                $overdueInvoice = Invoice::factory()->overdue()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                ]);

                Payment::factory()->pending()->create(['invoice_id' => $overdueInvoice->id]);
            }
        });

        // --- 13. Rapor per siswa ---
        $allStudents->each(
            fn (Student $student) => Rapor::factory()->create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
            ])
        );

        // --- 14. Pengumuman ---
        Announcement::factory(5)->forGuru()->create();
        Announcement::factory(5)->forSiswa()->create();

        // --- 15. Notifikasi per user ---
        User::all()->each(
            fn (User $user) => Notification::factory(3)->unread()->create(['user_id' => $user->id])
        );

        // --- 16. Log Aktivitas (Baru) ---
        $allUsers = User::all();
        foreach (range(1, 100) as $i) {
            ActivityLog::factory()->create([
                'user_id' => $allUsers->random()->id,
            ]);
        }
    }

    private function hasSeededDemoData(): bool
    {
        return User::query()->where('email', 'admin@example.com')->exists()
            && Student::query()->exists()
            && Teacher::query()->exists()
            && SchoolClass::query()->exists();
    }

    /**
     * @return Collection<int, Level>
     */
    protected function normalizeLevels(): Collection
    {
        $references = $this->canonicalLevelReferences();
        $existingLevels = Level::query()->get();
        $canonicalLevels = collect();

        foreach ($references as $reference) {
            $canonicalName = $reference['name'];
            $canonicalLevel = $existingLevels->first(
                fn (Level $level): bool => $this->resolveCanonicalLevelName($level->name) === $canonicalName
            );

            if (! $canonicalLevel) {
                $canonicalLevel = Level::query()->create([
                    'name' => $canonicalName,
                    'default_spp' => $reference['default_spp'],
                ]);

                $existingLevels->push($canonicalLevel);
            }

            $canonicalLevel->update([
                'name' => $canonicalName,
                'default_spp' => $reference['default_spp'],
            ]);

            $duplicateIds = $existingLevels
                ->filter(
                    fn (Level $level): bool => $level->id !== $canonicalLevel->id
                        && $this->resolveCanonicalLevelName($level->name) === $canonicalName
                )
                ->pluck('id');

            if ($duplicateIds->isNotEmpty()) {
                SchoolClass::query()->whereIn('level_id', $duplicateIds)->update(['level_id' => $canonicalLevel->id]);
                Subject::query()->whereIn('level_id', $duplicateIds)->update(['level_id' => $canonicalLevel->id]);
                Level::query()->whereIn('id', $duplicateIds)->delete();

                $existingLevels = $existingLevels->reject(
                    fn (Level $level): bool => $duplicateIds->contains($level->id)
                )->values();
            }

            $canonicalLevels->push($canonicalLevel->fresh());
        }

        return $canonicalLevels->values();
    }

    /**
     * @return Collection<int, array{name: string, default_spp: int, aliases: array<int, string>}>
     */
    protected function canonicalLevelReferences(): Collection
    {
        return collect([
            [
                'name' => 'SD',
                'default_spp' => 150000,
                'aliases' => ['sekolah dasar', 'elementary', 'primary'],
            ],
            [
                'name' => 'SMP',
                'default_spp' => 250000,
                'aliases' => ['sekolah menengah pertama', 'junior high', 'middle school'],
            ],
            [
                'name' => 'SMA',
                'default_spp' => 350000,
                'aliases' => ['sekolah menengah atas', 'senior high', 'high school'],
            ],
        ]);
    }

    protected function resolveCanonicalLevelName(string $rawName): ?string
    {
        $normalizedName = $this->normalizeLevelName($rawName);

        foreach ($this->canonicalLevelReferences() as $reference) {
            $candidates = collect([$reference['name'], ...$reference['aliases']])
                ->map(fn (string $name): string => $this->normalizeLevelName($name));

            if ($candidates->contains($normalizedName)) {
                return $reference['name'];
            }
        }

        return null;
    }

    protected function normalizeLevelName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? $normalized;

        return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    }
}
