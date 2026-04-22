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
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('invoice_number')->unique();
            $table->foreignUlid('student_id')->constrained('students');
            $table->foreignUlid('academic_year_id')->constrained('academic_years');
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->enum('status', ['UNPAID', 'PENDING', 'PAID', 'FAILED'])->default('UNPAID');
            $table->date('due_date');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
