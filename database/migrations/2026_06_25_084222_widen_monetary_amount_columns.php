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
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('custom_spp', 15, 2)->nullable()->change();
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->decimal('default_spp', 15, 2)->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('custom_spp', 10, 2)->nullable()->change();
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->decimal('default_spp', 10, 2)->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->change();
        });
    }
};
