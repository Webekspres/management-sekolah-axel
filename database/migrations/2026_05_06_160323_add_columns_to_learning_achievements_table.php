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
        Schema::table('learning_achievements', function (Blueprint $table) {
            $table->enum('material_coverage_status', ['Terpenuhi', 'Tidak Terpenuhi'])->nullable()->after('notes');
            $table->enum('daily_assessment_predicate', ['Kurang', 'Cukup', 'Baik', 'Sangat Baik'])->nullable()->after('material_coverage_status');
            $table->enum('midterm_assessment_predicate', ['Kurang', 'Cukup', 'Baik', 'Sangat Baik'])->nullable()->after('daily_assessment_predicate');
            $table->enum('final_assessment_predicate', ['Kurang', 'Cukup', 'Baik', 'Sangat Baik'])->nullable()->after('midterm_assessment_predicate');
            $table->string('achievement_status')->nullable()->after('final_assessment_predicate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_achievements', function (Blueprint $table) {
            $table->dropColumn([
                'material_coverage_status',
                'daily_assessment_predicate',
                'midterm_assessment_predicate',
                'final_assessment_predicate',
                'achievement_status',
            ]);
        });
    }
};
