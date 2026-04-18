<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to use a raw query to update the enum
        DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM('Pending', 'Deposit Paid', 'Partial', 'Paid', 'Overdue') DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM('Pending', 'Partial', 'Paid', 'Overdue') DEFAULT 'Pending'");
    }
};
