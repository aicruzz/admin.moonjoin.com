<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->enum('employee_earning_type', ['none', 'fixed', 'percentage'])->default('none')->after('minimum_order');
            $table->decimal('employee_earning_fixed_amount', 24, 2)->nullable()->after('employee_earning_type');
            $table->decimal('employee_earning_percentage', 8, 4)->nullable()->after('employee_earning_fixed_amount');
            $table->decimal('employee_earning_cap', 24, 2)->nullable()->after('employee_earning_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'employee_earning_type',
                'employee_earning_fixed_amount',
                'employee_earning_percentage',
                'employee_earning_cap',
            ]);
        });
    }
};
