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
        Schema::table('classes', function (Blueprint $table) {
            $table->foreign('level_id')->references('id')->on('levels');
            $table->foreign('teacher_id')->references('id')->on('teachers');
            $table->foreign('academic_year_id')->references('id')->on('academic_years');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('kbm_id')->references('id')->on('kbms')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->foreign('province_id')->references('id')->on('provinces')->cascadeOnDelete();
            $table->foreign('city_id')->references('id')->on('cities')->cascadeOnDelete();
            $table->foreign('sub_district_id')->references('id')->on('sub_districts')->cascadeOnDelete();
            $table->foreign('village_id')->references('id')->on('villages')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('address_id')->references('id')->on('addresses')->nullOnDelete();
            $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
            $table->dropForeign(['city_id']);
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['sub_district_id']);
            $table->dropForeign(['village_id']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['kbm_id']);
            $table->dropForeign(['student_id']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['academic_year_id']);
        });
    }
};
