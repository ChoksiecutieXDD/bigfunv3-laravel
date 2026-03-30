<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Booking;
use Carbon\Carbon;

#[Layout('components.layouts.plain')]
class CustomerProfile extends Component
{
    public Booking $booking;
    public $history = [];
    public $totalSpent = 0;
    public $totalBookings = 0;

    public function mount($id)
    {
        $this->booking = Booking::findOrFail($id);
        
        if ($this->booking->customer_email) {
            $historyQuery = Booking::where('customer_email', $this->booking->customer_email)
                ->orderBy('event_date', 'desc')
                ->get();

            $this->history = $historyQuery;
            $this->totalSpent = $historyQuery->sum('total_amount');
            $this->totalBookings = $historyQuery->count();
        }
    }

    public function render()
    {
        return view('livewire.supervisor.customer-profile');
    }
}
