<?php

namespace App\Livewire\Staff;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

#[Layout('components.layouts.staff')]
#[Title('Dashboard | BigFun')]
class StaffDashboard extends Component
{
    use WithPagination;

    public $search = '';

    // Reset pagination when searching so you don't get stuck on an empty page 3
    public function updatingSearch()
    {
        $this->resetPage('up_page');
        $this->resetPage('curr_page');
        $this->resetPage('pend_page');
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->updatingSearch();
    }

    public function render()
    {
        $today = Carbon::today()->toDateString();
        $user = Auth::user();
        $fullName = trim($user->first_name . ' ' . $user->last_name);
        $firstNameOnly = trim($user->first_name);

        // Base Query constraint for all sections
        $query = DB::table('bookings')->whereDate('event_date', '>=', $today);

        // Search Logic
        if (!empty($this->search)) {
            $searchStr = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchStr) {
                $q->where('id', 'like', $searchStr)
                    ->orWhere('customer_first_name', 'like', $searchStr)
                    ->orWhere('customer_last_name', 'like', $searchStr);
            });
        }

        // 1. Upcoming Schedule (Status != Cancelled)
        // Notice we assign unique page names so the tables don't paginate at the same time
        $upcoming_tasks = (clone $query)
            ->where('status', '!=', 'Cancelled')
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(6, ['*'], 'up_page');

        // 2. Current Operations (Confirmed)
        $curr_tasks = (clone $query)
            ->where('status', 'Confirmed')
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(10, ['*'], 'curr_page');

        // 3. Pendings Book (Pending)
        $pend_tasks = (clone $query)
            ->where('status', 'Pending')
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(10, ['*'], 'pend_page');

        // 4. Header Stats
        $upcoming_count = DB::table('bookings')
            ->where('status', '!=', 'Cancelled')
            ->whereDate('event_date', '>=', $today)
            ->count();

        $completed_count = DB::table('bookings')
            ->where('status', 'Completed')
            ->count();

        return view('livewire.staff.staff-dashboard', [
            'firstNameOnly' => $firstNameOnly,
            'fullName' => $fullName,
            'upcoming_tasks' => $upcoming_tasks,
            'curr_tasks' => $curr_tasks,
            'pend_tasks' => $pend_tasks,
            'upcoming_count' => $upcoming_count,
            'completed_count' => $completed_count,
        ]);
    }
}
