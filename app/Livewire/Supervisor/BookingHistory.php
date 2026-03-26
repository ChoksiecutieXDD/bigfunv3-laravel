<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Booking;

#[Layout('components.layouts.supervisor')]
class BookingHistory extends Component
{
    use WithPagination;

    public $search = '';
    public $date_filter = '';

    // Reset pagination when search or date filters change
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'date_filter']);
        $this->resetPage();
    }

    public function render()
    {
        // Assuming your Booking model has an 'items' relationship defined:
        // public function items() { return $this->hasMany(BookingItem::class); }
        $query = Booking::with('items');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('id', 'like', '%' . $this->search . '%')
                    ->orWhere('customer_first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('customer_last_name', 'like', '%' . $this->search . '%')
                    ->orWhereRaw("CONCAT(customer_first_name, ' ', customer_last_name) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        if (!empty($this->date_filter)) {
            $query->whereDate('event_date', $this->date_filter);
        }

        $history = $query->orderBy('event_date', 'desc')->paginate(10);

        return view('livewire.supervisor.booking-history', [
            'history' => $history,
            'total_rows' => $history->total(),
        ]);
    }
}
