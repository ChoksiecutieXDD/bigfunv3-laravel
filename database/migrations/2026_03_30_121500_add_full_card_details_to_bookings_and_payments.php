<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'card_number')) {
                $table->string('card_number')->nullable()->after('card_last4');
            }
            if (!Schema::hasColumn('bookings', 'card_expiry')) {
                $table->string('card_expiry')->nullable()->after('card_number');
            }
            if (!Schema::hasColumn('bookings', 'card_cvv')) {
                $table->string('card_cvv')->nullable()->after('card_expiry');
            }
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_payments', 'card_number')) {
                $table->string('card_number')->nullable()->after('reference');
            }
            if (!Schema::hasColumn('booking_payments', 'card_expiry')) {
                $table->string('card_expiry')->nullable()->after('card_number');
            }
            if (!Schema::hasColumn('booking_payments', 'card_cvv')) {
                $table->string('card_cvv')->nullable()->after('card_expiry');
            }
            if (!Schema::hasColumn('booking_payments', 'card_network')) {
                $table->string('card_network')->nullable()->after('card_cvv');
            }
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['card_number', 'card_expiry', 'card_cvv']);
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropColumn(['card_number', 'card_expiry', 'card_cvv', 'card_network']);
        });
    }
};
