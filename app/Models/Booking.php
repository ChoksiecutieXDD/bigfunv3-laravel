<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    // This tells Laravel which table to look at (optional if your table is named 'bookings')
    protected $table = 'bookings';

    // 👇 THE FIX: Tells Laravel to stop trying to update the missing 'updated_at' column
    const UPDATED_AT = null;

    // This allows mass assignment for your fields
    protected $guarded = [];

    // Your relationships
    public function items()
    {
        return $this->hasMany(BookingItem::class);
    }

    public function payments()
    {
        return $this->hasMany(BookingPayment::class);
    }

    /**
     * Get the total amount paid by summing all related payments.
     */
    public function getTotalPaidAttribute()
    {
        return (float) $this->payments->sum('amount');
    }
}
