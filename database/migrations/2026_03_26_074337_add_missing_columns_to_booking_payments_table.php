<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            // Add the missing columns after 'payment_method'
            $table->string('payment_type')->nullable()->after('payment_method');
            $table->string('reference')->nullable()->after('payment_date');
        });
    }

    public function down()
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            // Drop them if we rollback
            $table->dropColumn(['payment_type', 'reference']);
        });
    }
};
