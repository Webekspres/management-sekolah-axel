<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add level_id to user_policy_abilities for granular per-jenjang access control.
     * NULL means access to all levels (backward compatible).
     */
    public function up(): void
    {
        Schema::table('user_policy_abilities', function (Blueprint $table) {
            $table->ulid('level_id')->nullable()->after('expires_at')->index();
            $table->foreign('level_id')->references('id')->on('levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_policy_abilities', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropColumn('level_id');
        });
    }
};
