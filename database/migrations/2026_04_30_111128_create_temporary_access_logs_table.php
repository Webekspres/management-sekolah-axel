<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_access_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('access_policy_id')->constrained('access_policies')->cascadeOnDelete();
            $table->string('ability');
            $table->foreignUlid('level_id')->nullable()->constrained('levels')->nullOnDelete();
            $table->foreignUlid('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignUlid('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index('user_id');
            $table->index('granted_at');
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_access_logs');
    }
};
