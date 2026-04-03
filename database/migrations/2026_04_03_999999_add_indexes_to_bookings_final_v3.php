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
        try {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index('event_date', 'bookings_event_date_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }

        try {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index('invoice_number', 'bookings_invoice_number_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $col) {
            $col->dropIndex(['event_date']);
            $col->dropIndex(['invoice_number']);
        });
    }
};
