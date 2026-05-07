<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Add new columns
            $table->string('log_name', 100)->nullable()->after('entity_id');
            $table->json('properties')->nullable()->after('log_name');

            // Make user_id nullable: drop FK, change column, re-add FK
            $table->dropForeign('activity_logs_user_id_foreign');
            $table->char('user_id', 26)->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Add indexes for filter/sort performance
            $table->index('log_name');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Drop added indexes
            $table->dropIndex(['log_name']);
            $table->dropIndex(['action']);
            $table->dropIndex(['created_at']);

            // Drop added columns
            $table->dropColumn(['log_name', 'properties']);

            // Restore user_id to NOT NULL: drop FK, change column, re-add FK
            $table->dropForeign(['user_id']);
            $table->char('user_id', 26)->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
