<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_policies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('target_model');
            $table->json('abilities');
            $table->json('permanent_roles')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        DB::table('access_policies')->insert([
            [
                'id' => (string) Str::ulid(),
                'code' => 'announcement_management',
                'name' => 'Manajemen Pengumuman',
                'description' => 'Akses membuat, mengubah, menghapus, dan melihat seluruh pengumuman lintas role.',
                'target_model' => 'App\\Models\\Announcement',
                'abilities' => json_encode(['viewAny', 'view', 'create', 'update', 'delete'], JSON_THROW_ON_ERROR),
                'permanent_roles' => json_encode(['super_admin', 'kepala_sekolah', 'guru'], JSON_THROW_ON_ERROR),
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'lesson_plan_management',
                'name' => 'Manajemen RPP',
                'description' => 'Akses kelola data RPP termasuk melihat, membuat, mengubah, dan menghapus.',
                'target_model' => 'App\\Models\\LessonPlan',
                'abilities' => json_encode(['viewAny', 'view', 'create', 'update', 'delete'], JSON_THROW_ON_ERROR),
                'permanent_roles' => json_encode(['super_admin', 'kepala_sekolah', 'guru'], JSON_THROW_ON_ERROR),
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'kbm_management',
                'name' => 'Manajemen Laporan KBM',
                'description' => 'Akses kelola data laporan KBM termasuk melihat, membuat, mengubah, dan menghapus.',
                'target_model' => 'App\\Models\\Kbm',
                'abilities' => json_encode(['viewAny', 'view', 'create', 'update', 'delete'], JSON_THROW_ON_ERROR),
                'permanent_roles' => json_encode(['super_admin', 'kepala_sekolah', 'guru'], JSON_THROW_ON_ERROR),
                'is_active' => true,
                'created_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_policies');
    }
};
