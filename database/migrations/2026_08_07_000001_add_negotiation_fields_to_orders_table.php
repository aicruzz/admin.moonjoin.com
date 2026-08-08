<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // JSON-encoded list of order_details item ids the vendor marked
            // unavailable. Stored as text, matching how the orders table already
            // holds structured data (receiver_details is longtext, delivery_address
            // and item_details are text) and how order_details.add_on_ids stores an
            // id collection. No model cast: the controller json_encode()s the value
            // itself, so casting would double-encode it.
            $table->text('unavailable_item_ids')->nullable()->after('unavailable_item_note');

            // Set when the vendor asks the customer to edit the order (Workflow B).
            // Cleared once the customer submits their edit.
            $table->boolean('customer_edit_requested')->default(false)->after('unavailable_item_ids');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['unavailable_item_ids', 'customer_edit_requested']);
        });
    }
};
