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
            // Making hire_type nullable as it's missing in the current form logic
            // We also check for other common columns that might be required
            if (Schema::hasColumn('bookings', 'hire_type')) {
                $table->string('hire_type')->nullable()->change();
            } else {
                $table->string('hire_type')->nullable()->after('event_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('hire_type')->nullable(false)->change();
        });
    }
};
