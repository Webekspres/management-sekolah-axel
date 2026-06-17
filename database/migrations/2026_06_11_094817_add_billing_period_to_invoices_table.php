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
        Schema::table('invoices', function (Blueprint $table): void {
            $table->char('billing_period', 7)->nullable()->after('description');
        });

        DB::table('invoices')->orderBy('created_at')->chunk(100, function ($invoices): void {
            foreach ($invoices as $invoice) {
                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update(['billing_period' => date('Y-m', strtotime((string) $invoice->due_date))]);
            }
        });

        $duplicateGroups = DB::table('invoices')
            ->select('student_id', 'academic_year_id', 'billing_period', DB::raw('COUNT(*) as total'))
            ->whereNotNull('billing_period')
            ->groupBy('student_id', 'academic_year_id', 'billing_period')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            $keepId = DB::table('invoices')
                ->where('student_id', $group->student_id)
                ->where('academic_year_id', $group->academic_year_id)
                ->where('billing_period', $group->billing_period)
                ->orderBy('created_at')
                ->value('id');

            DB::table('invoices')
                ->where('student_id', $group->student_id)
                ->where('academic_year_id', $group->academic_year_id)
                ->where('billing_period', $group->billing_period)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->char('billing_period', 7)->nullable(false)->change();
            $table->unique(['student_id', 'academic_year_id', 'billing_period'], 'invoices_student_year_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique('invoices_student_year_period_unique');
            $table->dropColumn('billing_period');
        });
    }
};
