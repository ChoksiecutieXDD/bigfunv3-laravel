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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('card_holder')->nullable()->after('payment_reference');
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->string('card_holder')->nullable()->after('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('card_holder');
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropColumn('card_holder');
        });
    }
};
