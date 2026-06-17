<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Setting;
use App\Models\User;
use App\Support\SchoolPaymentSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Production-only seeder: creates essential operational accounts
 * (super_admin & kepala_sekolah) plus base reference data.
 * Does NOT create demo teachers, students, schedules, etc.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. Access policies (idempotent) ---
        $this->call(AccessPolicySeeder::class);

        // --- 2. Base levels (SD, SMP, SMA) ---
        $this->seedLevels();

        // --- 3. Payment instruction settings ---
        $this->seedPaymentSettings();

        // --- 4. Essential user accounts ---
        $this->seedEssentialUsers();

        $this->command?->info('Production seed selesai: akun admin & kepsek siap digunakan.');
    }

    private function seedEssentialUsers(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@hstkb.sch.id',
                'role' => 'super_admin',
                'password' => Hash::make('adminportal2026'),
                'gender' => 'L',
            ],
            [
                'name' => 'Kepala Sekolah',
                'email' => 'kepsek@hstkb.sch.id',
                'role' => 'kepala_sekolah',
                'password' => Hash::make('kepsekportal2026'),
                'gender' => 'L',
            ],
        ];

        foreach ($users as $userData) {
            User::query()->firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'email_verified_at' => now(),
                    'phone_number' => '081234567890',
                    'place_of_birth' => 'Jakarta',
                    'date_of_birth' => '1990-01-01',
                    'is_active' => true,
                ]),
            );

            $this->command?->info("User '{$userData['name']}' ({$userData['email']}) ready.");
        }
    }

    private function seedPaymentSettings(): void
    {
        $defaults = [
            SchoolPaymentSettings::BANK_NAME => 'Bank BCA',
            SchoolPaymentSettings::ACCOUNT_NUMBER => '1234567890',
            SchoolPaymentSettings::ACCOUNT_HOLDER => 'Yayasan Tunas Karya Bangsa',
            SchoolPaymentSettings::SCHOOL_WHATSAPP => '081234567890',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }

    private function seedLevels(): void
    {
        $levels = [
            ['name' => 'SD', 'default_spp' => 150000],
            ['name' => 'SMP', 'default_spp' => 250000],
            ['name' => 'SMA', 'default_spp' => 350000],
        ];

        foreach ($levels as $level) {
            Level::query()->firstOrCreate(
                ['name' => $level['name']],
                $level,
            );
        }
    }
}
