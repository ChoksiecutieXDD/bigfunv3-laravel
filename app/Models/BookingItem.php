<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    use HasFactory;

    // This tells Laravel which table to look at
    protected $table = 'booking_items';

    // 👇 THE FIX: Turn off automatic timestamps to prevent "Column not found" errors
    public $timestamps = false;

    // This allows mass assignment
    protected $guarded = [];

    /**
     * This creates the inverse relationship back to the Booking
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
