<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->json('target_role_json')->nullable();
        });

        DB::table('announcements')
            ->select(['id', 'target_role'])
            ->orderBy('id')
            ->get()
            ->each(function (object $announcement): void {
                DB::table('announcements')
                    ->where('id', $announcement->id)
                    ->update([
                        'target_role_json' => json_encode([$announcement->target_role], JSON_THROW_ON_ERROR),
                    ]);
            });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('target_role');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->renameColumn('target_role_json', 'target_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('target_role_enum')->nullable();
        });

        DB::table('announcements')
            ->select(['id', 'target_role'])
            ->orderBy('id')
            ->get()
            ->each(function (object $announcement): void {
                $decodedTargetRole = is_array($announcement->target_role)
                    ? $announcement->target_role
                    : json_decode((string) $announcement->target_role, true);
                $targetRole = is_array($decodedTargetRole) ? ($decodedTargetRole[0] ?? null) : null;

                DB::table('announcements')
                    ->where('id', $announcement->id)
                    ->update([
                        'target_role_enum' => in_array($targetRole, ['super_admin', 'kepala_sekolah', 'guru', 'siswa_ortu'], true)
                            ? $targetRole
                            : 'guru',
                    ]);
            });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('target_role');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->renameColumn('target_role_enum', 'target_role');
        });
    }
};
