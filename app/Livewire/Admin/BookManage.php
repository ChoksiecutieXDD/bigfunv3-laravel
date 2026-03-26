<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.admin')]
#[Title('Manage Bookings | BigFun Admin')]
class BookManage extends Component
{
    use WithPagination;

    // Search properties
    public $search_up = '';
    public $search_pen = '';

    // Reset pagination when searching
    public function updatingSearchUp()
    {
        $this->resetPage('page_up');
    }

    public function updatingSearchPen()
    {
        $this->resetPage('page_pen');
    }

    public function render()
    {
        $today = Carbon::today()->toDateString();

        // ==========================================
        // 1. UPCOMING SCHEDULE (Status != Cancelled, Future Dates)
        // ==========================================
        $upQuery = DB::table('bookings')
            ->where('event_date', '>=', $today)
            ->where('status', '!=', 'Cancelled');

        if (!empty($this->search_up)) {
            $term = '%' . $this->search_up . '%';
            $upQuery->where(function ($q) use ($term) {
                $q->where('id', 'like', $term)
                    ->orWhere('customer_first_name', 'like', $term)
                    ->orWhere('customer_last_name', 'like', $term);
            });
        }

        $upcoming_bookings = $upQuery->orderBy('event_date', 'asc')->paginate(10, ['*'], 'page_up');

        // ==========================================
        // 2. PENDING CONFIRMATION
        // ==========================================
        $penQuery = DB::table('bookings')->where('status', 'Pending');

        if (!empty($this->search_pen)) {
            $term = '%' . $this->search_pen . '%';
            $penQuery->where(function ($q) use ($term) {
                $q->where('id', 'like', $term)
                    ->orWhere('customer_first_name', 'like', $term)
                    ->orWhere('customer_last_name', 'like', $term);
            });
        }

        $pending_bookings = $penQuery->orderBy('event_date', 'asc')->paginate(9, ['*'], 'page_pen');

        return view('livewire.admin.book-manage', [
            'upcoming_bookings' => $upcoming_bookings,
            'pending_bookings' => $pending_bookings,
            'total_pen' => $pending_bookings->total()
        ]);
    }
}
