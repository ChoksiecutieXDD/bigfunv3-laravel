<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingPayment extends Model
{
    use HasFactory;

    // ADD THIS LINE: Tells Laravel this table doesn't use created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'amount',
        'payment_method',
        'payment_type',
        'payment_date',
        'reference',
        'card_holder',
        'card_number',
        'card_expiry',
        'card_cvv',
        'card_network',
        'notes',
    ];

    // This creates the inverse relationship back to the Booking
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
