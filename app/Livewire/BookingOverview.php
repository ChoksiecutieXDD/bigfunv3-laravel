<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.overview')]
#[Title('Booking Overview')]
class BookingOverview extends Component
{
    public $booking;
    public $items = [];
    public $payments = [];
    public $gallery_files = [];
    public $grouped_extras = [];
    public $calculated_extras_total = 0.00;
    public $total_paid = 0.00;
    public $balance = 0.00;
    public $address_line_1;
    public $address_line_2;
    public $from_url;
 
    public function mount($id)
    {
        $this->from_url = request()->query('back');
        $booking = Booking::findOrFail($id);
        $this->booking = $booking->toArray();

        // Items grouping or list
        $this->items = BookingItem::where('booking_id', $id)
            ->get()
            ->map(function($item) {
                return [
                    'item_name' => $item->item_name,
                    'category' => $item->category ?? 'General'
                ];
            })->toArray();

        // Payments
        $payments = BookingPayment::where('booking_id', $id)
            ->orderBy('payment_date', 'desc')
            ->get();
        $this->payments = $payments->toArray();
        $this->total_paid = $payments->sum('amount');
        $this->balance = max(0, $booking->total_amount - $this->total_paid);

        // Gallery / Attachments
        $this->gallery_files = collect([
            $booking->delivery_attachment,
            $booking->delivery_attachment_1,
            $booking->delivery_attachment_2,
            $booking->delivery_attachment_3,
            $booking->delivery_attachment_4
        ])->filter()->values()->toArray();

        // Extras Logic
        $gen = json_decode($booking->general_extra ?? '[]', true) ?? [];
        $spec = json_decode($booking->specific_extra ?? '[]', true) ?? [];
        
        $this->calculated_extras_total = array_sum($gen) + array_sum($spec);
        
        $this->grouped_extras = [];
        if (!empty($gen)) {
            $this->grouped_extras['General Extras'] = collect($gen)
                ->map(fn($cost, $name) => ['name' => $name, 'cost' => (float)$cost])
                ->values()->toArray();
        }
        if (!empty($spec)) {
            $this->grouped_extras['Specific Extras'] = collect($spec)
                ->map(fn($cost, $name) => ['name' => $name, 'cost' => (float)$cost])
                ->values()->toArray();
        }

        // Addresses
        $this->address_line_1 = $booking->address_line_1;
        $this->address_line_2 = trim(($booking->suburb ?? '') . ' ' . ($booking->state ?? '') . ' ' . ($booking->postcode ?? ''));
    }

    public function render()
    {
        return view('livewire.booking-overview');
    }
}
