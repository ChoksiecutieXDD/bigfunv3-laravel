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
    public $search_conf = '';
    public $search_comp = '';

    // Reset pagination when searching
    public function updatingSearchUp() { $this->resetPage('page_up'); }
    public function updatingSearchConf() { $this->resetPage('page_conf'); }
    public function updatingSearchComp() { $this->resetPage('page_comp'); }

    public function render()
    {
        $today = Carbon::today()->toDateString();

        // 1. UPCOMING SCHEDULE (Status != Cancelled, Future Dates)
        $upQuery = DB::table('bookings')
            ->where('event_date', '>=', $today)
            ->whereNotIn('status', ['Cancelled', 'Deleted']);

        if (!empty($this->search_up)) {
            $term = '%' . $this->search_up . '%';
            $upQuery->where(function ($q) use ($term) {
                $q->where('id', 'like', $term)
                    ->orWhere('customer_first_name', 'like', $term)
                    ->orWhere('customer_last_name', 'like', $term);
            });
        }
        $upcoming_bookings = $upQuery->orderBy('event_date', 'asc')->paginate(10, ['*'], 'page_up');

        // 2. CONFIRMED BOOKINGS
        $confQuery = DB::table('bookings')->where('status', 'Confirmed');
        if (!empty($this->search_conf)) {
            $term = '%' . $this->search_conf . '%';
            $confQuery->where(function ($q) use ($term) {
                $q->where('id', 'like', $term)
                    ->orWhere('customer_first_name', 'like', $term)
                    ->orWhere('customer_last_name', 'like', $term);
            });
        }
        $confirmed_bookings = $confQuery->orderBy('event_date', 'desc')->paginate(9, ['*'], 'page_conf');

        // 3. COMPLETED BOOKINGS
        $compQuery = DB::table('bookings')->where('status', 'Completed');
        if (!empty($this->search_comp)) {
            $term = '%' . $this->search_comp . '%';
            $compQuery->where(function ($q) use ($term) {
                $q->where('id', 'like', $term)
                    ->orWhere('customer_first_name', 'like', $term)
                    ->orWhere('customer_last_name', 'like', $term);
            });
        }
        $completed_bookings = $compQuery->orderBy('event_date', 'desc')->paginate(9, ['*'], 'page_comp');

        return view('livewire.admin.book-manage', [
            'upcoming_bookings' => $upcoming_bookings,
            'confirmed_bookings' => $confirmed_bookings,
            'completed_bookings' => $completed_bookings,
            'total_conf' => $confirmed_bookings->total(),
            'total_comp' => $completed_bookings->total()
        ]);
    }
}
