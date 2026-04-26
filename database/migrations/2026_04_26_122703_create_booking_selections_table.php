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
        Schema::create('booking_selections', function (Blueprint $schema) {
            $schema->id();
            $schema->unsignedBigInteger('user_id')->nullable();
            $schema->string('user_name');
            $schema->string('user_role');
            $schema->date('event_date');
            $schema->string('invoice_number')->nullable();
            $schema->timestamp('expires_at');
            $schema->timestamps();

            $schema->index(['event_date', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_selections');
    }
};
