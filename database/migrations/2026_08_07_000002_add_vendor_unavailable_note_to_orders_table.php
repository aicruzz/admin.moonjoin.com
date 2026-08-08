<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The vendor's message for the current negotiation round, e.g.
            // "Rice is unavailable today". Kept separate from
            // unavailable_item_note, which is the customer's permanent checkout
            // instruction written at order placement and must survive negotiation.
            //
            // text, not varchar(255): markUnavailableItems() validates the note at
            // max:500, and sql_mode has no STRICT_TRANS_TABLES, so a 255-char
            // column would silently truncate. Matches order_note,
            // cancellation_note and delivery_instruction, which are all text.
            $table->text('unavailable_item_vendor_note')->nullable()->after('unavailable_item_ids');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('unavailable_item_vendor_note');
        });
    }
};
