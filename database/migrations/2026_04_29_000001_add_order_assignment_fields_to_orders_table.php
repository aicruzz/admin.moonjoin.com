<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_employee_id')->nullable()->after('delivery_man_id');
            $table->unsignedBigInteger('locked_employee_id')->nullable()->after('assigned_employee_id');
            $table->string('claim_status')->nullable()->after('locked_employee_id');
            $table->string('pay_status')->nullable()->after('claim_status');

            $table->foreign('assigned_employee_id')->references('id')->on('vendor_employees')->onDelete('set null');
            $table->foreign('locked_employee_id')->references('id')->on('vendor_employees')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['assigned_employee_id']);
            $table->dropForeign(['locked_employee_id']);
            $table->dropColumn(['assigned_employee_id', 'locked_employee_id', 'claim_status', 'pay_status']);
        });
    }
};
