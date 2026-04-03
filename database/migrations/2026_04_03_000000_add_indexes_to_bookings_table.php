<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('event_date', 'idx_bookings_event_date');
            $table->index('invoice_number', 'idx_bookings_invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_bookings_event_date');
            $table->dropIndex('idx_bookings_invoice_number');
        });
    }
};
