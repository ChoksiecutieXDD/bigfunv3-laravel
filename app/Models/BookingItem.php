<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    // This creates the inverse relationship back to the Booking
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
