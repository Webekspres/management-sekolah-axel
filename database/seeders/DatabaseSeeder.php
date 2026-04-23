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
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. Wilayah Indonesia (province, city, sub_district, village) ---
        $this->call(IndonesianRegionSeeder::class);

        // --- 2. Referensi independen ---
        $academicYear = AcademicYear::factory()->active()->create([
            'name' => '2025/2026',
            'semester' => 'Genap',
        ]);

        $levels = Level::factory(3)->create();

        $subjects = Subject::factory(10)->sequence(
            fn ($sequence) => ['level_id' => $levels->random()->id]
        )->create();

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
            fn (SchoolClass $class) => Schedule::factory(5)->create([
                'class_id' => $class->id,
                'teacher_id' => $class->teacher_id,
                'subject_id' => $subjects->random()->id,
            ])
        );

        // --- 7. Lesson plan per guru ---
        $lessonPlans = $teachers->flatMap(
            fn (Teacher $teacher) => LessonPlan::factory(3)->approved()->create([
                'teacher_id' => $teacher->id,
                'subject_id' => $subjects->random()->id,
            ])
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
}
