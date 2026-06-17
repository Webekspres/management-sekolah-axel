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
        Schema::create('user_policy_abilities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->ulid('access_policy_id')->index();
            $table->string('ability'); // 'create', 'read', 'update', 'delete', etc.
            $table->boolean('is_inherited')->default(false); // true if inherited from role
            $table->string('source_role')->nullable(); // which role this ability comes from
            $table->ulid('granted_by_user_id')->nullable(); // who assigned this
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('access_policy_id')->references('id')->on('access_policies')->cascadeOnDelete();
            $table->foreign('granted_by_user_id')->references('id')->on('users')->cascadeOnDelete()->nullable();

            // Prevent duplicate direct assignments (inherited can have duplicates from different role checks)
            $table->unique(['user_id', 'access_policy_id', 'ability', 'is_inherited'], 'unique_user_ability');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_policy_abilities');
    }
};
