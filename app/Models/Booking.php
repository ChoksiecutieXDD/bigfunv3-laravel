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

    /**
     * Re-calculates and updates cached financial columns and payment status.
     */
    public function syncFinancials()
    {
        $totalPaid = (float) $this->payments()->sum('amount');
        $totalAmount = (float) $this->total_amount;
        $owingAmount = max(0, $totalAmount - $totalPaid);
        $depositRequired = (float) $this->deposit_required > 0 ? (float) $this->deposit_required : ($totalAmount / 2);

        $eventDate = \Carbon\Carbon::parse($this->event_date)->startOfDay();
        $today = \Carbon\Carbon::today();

        // Determine Payment Status
        if ($owingAmount <= 0.01) {
            $paymentStatus = 'Paid';
        } elseif ($eventDate->isBefore($today) && $owingAmount > 0) {
            $paymentStatus = 'Overdue';
        } elseif ($totalPaid >= $depositRequired) {
            $paymentStatus = 'Deposit Paid';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'Partial';
        } else {
            $paymentStatus = 'Pending';
        }

        $this->update([
            'amount_paid' => $totalPaid,
            'owing_amount' => $owingAmount,
            'payment_status' => $paymentStatus
        ]);

        $this->refresh();
    }
}
